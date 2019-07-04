<?php

use HeidelPayment\Controllers\AbstractHeidelpayPaymentController;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\PaymentTypes\SepaDirectDebitGuaranteed as SepaDirectDebitGuaranteedPaymentType;

class Shopware_Controllers_Widgets_HeidelpaySepaDirectDebitGuaranteed extends AbstractHeidelpayPaymentController
{
    /** @var SepaDirectDebitGuaranteedPaymentType */
    protected $paymentType;

    public function createPaymentAction(): void
    {
        $heidelBasket   = $this->getHeidelpayBasket();
        $heidelCustomer = $this->getHeidelpayCustomer();
        $heidelMetadata = $this->getHeidelpayMetadata();
        $returnUrl      = $this->getHeidelpayReturnUrl();

        try {
            $heidelCustomer = $this->heidelpayClient->createOrUpdateCustomer($heidelCustomer);

            $result = $this->paymentType->charge(
                $heidelBasket->getAmountTotal(),
                $heidelBasket->getCurrencyCode(),
                $returnUrl,
                $heidelCustomer,
                $heidelBasket->getOrderId(),
                $heidelMetadata,
                $heidelBasket
            );
        } catch (HeidelpayApiException $apiException) {
            $this->view->assign('redirectUrl', $this->getHeidelpayErrorUrl($apiException->getMerchantMessage()));
        }

        $this->view->assign('success', isset($result));

        if (isset($result)) {
            $this->session->offsetSet('heidelPaymentId', $result->getPaymentId());
            $this->view->assign('redirectUrl', $result->getPayment()->getRedirectUrl() ?: $returnUrl);
        }
    }
}
