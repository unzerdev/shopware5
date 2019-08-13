<?php

use HeidelPayment\Controllers\AbstractHeidelpayPaymentController;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\PaymentTypes\InvoiceGuaranteed as InvoiceGuaranteedPaymentType;

class Shopware_Controllers_Widgets_HeidelpayInvoiceGuaranteed extends AbstractHeidelpayPaymentController
{
    /** @var InvoiceGuaranteedPaymentType */
    protected $paymentType;

    public function createPaymentAction(): void
    {
        $this->paymentType = new InvoiceGuaranteedPaymentType();
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

            $this->getApiLogger()->logResponse('Created invoice guaranteed payment', $result);

            $this->redirect($result->getPayment()->getRedirectUrl() ?: $returnUrl);
        } catch (HeidelpayApiException $apiException) {
            $this->getApiLogger()->logException('Error while creating invoice guaranteed payment', $apiException);

            $this->redirect($this->getHeidelpayErrorUrl($apiException->getClientMessage()));
        }

        if (isset($result)) {
            $this->session->offsetSet('heidelPaymentId', $result->getPaymentId());
        }
    }
}
