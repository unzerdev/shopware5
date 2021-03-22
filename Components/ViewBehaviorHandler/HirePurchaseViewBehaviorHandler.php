<?php

declare(strict_types=1);

namespace UnzerPayment\Components\ViewBehaviorHandler;

use Enlight_View_Default as View;
use Smarty_Data;
use UnzerPayment\Services\UnzerPaymentApiLogger\UnzerPaymentApiLoggerServiceInterface;
use UnzerPayment\Services\UnzerPaymentClient\UnzerPaymentClientServiceInterface;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\PaymentTypes\HirePurchaseDirectDebit;

class HirePurchaseViewBehaviorHandler implements ViewBehaviorHandlerInterface
{
    /** @var UnzerPaymentClientServiceInterface */
    private $unzerPaymentClient;

    /** @var UnzerPaymentApiLoggerServiceInterface */
    private $apiLoggerService;

    public function __construct(UnzerPaymentClientServiceInterface $unzerPaymentClientService, UnzerPaymentApiLoggerServiceInterface $apiLoggerService)
    {
        $this->unzerPaymentClient = $unzerPaymentClientService;
        $this->apiLoggerService   = $apiLoggerService;
    }

    public function processCheckoutFinishBehavior(View $view, string $transactionId): void
    {
        $charge = $this->getPaymentTypeTransactionId($transactionId);

        if (!$charge) {
            return;
        }

        $view->assign([
            'unzerPayment' => [
                'interest'          => $charge->getTotalInterestAmount(),
                'totalWithInterest' => $charge->getTotalAmount(),
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     *
     * Is not used for this payment
     */
    public function processDocumentBehavior(Smarty_Data $viewAssignments, string $paymentId, int $documentTypeId): void
    {
    }

    /**
     * {@inheritdoc}
     *
     * Is not used for this payment
     */
    public function processEmailVariablesBehavior(string $paymentId): array
    {
        return [];
    }

    private function getPaymentTypeTransactionId(string $transactionId): ?HirePurchaseDirectDebit
    {
        try {
            $paymentType = $this->unzerPaymentClient->getUnzerPaymentClient()
                ->fetchPayment($transactionId)->getChargeByIndex(0);

            if ($paymentType) {
                return $paymentType->getPayment()->getPaymentType();
            }
        } catch (UnzerApiException $apiException) {
            $this->apiLoggerService->logException(sprintf('Error while fetching first charge of payment with transaction-id [%s]', $transactionId), $apiException);
        }

        return null;
    }
}
