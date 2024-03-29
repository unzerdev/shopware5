<?php

declare(strict_types=1);

namespace UnzerPayment\Components\ViewBehaviorHandler;

use Enlight_View_Default as View;
use Smarty_Data;
use UnzerPayment\Services\UnzerPaymentApiLogger\UnzerPaymentApiLoggerServiceInterface;
use UnzerPayment\Services\UnzerPaymentClient\UnzerPaymentClientServiceInterface;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\TransactionTypes\Charge;

class InvoiceViewBehaviorHandler implements ViewBehaviorHandlerInterface
{
    private UnzerPaymentClientServiceInterface $unzerPaymentClientService;

    private UnzerPaymentApiLoggerServiceInterface $apiLoggerService;

    public function __construct(UnzerPaymentClientServiceInterface $unzerPaymentClientService, UnzerPaymentApiLoggerServiceInterface $apiLoggerService)
    {
        $this->unzerPaymentClientService = $unzerPaymentClientService;
        $this->apiLoggerService          = $apiLoggerService;
    }

    public function processCheckoutFinishBehavior(View $view, string $transactionId): void
    {
        $charge = $this->getCharge($transactionId);

        if (null === $charge) {
            return;
        }

        $bankData = $this->getBankData($charge);

        $view->assign('bankData', $bankData);
    }

    /**
     * {@inheritdoc}
     */
    public function supportDocumentBehavior(int $documentType): bool
    {
        return $documentType === static::DOCUMENT_TYPE_INVOICE;
    }

    /**
     * {@inheritdoc}
     */
    public function processDocumentBehavior(Smarty_Data $viewAssignments, string $paymentId, int $documentTypeId): void
    {
        $charge = $this->getCharge($paymentId);

        if (null === $charge) {
            return;
        }

        $bankData = $this->getBankData($charge);

        $viewAssignments->assign('bankData', $bankData, true);
    }

    /**
     * {@inheritdoc}
     */
    public function processEmailVariablesBehavior(string $paymentId): array
    {
        $charge = $this->getCharge($paymentId);

        if (null === $charge) {
            return [];
        }

        return ['bankData' => $this->getBankData($charge)];
    }

    private function getCharge(string $paymentId): ?Charge
    {
        try {
            return $this->unzerPaymentClientService->getUnzerPaymentClientByPaymentId($paymentId)->fetchPayment($paymentId)->getChargeByIndex(0);
        } catch (UnzerApiException $apiException) {
            $this->apiLoggerService->logException(sprintf('Error while fetching first charge of payment with payment-id [%s]', $paymentId), $apiException);

            return null;
        }
    }

    private function getBankData(Charge $charge): array
    {
        return [
            'iban'       => $charge->getIban(),
            'bic'        => $charge->getBic(),
            'holder'     => $charge->getHolder(),
            'amount'     => $charge->getAmount(),
            'currency'   => $charge->getCurrency(),
            'descriptor' => $charge->getDescriptor(),
        ];
    }
}
