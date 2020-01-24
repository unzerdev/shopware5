<?php

declare(strict_types=1);

use HeidelPayment\Components\BookingMode;
use HeidelPayment\Components\Payment\HeidelPaymentStruct\HeidelPaymentStruct;
use HeidelPayment\Controllers\AbstractRecurringPaymentController;
use HeidelPayment\Services\PaymentVault\Struct\VaultedDeviceStruct;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\Basket;
use heidelpayPHP\Resources\Metadata;
use heidelpayPHP\Resources\PaymentTypes\Card;
use heidelpayPHP\Resources\Recurring;
use heidelpayPHP\Resources\TransactionTypes\AbstractTransactionType;
use heidelpayPHP\Resources\TransactionTypes\Charge;

class Shopware_Controllers_Widgets_HeidelpayCreditCard extends AbstractRecurringPaymentController
{
    /** @var Card */
    protected $paymentType;

    /** @var bool */
    protected $isAsync = true;

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
            $recurring = $this->recurringPurchase($returnUrl);

            if (!$recurring) {
                $this->getApiLogger()->getPluginLogger()->warning(
                    'Recurring could not be activated for basket',
                    [$heidelBasket->jsonSerialize()]
                );

                $this->view->assign(
                    [
                        'success'     => false,
                        'redirectUrl' => $this->getHeidelpayErrorUrlFromSnippet(
                            'frontend/heidelpay/checkout/confirm',
                            'recurringError'
                        ),
                    ]
                );

                return;
            }
        }

        $bookingMode = $this->container->get('heidel_payment.services.config_reader')->get('credit_card_bookingmode');

        $result = $this->makePurchase($heidelBasket, $heidelMetadata, $returnUrl, $bookingMode);

        if (!$isRecurring && $this->isSaveToDeviceVaultConfigured($bookingMode)) {
            $this->saveToDeviceVault();
        }

        /** @var AbstractTransactionType $result */
        $this->view->assign('success', isset($result));

        if (isset($result)) {
            $this->session->offsetSet('heidelPaymentId', $result->getPaymentId());
            $completeUrl = $result->getPayment()->getRedirectUrl() ?: $returnUrl;
            $this->view->assign('redirectUrl', $completeUrl);
        }
    }

    protected function handleRecurringPayment(HeidelPaymentStruct $paymentStruct): AbstractTransactionType
    {
        $bookingMode = $this->container->get('heidel_payment.services.config_reader')->get('credit_card_bookingmode');

        if ($bookingMode === BookingMode::CHARGE || $bookingMode === BookingMode::CHARGE_REGISTER) {
            return $this->paymentType->charge(
                $paymentStruct->getAmount(),
                $paymentStruct->getCurrency(),
                $paymentStruct->getReturnUrl(),
                $paymentStruct->getCustomer(),
                $paymentStruct->getOrderId(),
                $paymentStruct->getMetadata(),
                null,
                null,
                null,
                (string) $paymentStruct->getPaymentReference()
            );
        }

        return $this->paymentType->authorize(
            $paymentStruct->getAmount(),
            $paymentStruct->getCurrency(),
            $paymentStruct->getReturnUrl(),
            $paymentStruct->getCustomer(),
            $paymentStruct->getOrderId(),
            $paymentStruct->getMetadata(),
            null,
            null,
            null,
            (string) $paymentStruct->getPaymentReference()
        );
    }

    private function makePurchase(
        Basket $heidelBasket,
        Metadata $heidelMetadata,
        string $returnUrl,
        string $bookingMode
    ): ?AbstractTransactionType {
        $result = null;

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
        } catch (HeidelpayApiException $apiException) {
            $this->view->assign('redirectUrl', $this->getHeidelpayErrorUrl($apiException->getClientMessage()));

            $this->getApiLogger()->logException('Error while creating credit card payment', $apiException);
        }

        return $result ?: null;
    }

    private function recurringPurchase(string $returnUrl): ?Recurring
    {
        try {
            /** @var Recurring $recurring */
            return $this->paymentType->activateRecurring($returnUrl);
        } catch (HeidelpayApiException $ex) {
            $this->getApiLogger()->logException('Error in recurring activation', $ex);
        }

        return null;
    }

    private function isSaveToDeviceVaultConfigured(string $bookingMode): bool
    {
        $typeId = $this->request->get('typeId');

        return ($bookingMode === BookingMode::CHARGE_REGISTER || $bookingMode === BookingMode::AUTHORIZE_REGISTER) && $typeId === null;
    }

    private function saveToDeviceVault(): void
    {
        $userData    = $this->getUser();
        $deviceVault = $this->container->get('heidel_payment.services.payment_device_vault');

        $deviceVault->saveDeviceToVault($this->paymentType, VaultedDeviceStruct::DEVICE_TYPE_CARD, $userData['billingaddress'], $userData['shippingaddress']);
    }
}
