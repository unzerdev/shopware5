<?php

declare(strict_types=1);

namespace UnzerPayment\Components\ViewBehaviorHandler;

use Enlight_View_Default as View;
use Smarty_Data;
use UnzerPayment\Services\UnzerPaymentApiLogger\UnzerPaymentApiLoggerServiceInterface;
use UnzerPayment\Services\UnzerPaymentClient\UnzerPaymentClientServiceInterface;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\PaymentTypes\InstallmentSecured;

class InstallmentSecuredViewBehaviorHandler implements ViewBehaviorHandlerInterface
{
    /** @var UnzerPaymentClientServiceInterface */
    private $unzerPaymentClientService;

    /** @var UnzerPaymentApiLoggerServiceInterface */
    private $apiLoggerService;

    public function __construct(UnzerPaymentClientServiceInterface $unzerPaymentClientService, UnzerPaymentApiLoggerServiceInterface $apiLoggerService)
    {
        $this->unzerPaymentClientService = $unzerPaymentClientService;
        $this->apiLoggerService          = $apiLoggerService;
    }

    public function processCheckoutFinishBehavior(View $view, string $transactionId): void
    {
        $charge = $this->getPaymentTypeByTransactionId($transactionId);

        if (!($charge instanceof InstallmentSecured)) {
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
     */
    public function supportDocumentBehavior(int $documentType): bool
    {
        return false;
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

    private function getPaymentTypeByTransactionId(string $transactionId): ?BasePaymentType
    {
        try {
            // TODO PAYMENT_ID_VS_TRANSACTION_ID_ISSUE
            // $transactionId and $paymentId are used in the code, but mean the same thing. This leads to confusion. Correct this.
            // Search for PAYMENT_ID_VS_TRANSACTION_ID_ISSUE to see a related issue.
            $paymentType = $this->unzerPaymentClientService
                ->getUnzerPaymentClientByPaymentId($transactionId)
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
