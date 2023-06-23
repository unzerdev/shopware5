<?php

declare(strict_types=1);

use UnzerPayment\Components\BookingMode;
use UnzerPayment\Components\PaymentHandler\Traits\CanAuthorize;
use UnzerPayment\Components\PaymentHandler\Traits\CanCharge;
use UnzerPayment\Components\PaymentHandler\Traits\CanRecur;
use UnzerPayment\Controllers\AbstractUnzerPaymentController;
use UnzerPayment\Services\PaymentVault\Struct\VaultedDeviceStruct;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\PaymentTypes\Paypal;

/**
 * @property Paypal $paymentType
 */
class Shopware_Controllers_Widgets_UnzerPaymentPaypal extends AbstractUnzerPaymentController
{
    use CanAuthorize;
    use CanCharge;
    use CanRecur;

    /** @var string */
    protected $bookingMode;

    /** @var bool */
    protected $isRedirectPayment = true;

    public function preDispatch(): void
    {
        parent::preDispatch();

        $this->bookingMode = $this->container->get('unzer_payment.services.config_reader')->get('paypal_bookingmode');
    }

    public function createPaymentAction(): void
    {
        parent::pay();

        if (!$this->paymentType) {
            $this->paymentType = $this->unzerPaymentClient->createPaymentType(new Paypal());

            if ($this->paymentDataStruct->isRecurring() ||
                in_array($this->bookingMode, [BookingMode::CHARGE_REGISTER, BookingMode::AUTHORIZE_REGISTER])) {
                $this->handleRecurringPayment();

                return;
            }
        }

        if ($this->paymentType->isRecurring()) {
            $this->recurringFinishedAction();
        } else {
            $this->handleNormalPayment();
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
        } catch (UnzerApiException $ex) {
            $this->getApiLogger()->logException($ex->getMessage(), $ex);
        } catch (RuntimeException $ex) {
            $this->getApiLogger()->getPluginLogger()->error($ex->getMessage(), $ex);
        } finally {
            $this->view->assign([
                'success' => !empty($orderNumber),
                'data'    => [
                    'orderNumber' => $orderNumber ?? '',
                ],
            ]);
        }
    }

    protected function recurringFinishedAction(): void
    {
        try {
            parent::pay();

            if (!$this->paymentType) {
                $session           = $this->container->get('session');
                $paymentTypeId     = $session->offsetGet('PaymentTypeId');
                $this->paymentType = $this->unzerPaymentClient->fetchPaymentType($paymentTypeId);
            }

            if (!$this->paymentType->isRecurring()) {
                $this->getApiLogger()->getPluginLogger()->warning('Recurring could not be activated for basket', [$this->paymentDataStruct->getBasket()->jsonSerialize()]);
                $redirectUrl = $this->getUnzerPaymentErrorUrlFromSnippet('recurringError');
            }

            if (in_array($this->bookingMode, [BookingMode::CHARGE, BookingMode::CHARGE_REGISTER])) {
                $redirectUrl = $this->charge($this->paymentDataStruct->getReturnUrl());
            } elseif (in_array($this->bookingMode, [BookingMode::AUTHORIZE, BookingMode::AUTHORIZE_REGISTER])) {
                $redirectUrl = $this->authorize($this->paymentDataStruct->getReturnUrl());
            }

            $this->saveToDeviceVault();
        } catch (UnzerApiException $ex) {
            $this->getApiLogger()->logException('Error while creating PayPal recurring payment', $ex);
            $redirectUrl = $this->getUnzerPaymentErrorUrl($ex->getClientMessage());
        } catch (RuntimeException $ex) {
            $redirectUrl = $this->getUnzerPaymentErrorUrlFromSnippet('communicationError');
        } finally {
            $redirectUrl = $this->handleEmptyRedirectUrl(!empty($redirectUrl) ? $redirectUrl : '', 'Paypal recurring');

            $this->view->assign('redirectUrl', $redirectUrl);
        }
    }

    private function handleRecurringPayment(): void
    {
        $redirectUrl = $this->paymentDataStruct->getReturnUrl();

        try {
            $this->paymentDataStruct->setReturnUrl($this->getInitialRecurringUrl());

            $redirectUrl = $this->activateRecurring($this->paymentDataStruct->getReturnUrl());
        } catch (UnzerApiException $apiException) {
            if ((string) $apiException->getCode() !== AbstractUnzerPaymentController::ALREADY_RECURRING_ERROR_CODE) {
                $this->getApiLogger()->logException('Error while creating PayPal payment', $apiException);

                $redirectUrl = $this->getUnzerPaymentErrorUrlFromSnippet('communicationError');
            }
        }

        $this->view->assign('redirectUrl', $redirectUrl);
    }

    private function handleNormalPayment(): void
    {
        try {
            if ($this->bookingMode === BookingMode::CHARGE) {
                $redirectUrl = $this->charge($this->paymentDataStruct->getReturnUrl());
            } elseif ($this->bookingMode === BookingMode::AUTHORIZE) {
                $redirectUrl = $this->authorize($this->paymentDataStruct->getReturnUrl());
            } else {
                $redirectUrl = $this->getUnzerPaymentErrorUrlFromSnippet('communicationError');
            }
        } catch (UnzerApiException $apiException) {
            $this->getApiLogger()->logException('Error while creating PayPal payment', $apiException);
            $redirectUrl = $this->getUnzerPaymentErrorUrl($apiException->getClientMessage());
        } catch (RuntimeException $runtimeException) {
            $redirectUrl = $this->getUnzerPaymentErrorUrlFromSnippet('communicationError');
        } finally {
            $redirectUrl = $this->handleEmptyRedirectUrl(!empty($redirectUrl) ? $redirectUrl : '', 'Paypal normal');

            $this->view->assign('redirectUrl', $redirectUrl);
        }
    }

    private function saveToDeviceVault(): void
    {
        if (in_array($this->bookingMode, [BookingMode::CHARGE_REGISTER, BookingMode::AUTHORIZE_REGISTER])) {
            $deviceVault = $this->container->get('unzer_payment.services.payment_device_vault');
            $userData    = $this->getUser();

            $deviceVault->saveDeviceToVault(
                $this->paymentType,
                VaultedDeviceStruct::DEVICE_TYPE_PAYPAL,
                $userData['billingaddress'],
                $userData['shippingaddress']
            );
        }
    }
}
