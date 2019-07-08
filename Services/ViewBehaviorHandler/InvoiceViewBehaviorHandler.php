<?php

namespace HeidelPayment\Services\ViewBehaviorHandler;

use Enlight_View_Default as View;
use HeidelPayment\Services\Heidelpay\HeidelpayClientServiceInterface;
use heidelpayPHP\Heidelpay;
use heidelpayPHP\Resources\TransactionTypes\Charge;

class InvoiceViewBehaviorHandler implements ViewBehaviorHandlerInterface
{
    /** @var View */
    private $view;

    /** @var string */
    private $paymentId;

    /** @var string */
    private $action;

    /** @var Heidelpay */
    private $heidelpayClient;

    public function __construct(HeidelpayClientServiceInterface $heidelpayClientService)
    {
        $this->heidelpayClient = $heidelpayClientService->getHeidelpayClient();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(View $view, string $paymentId, string $action)
    {
        $this->view      = $view;
        $this->paymentId = $paymentId;
        $this->action    = $action;

        switch ($action) {
            case ViewBehaviorHandlerInterface::ACTION_FINISH:
                $this->handleFinishAction();
            break;
            case ViewBehaviorHandlerInterface::ACTION_INVOICE:
                $this->handleInvoiceAction();
                break;
            case ViewBehaviorHandlerInterface::ACTION_EMAIL:
                $this->handleEmailAction();
                break;
        }
    }

    private function handleFinishAction()
    {
        $this->view->loadTemplate('frontend/heidelpay/invoice/finish.tpl');

        /** @var Charge $paymentType */
        $charge = $this->heidelpayClient->fetchPayment($this->paymentId)->getChargeByIndex(0);

        $bankData = [
            'iban'       => $charge->getIban(),
            'bic'        => $charge->getBic(),
            'holder'     => $charge->getHolder(),
            'amount'     => $charge->getAmount(),
            'currency'   => $charge->getCurrency(),
            'descriptor' => $charge->getDescriptor(),
        ];

        $this->view->assign('bankData', $bankData);
    }

    private function handleInvoiceAction()
    {
    }

    private function handleEmailAction()
    {
    }

    private function getBankData()
    {
        //TODO: Fetch payment and obtain the bank information
    }
}
