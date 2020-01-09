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
            /** @var Recurring $result */
            $result = $this->heidelpayClient->activateRecurringPayment($this->paymentType, $returnUrl);

            if (!$recurring) {
//                TODO: throw error
                return;
            }

//            TODO: handle charge
        } else {
            $result = $this->singlePurchase($heidelBasket, $heidelMetadata, $returnUrl);
        }

        /** @var AbstractTransactionType $result */
        $this->view->assign('success', isset($result));

        if (isset($result)) {
            $this->session->offsetSet('heidelPaymentId', $paymentId);
            $this->view->assign('redirectUrl', $result->getPayment()->getRedirectUrl() ?: $returnUrl);
        }
    }

    /**
     * @return null|Authorization|Charge
     */
    private function singlePurchase(Basket $heidelBasket, Metadata $heidelMetadata, string $returnUrl): ?mixed
    {
        $bookingMode = $this->container->get('heidel_payment.services.config_reader')->get('credit_card_bookingmode');
        $typeId      = $this->request->get('typeId');

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
        } catch (HeidelpayApiException $apiException) {
            $this->view->assign('redirectUrl', $this->getHeidelpayErrorUrl($apiException->getClientMessage()));

            $this->getApiLogger()->logException('Error while creating credit card payment', $apiException);
        }

        return $result ?: null;
    }
}
