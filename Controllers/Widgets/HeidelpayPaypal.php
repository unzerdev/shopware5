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
            parent::pay();
            $this->paymentType = $this->heidelpayClient->fetchPaymentType($paymentTypeId);

            if ($this->paymentType instanceof Paypal && $this->paymentType->isRecurring()) {
                $redirectUrl = $this->charge($this->paymentDataStruct->getReturnUrl());

                if (!$redirectUrl) {
                    $this->getApiLogger()->getPluginLogger()->warning('PayPal is not chargeable for basket', [$heidelBasket->jsonSerialize()]);

                    $redirectUrl = $errorUrl = $this->getHeidelpayErrorUrlFromSnippet(
                        'frontend/heidelpay/checkout/confirm',
                        'communicationError'
                    );
                }
            }
        } catch (HeidelpayApiException $apiException) {
            $this->getApiLogger()->logException('Error while creating PayPal recurring payment', $apiException);
            $redirectUrl = $this->getHeidelpayErrorUrl($apiException->getClientMessage());
        } finally {
            $this->view->assign('redirectUrl', $redirectUrl);
        }
    }

    public function chargeRecurringPaymentAction(): void
    {
        parent::recurring();

        if (!$this->paymentDataStruct) {
            $this->getApiLogger()->getPluginLogger()->error('The payment data struct could not be created');
            $this->view->assign('success', false);

            return;
        }

        try {
            $this->charge($this->paymentDataStruct->getReturnUrl());
            $orderNumber = $this->createRecurringOrder();
        } catch (HeidelpayApiException $ex) {
            $this->getApiLogger()->logException($ex->getMessage(), $ex);
        } finally {
            $this->view->assign([
                'success' => !empty($orderNumber),
                'data'    => [
                    'orderNumber' => $orderNumber ?: '',
                ],
            ]);
        }
    }

    private function handleRecurringPayment(): void
    {
        try {
            $this->paymentDataStruct->setReturnUrl($this->getInitialRecurringUrl());
            $this->paymentType = $this->heidelpayClient->createPaymentType(new Paypal());

            $redirectUrl = $this->activateRecurring($this->paymentDataStruct->getReturnUrl());
        } catch (HeidelpayApiException $apiException) {
            $this->getApiLogger()->logException('Error while creating PayPal payment', $apiException);
        }

        if ($this->recurring && ($this->recurring->isSuccess() || $this->recurring->isPending())) {
            $this->view->assign('redirectUrl', $redirectUrl);

            return;
        }

        $this->getApiLogger()->getPluginLogger()->warning('Recurring could not be activated for basket', [$this->paymentDataStruct->getBasket()->jsonSerialize()]);
        $this->view->assign('redirectUrl',
            $this->getHeidelpayErrorUrlFromSnippet(
                'frontend/heidelpay/checkout/confirm',
                'recurringError')
        );
    }

    private function handleNormalPayment(): void
    {
        try {
            $this->paymentType = $this->heidelpayClient->createPaymentType(new Paypal());
            $redirectUrl       = $this->charge($this->paymentDataStruct->getReturnUrl());
        } catch (HeidelpayApiException $apiException) {
            $this->getApiLogger()->logException('Error while creating PayPal payment', $apiException);
            $redirectUrl = $this->getHeidelpayErrorUrl($apiException->getClientMessage());
        } finally {
            $this->view->assign('redirectUrl', $redirectUrl);
        }
    }
}
