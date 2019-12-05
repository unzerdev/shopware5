<?php

namespace HeidelPayment\Services\ViewBehaviorHandler;

use Enlight_View_Default as View;
use HeidelPayment\Services\Heidelpay\HeidelpayClientServiceInterface;
use HeidelPayment\Services\HeidelpayApiLoggerServiceInterface;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\TransactionTypes\Charge;
use Smarty_Data;

class PrepaymentViewBehaviorHandler implements ViewBehaviorHandlerInterface
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
    public function processCheckoutFinishBehavior(View $view, string $paymentId)
    {
        /** @var Charge $paymentType */
        $charge   = $this->getCharge($paymentId);
        $bankData = $this->getBankData($charge);

        $view->assign('bankData', $bankData);
    }

    /**
     * {@inheritdoc}
     */
    public function processDocumentBehavior(Smarty_Data $viewAssignments, string $paymentId, int $documentTypeId)
    {
        if ($documentTypeId !== static::DOCUMENT_TYPE_INVOICE) {
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
            return $this->heidelpayClient->getHeidelpayClient()->fetchPayment($paymentId)->getChargeByIndex(0);
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
