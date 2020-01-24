<?php

declare(strict_types=1);

use HeidelPayment\Components\BookingMode;
use HeidelPayment\Components\Payment\HeidelPaymentStruct\HeidelPaymentStruct;
use HeidelPayment\Controllers\AbstractRecurringPaymentController;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\Basket;
use heidelpayPHP\Resources\PaymentTypes\Paypal;
use heidelpayPHP\Resources\Recurring;
use heidelpayPHP\Resources\TransactionTypes\AbstractTransactionType;
use heidelpayPHP\Resources\TransactionTypes\Charge;

class Shopware_Controllers_Widgets_HeidelpayPaypal extends AbstractRecurringPaymentController
{
    /** @var Paypal */
    protected $paymentType;

    public function createPaymentAction(): void
    {
        $this->paymentType = $this->heidelpayClient->createPaymentType(new Paypal());
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

    public function recurringFinishedAction(): void
    {
        $session       = $this->container->get('session');
        $paymentTypeId = $session->offsetGet('PaymentTypeId');

        try {
            $bookingMode       = $this->container->get('heidel_payment.services.config_reader')->get('paypal_bookingmode');
            $heidelpayClient   = $this->container->get('heidel_payment.services.api_client')->getHeidelpayClient();
            $this->paymentType = $heidelpayClient->fetchPaymentType($paymentTypeId);

            if ($this->paymentType instanceof Paypal && $this->paymentType->isRecurring()) {
                $heidelBasket   = $this->getHeidelpayBasket();
                $heidelCustomer = $this->heidelpayClient->createOrUpdateCustomer($this->getHeidelpayB2cCustomer());

                if ($bookingMode === BookingMode::CHARGE) {
                    $result = $this->paymentType->charge(
                        $heidelBasket->getAmountTotalGross(),
                        $heidelBasket->getCurrencyCode(),
                        $this->getChargeRecurringUrl(),
                        $heidelCustomer,
                        $heidelBasket->getOrderId(),
                        $heidelMetadata,
                        $heidelBasket
                    );
                } else {
                    $result = $this->paymentType->authorize(
                        $heidelBasket->getAmountTotalGross(),
                        $heidelBasket->getCurrencyCode(),
                        $this->getHeidelpayReturnUrl(),
                        $heidelCustomer,
                        $heidelBasket->getOrderId(),
                        $heidelMetadata,
                        $heidelBasket
                    );
                }

                if (!$result) {
                    $this->getApiLogger()->getPluginLogger()->warning('PayPal is not chargeable for basket', [$heidelBasket->jsonSerialize()]);
                    $this->handleCommunicationError();
                }

                $this->session->offsetSet('heidelPaymentId', $result->getPaymentId());
                $this->redirect($result->getReturnUrl());
            }
        } catch (HeidelpayApiException $apiException) {
            $this->getApiLogger()->logException('Error while creating PayPal recurring payment', $apiException);

            $this->redirect($this->getHeidelpayErrorUrl($apiException->getClientMessage()));
        }
    }

    protected function handleRecurringPayment(HeidelPaymentStruct $paymentStruct): ?AbstractTransactionType
    {
        $bookingMode = $this->container->get('heidel_payment.services.config_reader')->get('paypal_bookingmode');

        if ($bookingMode === BookingMode::CHARGE) {
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
                $paymentStruct->getPaymentReference()
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
            $paymentStruct->getPaymentReference()
        );
    }

    private function recurringPurchase(string $returnUrl): void
    {
        try {
            /** @var Recurring $recurring */
            $recurring = $this->heidelpayClient->activateRecurringPayment(
                $this->paymentType->getId(),
                $this->getinitialRecurringUrl()
            );

            if (!$recurring) {
                $this->getApiLogger()->getPluginLogger()->warning('Recurring could not be activated for basket', $heidelBasket);

                $this->view->assign([
                    'success'     => false,
                    'redirectUrl' => $this->getHeidelpayErrorUrlFromSnippet(
                        'frontend/heidelpay/checkout/confirm',
                        'recurringError'),
                ]);

                return;
            }

            if (empty($recurring->getRedirectUrl()) && $recurring->isSuccess()) {
                $this->redirect($returnUrl);
            } elseif (!empty($recurring->getRedirectUrl()) && $recurring->isPending()) {
                $this->redirect($recurring->getRedirectUrl());
            }
        } catch (HeidelpayApiException $ex) {
            $this->getApiLogger()->logException($ex->getMessage(), $ex);

            $this->redirect($this->getHeidelpayErrorUrl($ex->getMessage()));
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

        if (isset($result)) {
            $this->session->offsetSet('heidelPaymentId', $result->getPaymentId());
            $this->redirect($result->getPayment()->getRedirectUrl());
        }
    }
}
