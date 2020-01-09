<?php

declare(strict_types=1);

use HeidelPayment\Components\BookingMode;
use HeidelPayment\Controllers\AbstractHeidelpayPaymentController;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\Basket;
use heidelpayPHP\Resources\PaymentTypes\Paypal;
use heidelpayPHP\Resources\Recurring;
use heidelpayPHP\Resources\TransactionTypes\Charge;

class Shopware_Controllers_Widgets_HeidelpayPaypal extends AbstractHeidelpayPaymentController
{
    /** @var Paypal */
    protected $paymentType;

    public function createPaymentAction(): void
    {
        $this->paymentType = $this->heidelpayClient->createPaymentType(new Paypal());
        $this->paymentType->setParentResource($this->heidelpayClient);
        $this->session->offsetSet('PaymentTypeId', $this->paymentType->getId());

        if (!$this->paymentType) {
            $this->handleCommunicationError();

            return;
        }

        $returnUrl    = $this->getHeidelpayReturnUrl();
        $heidelBasket = $this->getHeidelpayBasket();
        $isRecurring  = $heidelBasket->getSpecialParams()['isAbo'] ?: false;

        if ($isRecurring) {
            $this->recurringPurchase($returnUrl);
        } else {
            $this->singlePurchase($heidelBasket, $returnUrl);
        }
    }

    private function recurringPurchase(string $returnUrl): void
    {
        /** @var Recurring $recurring */
        $recurring = $this->heidelpayClient->activateRecurringPayment(
            $this->paymentType->getId(),
            $this->getRecurringUrl()
        );

        if (!$recurring) {
//                TODO: throw error
            return;
        }

        if (empty($recurring->getRedirectUrl()) && $recurring->isSuccess()) {
            $this->redirect($returnUrl);
        } elseif (!empty($recurring->getRedirectUrl()) && $recurring->isPending()) {
            $this->redirect($recurring->getRedirectUrl());
        }
    }

    private function singlePurchase(Basket $heidelBasket, string $returnUrl): void
    {
        $heidelCustomer = $this->getHeidelpayB2cCustomer();
        $heidelMetadata = $this->getHeidelpayMetadata();
        $bookingMode    = $this->container->get('heidel_payment.services.config_reader')->get('paypal_bookingmode');

        try {
            $heidelCustomer = $this->heidelpayClient->createOrUpdateCustomer($heidelCustomer);

            if ($bookingMode === BookingMode::CHARGE) {
                $result = $this->paymentType->charge(
                    $heidelBasket->getAmountTotalGross(),
                    $heidelBasket->getCurrencyCode(),
                    $returnUrl,
                    $heidelCustomer,
                    $heidelBasket->getOrderId(),
                    $heidelMetadata,
                    $heidelBasket
                );
            } else {
                $result = $this->paymentType->authorize(
                    $heidelBasket->getAmountTotalGross(),
                    $heidelBasket->getCurrencyCode(),
                    $returnUrl,
                    $heidelCustomer,
                    $heidelBasket->getOrderId(),
                    $heidelMetadata,
                    $heidelBasket
                );
            }
        } catch (HeidelpayApiException $apiException) {
            $this->getApiLogger()->logException('Error while creating PayPal payment', $apiException);

            $this->redirect($this->getHeidelpayErrorUrl($apiException->getClientMessage()));
        }

        $this->session->offsetSet('heidelPaymentId', $result->getPaymentId());
        $this->redirect($result->getPayment()->getRedirectUrl());
    }

    private function getRecurringUrl()
    {
        return $this->get('router')->assemble([
            'controller' => 'HeidelpayRecurringPaypal',
            'action'     => 'check',
        ]);
    }
}
