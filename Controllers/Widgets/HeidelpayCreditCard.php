<?php

declare(strict_types=1);

use HeidelPayment\Components\BookingMode;
use HeidelPayment\Controllers\AbstractHeidelpayPaymentController;
use HeidelPayment\Services\PaymentVault\Struct\VaultedDeviceStruct;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\Basket;
use heidelpayPHP\Resources\Metadata;
use heidelpayPHP\Resources\PaymentTypes\Card;
use heidelpayPHP\Resources\Recurring;
use heidelpayPHP\Resources\TransactionTypes\AbstractTransactionType;
use heidelpayPHP\Resources\TransactionTypes\Authorization;
use heidelpayPHP\Resources\TransactionTypes\Charge;

class Shopware_Controllers_Widgets_HeidelpayCreditCard extends AbstractHeidelpayPaymentController
{
    /** @var Card */
    protected $paymentType;

    /** @var bool */
    protected $isAsync = false;

    public function createPaymentAction(): void
    {
        if (!$this->paymentType) {
            $this->handleCommunicationError();

            return;
        }

        $heidelBasket   = $this->getHeidelpayBasket();
        $heidelMetadata = $this->getHeidelpayMetadata();
        $returnUrl      = $this->getHeidelpayReturnUrl();
        $isRecurring    = $heidelBasket->getSpecialParams()['isAbo'] ?: false;

        if ($isRecurring) {
            $result = $this->recurringPurchase($heidelBasket, $heidelMetadata, $returnUrl);
        } else {
            $result = $this->singlePurchase($heidelBasket, $heidelMetadata, $returnUrl);
        }

        /** @var AbstractTransactionType $result */
        $this->view->assign('success', isset($result));

        if (isset($result)) {
            $this->session->offsetSet('heidelPaymentId', $result->getPaymentId());
            $completeUrl = $result->getPayment()->getRedirectUrl() ?: $returnUrl;
            $this->view->assign('redirectUrl', $completeUrl);
        }
    }

    /**
     * @return null|Authorization|Charge
     */
    private function singlePurchase(Basket $heidelBasket, Metadata $heidelMetadata, string $returnUrl)
    {
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

//            if ($this->isSaveToDeviceVaultConfigured()) {
//                $this->saveToDeviceVault();
//            }
        } catch (HeidelpayApiException $apiException) {
            $this->view->assign('redirectUrl', $this->getHeidelpayErrorUrl($apiException->getClientMessage()));

            $this->getApiLogger()->logException('Error while creating credit card payment', $apiException);
        }

        return $result ?: null;
    }

    private function recurringPurchase(Basket $heidelBasket, Metadata $heidelMetadata, string $returnUrl): ?Charge
    {
        try {
            /** @var Recurring $recurring */
            $recurring = $this->heidelpayClient->activateRecurringPayment($this->paymentType, $returnUrl);

            if (!$recurring) {
//                TODO: error handling
                return null;
            }

//            dump($heidelBasket);
//            dump($heidelMetadata);
//            dump($returnUrl);
//            die();

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

            dump($result->getPayment());

//            dump($result);

            return $result;
        } catch (HeidelpayApiException $ex) {
//            TODO: error handling
        }

        return null;
    }

    private function isSaveToDeviceVaultConfigured(): bool
    {
        $typeId      = $this->request->get('typeId');
        $bookingMode = $this->container->get('heidel_payment.services.config_reader')->get('credit_card_bookingmode');

        if (($bookingMode === BookingMode::CHARGE_REGISTER || $bookingMode === BookingMode::AUTHORIZE_REGISTER) && $typeId === null) {
            return true;
        }

        return false;
    }

    private function saveToDeviceVault(): void
    {
        $userData    = $this->getUser();
        $deviceVault = $this->container->get('heidel_payment.services.payment_device_vault');

        $deviceVault->saveDeviceToVault($this->paymentType, VaultedDeviceStruct::DEVICE_TYPE_CARD, $userData['billingaddress'], $userData['shippingaddress']);
    }
}
