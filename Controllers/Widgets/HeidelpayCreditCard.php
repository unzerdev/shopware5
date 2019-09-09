<?php

use HeidelPayment\Components\BookingMode;
use HeidelPayment\Controllers\AbstractHeidelpayPaymentController;
use HeidelPayment\Services\PaymentVault\Struct\VaultedDeviceStruct;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\PaymentTypes\Card as CreditCardType;

class Shopware_Controllers_Widgets_HeidelpayCreditCard extends AbstractHeidelpayPaymentController
{
    /** @var CreditCardType */
    protected $paymentType;

    public function createPaymentAction()
    {
        $this->Front()->Plugins()->Json()->setRenderer();

        $bookingMode = $this->container->get('heidel_payment.services.config_reader')->get('credit_card_bookingmode');

        $heidelBasket   = $this->getHeidelpayBasket();
        $heidelMetadata = $this->getHeidelpayMetadata();
        $returnUrl      = $this->getHeidelpayReturnUrl();
        $typeId         = $this->request->get('typeId');

        try {
            if ($bookingMode === BookingMode::CHARGE || $bookingMode === BookingMode::CHARGE_REGISTER) {
                $result = $this->paymentType->charge(
                    $heidelBasket->getAmountTotalGross(),
                    $heidelBasket->getCurrencyCode(),
                    $returnUrl,
                    null,
                    $heidelBasket->getOrderId(),
                    $heidelMetadata,
                    $heidelBasket,
                    true
                );
            } else {
                $result = $this->paymentType->authorize(
                    $heidelBasket->getAmountTotalGross(),
                    $heidelBasket->getCurrencyCode(),
                    $returnUrl,
                    null,
                    $heidelBasket->getOrderId(),
                    $heidelMetadata,
                    $heidelBasket,
                    true
                );
            }

            if (($bookingMode === BookingMode::CHARGE_REGISTER || $bookingMode === BookingMode::AUTHORIZE_REGISTER) && $typeId === null) {
                $deviceVault = $this->container->get('heidel_payment.services.payment_device_vault');
                $userData    = $this->getUser();

                $deviceVault->saveDeviceToVault($this->paymentType, VaultedDeviceStruct::DEVICE_TYPE_CARD, $userData['billingaddress'], $userData['shippingaddress']);
            }

            $this->getApiLogger()->logResponse('Created credit card payment', $result);
        } catch (HeidelpayApiException $apiException) {
            $this->view->assign('redirectUrl', $this->getHeidelpayErrorUrl($apiException->getClientMessage()));

            $this->getApiLogger()->logException('Error while creating credit card payment', $apiException);
        }

        $this->view->assign('success', isset($result));

        if (isset($result)) {
            $this->session->offsetSet('heidelPaymentId', $result->getPaymentId());
            $this->view->assign('redirectUrl', $result->getPayment()->getRedirectUrl() ?: $returnUrl);
        }
    }
}
