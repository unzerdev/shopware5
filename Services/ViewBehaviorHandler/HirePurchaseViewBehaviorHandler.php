<?php

declare(strict_types=1);

namespace HeidelPayment\Services\ViewBehaviorHandler;

use Enlight_View_Default as View;
use HeidelPayment\Services\Heidelpay\HeidelpayClientServiceInterface;
use HeidelPayment\Services\HeidelpayApiLoggerServiceInterface;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\PaymentTypes\HirePurchaseDirectDebit;
use Smarty_Data;

class HirePurchaseViewBehaviorHandler implements ViewBehaviorHandlerInterface
{
    /** @var HeidelpayClientServiceInterface */
    private $heidelpayClient;

    /** @var HeidelpayApiLoggerServiceInterface */
    private $apiLoggerService;

    public function __construct(HeidelpayClientServiceInterface $heidelpayClientService, HeidelpayApiLoggerServiceInterface $apiLoggerService)
    {
        $this->heidelpayClient  = $heidelpayClientService;
        $this->apiLoggerService = $apiLoggerService;
    }

    /**
     * {@inheritdoc}
     */
    public function processCheckoutFinishBehavior(View $view, string $paymentId): void
    {
        $charge = $this->getPaymentTypeByPaymentId($paymentId);

        if (!$charge) {
            return;
        }

        $view->assign([
            'heidelpay' => [
                'interest'          => $charge->getTotalInterestAmount(),
                'totalWithInterest' => $charge->getTotalAmount(),
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     *
     * Is not used in this payment
     */
    public function processDocumentBehavior(Smarty_Data $viewAssignments, string $paymentId, int $documentTypeId): void
    {
    }

    /**
     * {@inheritdoc}
     *
     * Is not used in this payment
     */
    public function processEmailVariablesBehavior(string $paymentId): array
    {
        return [];
    }

    private function getPaymentTypeByPaymentId($paymentId): ?HirePurchaseDirectDebit
    {
        try {
            $paymentType = $this->heidelpayClient->getHeidelpayClient()->fetchPayment($paymentId)->getChargeByIndex(0);

            if ($paymentType) {
                return $paymentType->getPayment()->getPaymentType();
            }
        } catch (HeidelpayApiException $apiException) {
            $this->apiLoggerService->logException(sprintf('Error while fetching first charge of payment with payment-id [%s]', $paymentId), $apiException);
        }

        return null;
    }
}
