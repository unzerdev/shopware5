<?php

namespace HeidelPayment\Services\ViewBehaviorHandler;

use Enlight_View_Default as View;
use HeidelPayment\Services\Heidelpay\HeidelpayClientServiceInterface;
use HeidelPayment\Services\HeidelpayApiLoggerServiceInterface;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Heidelpay;
use heidelpayPHP\Resources\TransactionTypes\Charge;
use Smarty_Data;

class InvoiceViewBehaviorHandler implements ViewBehaviorHandlerInterface
{
    private const DOCUMENT_TYPE_INVOICE = 1;

    /** @var Heidelpay */
    private $heidelpayClient;

    /** @var HeidelpayApiLoggerServiceInterface */
    private $apiLoggerService;

    public function __construct(HeidelpayClientServiceInterface $heidelpayClientService, HeidelpayApiLoggerServiceInterface $apiLoggerService)
    {
        $this->heidelpayClient  = $heidelpayClientService->getHeidelpayClient();
        $this->apiLoggerService = $apiLoggerService;
    }

    /**
     * {@inheritdoc}
     */
    public function processCheckoutFinishBehavior(View $view, string $paymentId): void
    {
        /** @var Charge $paymentType */
        $charge   = $this->getCharge($paymentId);
        $bankData = $this->getBankData($charge);

        $view->assign('bankData', $bankData);
    }

    /**
     * {@inheritdoc}
     */
    public function processDocumentBehavior(Smarty_Data $viewAssignments, string $paymentId, int $documentTypeId): void
    {
        if ($documentTypeId !== self::DOCUMENT_TYPE_INVOICE) {
            return;
        }

        /** @var Charge $paymentType */
        $charge   = $this->getCharge($paymentId);
        $bankData = $this->getBankData($charge);

        $viewAssignments->assign('bankData', $bankData);
    }

    /**
     * {@inheritdoc}
     */
    public function processEmailVariablesBehavior(string $paymentId): array
    {
        $charge = $this->getCharge($paymentId);

        return ['bankData' => $this->getBankData($charge)];
    }

    private function getCharge(string $paymentId): Charge
    {
        try {
            $result = $this->heidelpayClient->fetchPayment($paymentId)->getChargeByIndex(0);

            $this->apiLoggerService->logResponse(sprintf('Received first charge of payment with payment-id [%s]', $paymentId), $result);

            return $result;
        } catch (HeidelpayApiException $apiException) {
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
