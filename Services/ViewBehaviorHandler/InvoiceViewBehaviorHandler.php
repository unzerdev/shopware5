<?php

namespace HeidelPayment\Services\ViewBehaviorHandler;

use Enlight_View_Default as View;
use HeidelPayment\Services\Heidelpay\HeidelpayClientServiceInterface;
use heidelpayPHP\Heidelpay;
use heidelpayPHP\Resources\TransactionTypes\Charge;
use Smarty_Data;

class InvoiceViewBehaviorHandler implements ViewBehaviorHandlerInterface
{
    private const DOCUMENT_TYPE_INVOICE = 1;

    /** @var Heidelpay */
    private $heidelpayClient;

    public function __construct(HeidelpayClientServiceInterface $heidelpayClientService)
    {
        $this->heidelpayClient = $heidelpayClientService->getHeidelpayClient();
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
    public function processEmailTemplateBehavior(View $view, string $paymentId): void
    {
        // TODO: Implement handleEmailTemplate() method.
    }

    private function getCharge(string $paymentId): Charge
    {
        return $this->heidelpayClient->fetchPayment($paymentId)->getChargeByIndex(0);
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
