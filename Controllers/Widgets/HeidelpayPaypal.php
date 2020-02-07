<?php

declare(strict_types=1);

use HeidelPayment\Components\PaymentHandler\Traits\CanCharge;
use HeidelPayment\Components\PaymentHandler\Traits\CanRecurring;
use HeidelPayment\Controllers\AbstractHeidelpayPaymentController;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\PaymentTypes\Paypal;

class Shopware_Controllers_Widgets_HeidelpayPaypal extends AbstractHeidelpayPaymentController
{
    use CanCharge;
    use CanRecurring;

    public function createPaymentAction(): void
    {
        parent::pay();

        if ($this->paymentDataStruct->isRecurring()) {
            $this->handleRecurringPayment();
        } else {
            $this->handleNormalPayment();
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
                $heidelCustomer = $this->heidelpayClient->createOrUpdateCustomer($this->getHeidelpayCustomer());

                $result = $this->charge(
                    $heidelBasket->getAmountTotalGross(),
                    $heidelBasket->getCurrencyCode(),
                    $this->getChargeRecurringUrl(),
                    $heidelCustomer,
                    $heidelBasket->getOrderId(),
                    $heidelMetadata,
                    $heidelBasket
                );

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

    public function chargeRecurringPaymentAction(): void
    {
        parent::recurring();

        if (!$this->view->getAssign('success')) {
            return;
        }

        $this->handleNormalPayment();

        try {
            $orderNumber = $this->createRecurringOrder();
        } catch (HeidelpayApiException $ex) {
            $this->getApiLogger()->logException($ex->getMessage(), $ex);
        } finally {
            $this->view->assign([
                'success' => isset($orderNumber),
                'data'    => [
                    'orderNumber' => $orderNumber ?: '',
                ],
            ]);
        }
    }

    private function handleRecurringPayment(): void
    {
        $this->activateRecurring($this->paymentDataStruct->getReturnUrl());

        if (!$this->recurring) {
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
    }

    private function handleNormalPayment(): void
    {
        try {
            $this->paymentType = $this->heidelpayClient->createPaymentType(new Paypal());
            $resultUrl         = $this->charge($this->paymentDataStruct->getReturnUrl());
        } catch (HeidelpayApiException $apiException) {
            $this->getApiLogger()->logException('Error while creating PayPal payment', $apiException);
            $resultUrl = $this->getHeidelpayErrorUrl($apiException->getClientMessage());
        } finally {
            $this->view->assign([
                'success'     => isset($this->payment),
                'redirectUrl' => $resultUrl,
            ]);
        }
    }
}
