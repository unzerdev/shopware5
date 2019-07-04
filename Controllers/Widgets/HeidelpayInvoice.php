<?php

use HeidelPayment\Controllers\AbstractHeidelpayPaymentController;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\PaymentTypes\Invoice as Invoice;

class Shopware_Controllers_Widgets_HeidelpayInvoice extends AbstractHeidelpayPaymentController
{
    /** @var \heidelpayPHP\Resources\PaymentTypes\Invoice */
    protected $paymentType;

    public function createPaymentAction(): void
    {
        $this->paymentType = new Invoice();
        $this->paymentType->setParentResource($this->heidelpayClient);

        $heidelBasket   = $this->getHeidelpayBasket();
        $heidelCustomer = $this->getHeidelpayCustomer();
        $heidelMetadata = $this->getHeidelpayMetadata();
        $returnUrl      = $this->getHeidelpayReturnUrl();

        try {
            $heidelCustomer = $this->heidelpayClient->createOrUpdateCustomer($heidelCustomer);
            $result         = $this->paymentType->charge(
                $heidelBasket->getAmountTotal(),
                $heidelBasket->getCurrencyCode(),
                $returnUrl,
                $heidelCustomer,
                $heidelBasket->getOrderId(),
                $heidelMetadata,
                $heidelBasket
            );
        } catch (HeidelpayApiException $apiException) {
            $this->redirect($this->getHeidelpayErrorUrl($apiException->getClientMessage()));
        }

        if (isset($result)) {
            $this->session->offsetSet('heidelPaymentId', $result->getPaymentId());
        }
    }
}
