<?php

namespace HeidelPayment\Services\ViewBehaviorHandler;

use Enlight_View_Default as View;
use HeidelPayment\Services\Heidelpay\HeidelpayClientServiceInterface;
use heidelpayPHP\Heidelpay;
use heidelpayPHP\Resources\TransactionTypes\Charge;

class InvoiceViewBehaviorHandler implements ViewBehaviorHandlerInterface
{
    /** @var Heidelpay */
    private $heidelpayClient;

    public function __construct(HeidelpayClientServiceInterface $heidelpayClientService)
    {
        $this->heidelpayClient = $heidelpayClientService->getHeidelpayClient();
    }

    /**
     * {@inheritdoc}
     */
    public function handleFinishPage(View $view, string $paymentId)
    {
        $view->loadTemplate('frontend/heidelpay/invoice/finish.tpl');

        /** @var Charge $paymentType */
        $charge = $this->getCharge($paymentId);
        $bankData = $this->getBankData($charge);
        $view->assign('bankData', $bankData);
    }

    public function handleInvoiceDocument(\Smarty_Data $view, string $paymentId)
    {
        /** @var Charge $paymentType */
        $charge = $this->getCharge($paymentId);
        $bankData = $this->getBankData($charge);
        $view->assign('bankData', $bankData);
    }

    public function handleEmailTemplate(View $view, string $paymentId)
    {
        // TODO: Implement handleEmailTemplate() method.
    }

    private function getCharge(string $paymentId)
    {
        return $this->heidelpayClient->fetchPayment($paymentId)->getChargeByIndex(0);
    }

    private function getBankData(Charge $charge)
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
