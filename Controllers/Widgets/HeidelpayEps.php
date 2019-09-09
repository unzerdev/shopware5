<?php

use HeidelPayment\Controllers\AbstractHeidelpayPaymentController;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\PaymentTypes\EPS as EpsType;

class Shopware_Controllers_Widgets_HeidelpayEps extends AbstractHeidelpayPaymentController
{
    /** @var EpsType */
    protected $paymentType;

    public function createPaymentAction()
    {
        $heidelBasket   = $this->getHeidelpayBasket();
        $heidelMetadata = $this->getHeidelpayMetadata();
        $heidelCustomer = $this->getHeidelpayB2cCustomer();
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

            $this->getApiLogger()->logResponse('Created EPS payment', $result);
        } catch (HeidelpayApiException $apiException) {
            $this->getApiLogger()->logException('Error while creating EPS payment', $apiException);

            $this->view->assign('redirectUrl', $this->getHeidelpayErrorUrl($apiException->getClientMessage()));
        }

        $this->view->assign('success', isset($result));

        if (isset($result)) {
            $this->session->offsetSet('heidelPaymentId', $result->getPaymentId());
            $this->view->assign('redirectUrl', $result->getPayment()->getRedirectUrl() ?: $returnUrl);
        }
    }
}
