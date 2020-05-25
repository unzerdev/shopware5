<?php

declare(strict_types=1);

use HeidelPayment\Components\BookingMode;
use HeidelPayment\Components\PaymentHandler\Traits\CanAuthorize;
use HeidelPayment\Components\PaymentHandler\Traits\CanCharge;
use HeidelPayment\Components\PaymentHandler\Traits\CanRecur;
use HeidelPayment\Controllers\AbstractHeidelpayPaymentController;
use HeidelPayment\Services\PaymentVault\Struct\VaultedDeviceStruct;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\PaymentTypes\Paypal;

class Shopware_Controllers_Widgets_HeidelpayPaypal extends AbstractHeidelpayPaymentController
{
    use CanAuthorize;
    use CanCharge;
    use CanRecur;

    /** @var bool */
    protected $isAsync = true;

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
                    $this->getApiLogger()->getPluginLogger()->warning('PayPal is not chargeable for basket', [$this->paymentDataStruct->getBasket()->jsonSerialize()]);

                    $redirectUrl = $this->getHeidelpayErrorUrlFromSnippet('frontend/heidelpay/checkout/confirm', 'communicationError');
                }
            }
        } catch (HeidelpayApiException $ex) {
            $this->getApiLogger()->logException('Error while creating PayPal recurring payment', $ex);
            $redirectUrl = $this->getHeidelpayErrorUrl($ex->getClientMessage());
        } catch (RuntimeException $ex) {
            $redirectUrl = $this->getHeidelpayErrorUrl('Error while fetching payment');
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
        } catch (RuntimeException $ex) {
            $this->getApiLogger()->getPluginLogger()->error($ex->getMessage(), $ex);
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

            if (!$this->paymentType instanceof Paypal) {
                $this->paymentType = $this->heidelpayClient->createPaymentType(new Paypal());
            }

            $typeId      = $this->request->get('typeId');
            $bookingMode = $this->container->get('heidel_payment.services.config_reader')
                ->get('paypal_bookingmode');

            $redirectUrl = $this->activateRecurring($this->paymentDataStruct->getReturnUrl());

            if (($bookingMode === BookingMode::CHARGE_REGISTER || $bookingMode === BookingMode::AUTHORIZE_REGISTER) && $typeId === null) {
                $deviceVault = $this->container->get('heidel_payment.services.payment_device_vault');
                $userData    = $this->getUser();

                $deviceVault->saveDeviceToVault($this->paymentType, VaultedDeviceStruct::DEVICE_TYPE_PAYPAL, $userData['billingaddress'], $userData['shippingaddress']);

                $this->isAsync = false;
            }
        } catch (HeidelpayApiException $apiException) {
            $this->getApiLogger()->logException('Error while creating PayPal payment', $apiException);
        }

        if ($this->recurring && ($this->recurring->isSuccess() || $this->recurring->isPending())) {
            $this->view->assign('redirectUrl', $redirectUrl);

            return;
        }

        $this->getApiLogger()->getPluginLogger()->warning('Recurring could not be activated for basket', [$this->paymentDataStruct->getBasket()->jsonSerialize()]);
        $this->view->assign('redirectUrl',
            $this->getHeidelpayErrorUrlFromSnippet('frontend/heidelpay/checkout/confirm', 'recurringError')
        );
    }

    private function handleNormalPayment(): void
    {
        try {
            if (!$this->paymentType instanceof Paypal) {
                $this->paymentType = $this->heidelpayClient->createPaymentType(new Paypal());
            }

            $typeId      = $this->request->get('typeId');
            $bookingMode = $this->container->get('heidel_payment.services.config_reader')
                ->get('paypal_bookingmode');

            if ($bookingMode === BookingMode::CHARGE || $bookingMode === BookingMode::CHARGE_REGISTER) {
                $redirectUrl = $this->charge($this->paymentDataStruct->getReturnUrl());
            } elseif ($bookingMode === BookingMode::AUTHORIZE || $bookingMode === BookingMode::AUTHORIZE_REGISTER) {
                $redirectUrl = $this->authorize($this->paymentDataStruct->getReturnUrl());
            } else {
                $redirectUrl = $this->getHeidelpayErrorUrlFromSnippet('frontend/heidelpay/checkout/confirm', 'communicationError');
            }

            if (($bookingMode === BookingMode::CHARGE_REGISTER || $bookingMode === BookingMode::AUTHORIZE_REGISTER) && $typeId === null) {
                $deviceVault   = $this->container->get('heidel_payment.services.payment_device_vault');
                $userData      = $this->getUser();
                $this->isAsync = false;

                $deviceVault->saveDeviceToVault($this->paymentType, VaultedDeviceStruct::DEVICE_TYPE_PAYPAL, $userData['billingaddress'], $userData['shippingaddress']);
            }
        } catch (HeidelpayApiException $apiException) {
            $this->getApiLogger()->logException('Error while creating PayPal payment', $apiException);
            $redirectUrl = $this->getHeidelpayErrorUrl($apiException->getClientMessage());
        } catch (RuntimeException $runtimeException) {
            $redirectUrl = $this->getHeidelpayErrorUrl('Error while fetching payment');
        } finally {
            $this->view->assign('redirectUrl', $redirectUrl);
        }
    }
}
