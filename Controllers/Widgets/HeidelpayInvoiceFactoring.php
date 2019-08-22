<?php

use HeidelPayment\Controllers\AbstractHeidelpayPaymentController;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\PaymentTypes\InvoiceFactoring as InvoiceFactoringPaymentType;

class Shopware_Controllers_Widgets_HeidelpayInvoiceFactoring extends AbstractHeidelpayPaymentController
{
    /** @var InvoiceFactoringPaymentType */
    protected $paymentType;

    public function createPaymentAction(): void
    {
        $this->paymentType = new InvoiceFactoringPaymentType();
        $this->paymentType->setParentResource($this->heidelpayClient);

        $heidelBasket   = $this->getHeidelpayBasket();
        $heidelCustomer = $this->getHeidelpayCustomer();
        $heidelMetadata = $this->getHeidelpayMetadata();
        $returnUrl      = $this->getHeidelpayReturnUrl();

        try {
            $heidelCustomer = $this->heidelpayClient->createOrUpdateCustomer($heidelCustomer);
            $result         = $this->paymentType->charge(
                $heidelBasket->getAmountTotalGross(),
                $heidelBasket->getCurrencyCode(),
                $returnUrl,
                $heidelCustomer,
                $heidelBasket->getOrderId(),
                $heidelMetadata,
                $heidelBasket
            );
            $this->getApiLogger()->logResponse('Created invoice factoring payment', $result);

            $this->redirect($result->getPayment()->getRedirectUrl() ?: $returnUrl);
        } catch (HeidelpayApiException $apiException) {
            $this->getApiLogger()->logException('Error while creating invoice factoring payment', $apiException);

            $this->redirect($this->getHeidelpayErrorUrl($apiException->getClientMessage()));
        }

        if (isset($result)) {
            $this->session->offsetSet('heidelPaymentId', $result->getPaymentId());
        }
    }
}
