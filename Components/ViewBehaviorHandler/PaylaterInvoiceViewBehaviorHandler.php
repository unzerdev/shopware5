<?php

declare(strict_types=1);

namespace UnzerPayment\Components\ViewBehaviorHandler;

use Enlight_View_Default as View;
use Smarty_Data;
use UnzerPayment\Services\UnzerPaymentApiLogger\UnzerPaymentApiLoggerServiceInterface;
use UnzerPayment\Services\UnzerPaymentClient\UnzerPaymentClientServiceInterface;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\TransactionTypes\Authorization;

class PaylaterInvoiceViewBehaviorHandler implements ViewBehaviorHandlerInterface
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
        $authorization = $this->getAuthorization($paymentId);

        if (null === $authorization) {
            return;
        }

        $bankData = $this->getBankData($authorization);

        $viewAssignments->assign('bankData', $bankData, true);
    }

    /**
     * {@inheritdoc}
     */
    public function processEmailVariablesBehavior(string $paymentId): array
    {
        $authorization = $this->getAuthorization($paymentId);

        if (null === $authorization) {
            return [];
        }

        return ['bankData' => $this->getBankData($authorization)];
    }

    private function getAuthorization(string $paymentId): ?Authorization
    {
        try {
            return $this->unzerPaymentClientService->getUnzerPaymentClientByPaymentId($paymentId)->fetchPayment($paymentId)->getAuthorization();
        } catch (UnzerApiException $apiException) {
            $this->apiLoggerService->logException(sprintf('Error while fetching authorization of payment with payment-id [%s]', $paymentId), $apiException);

            return null;
        }
    }

    private function getBankData(Authorization $authorization): array
    {
        return [
            'iban'       => $authorization->getIban(),
            'bic'        => $authorization->getBic(),
            'holder'     => $authorization->getHolder(),
            'amount'     => $authorization->getAmount(),
            'currency'   => $authorization->getCurrency(),
            'descriptor' => $authorization->getDescriptor(),
        ];
    }
}
