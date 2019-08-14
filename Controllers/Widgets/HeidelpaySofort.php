<?php

use HeidelPayment\Controllers\AbstractHeidelpayPaymentController;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\PaymentTypes\Sofort;

class Shopware_Controllers_Widgets_HeidelpaySofort extends AbstractHeidelpayPaymentController
{
    /** @var Sofort */
    protected $paymentType;

    public function createPaymentAction(): void
    {
        $this->paymentType = new Sofort();
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

            $this->getApiLogger()->logResponse('Created SOFORT payment', $result);

            $this->redirect($result->getPayment()->getRedirectUrl());
        } catch (HeidelpayApiException $apiException) {
            $this->getApiLogger()->logException('Error while creating SOFORT payment', $apiException);

            $this->redirect($this->getHeidelpayErrorUrl($apiException->getClientMessage()));
        }

        if (isset($result)) {
            $this->session->offsetSet('heidelPaymentId', $result->getPaymentId());
        }
    }
}
