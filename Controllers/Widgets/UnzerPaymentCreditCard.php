<?php

declare(strict_types=1);

use UnzerPayment\Components\BookingMode;
use UnzerPayment\Components\PaymentHandler\Traits\CanAuthorize;
use UnzerPayment\Components\PaymentHandler\Traits\CanCharge;
use UnzerPayment\Components\PaymentHandler\Traits\CanRecur;
use UnzerPayment\Controllers\AbstractUnzerPaymentController;
use UnzerPayment\Services\PaymentVault\PaymentDeviceVault;
use UnzerPayment\Services\PaymentVault\Struct\VaultedDeviceStruct;
use UnzerSDK\Constants\RecurrenceTypes;
use UnzerSDK\Exceptions\UnzerApiException;

class Shopware_Controllers_Widgets_UnzerPaymentCreditCard extends AbstractUnzerPaymentController
{
    use CanAuthorize;
    use CanCharge;
    use CanRecur;

    protected bool $isAsync = true;

    protected PaymentDeviceVault $deviceVault;

    protected bool $isRedirectPayment = true;

    public function preDispatch(): void
    {
        parent::preDispatch();

        $this->deviceVault = $this->container->get('unzer_payment.services.payment_device_vault');
    }

    public function createPaymentAction(): void
    {
        parent::pay();

        if (!$this->paymentType && $this->paymentDataStruct->isRecurring()) {
            $activateRecurring = false;

            try {
                $activateRecurring = $this->handleRecurringPayment();
            } catch (UnzerApiException $apiException) {
                if ((string) $apiException->getCode() === AbstractUnzerPaymentController::ALREADY_RECURRING_ERROR_CODE) {
                    $activateRecurring = true;
                }
            }

            if (!$activateRecurring) {
                $this->view->assign('redirectUrl',
                    $this->getUnzerPaymentErrorUrlFromSnippet('recurringError')
                );

                return;
            }

            if ($this->recurring->getRedirectUrl() !== null) {
                $this->view->assign('redirectUrl', $this->recurring->getRedirectUrl());

                return;
            }
        }

        if ($this->paymentType !== null && $this->paymentType->isRecurring()) {
            $this->recurringFinishedAction();
        } else {
            $this->handleNormalPayment();
        }
    }

    public function chargeRecurringPaymentAction(): void
    {
        parent::recurring();

        if (empty($this->paymentDataStruct)) {
            $this->getApiLogger()->getPluginLogger()->error('The payment data struct could not be created');
            $this->view->assign('success', false);

            return;
        }

        try {
            $this->paymentDataStruct->setRecurrenceType(RecurrenceTypes::SCHEDULED);

            $this->charge($this->paymentDataStruct->getReturnUrl());
            $orderNumber = $this->createRecurringOrder();
        } catch (UnzerApiException $ex) {
            $this->getApiLogger()->logException($ex->getMessage(), $ex);
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

            $redirectUrl = null;

            if (empty($this->paymentType)) {
                $session           = $this->container->get('session');
                $paymentTypeId     = $session->offsetGet('PaymentTypeId');
                $this->paymentType = $this->unzerPaymentClient->fetchPaymentType($paymentTypeId);
            }

            if (!$this->paymentType->isRecurring()) {
                $this->getApiLogger()->getPluginLogger()->warning('Recurring could not be activated for basket', [$this->paymentDataStruct->getBasket()->jsonSerialize()]);
                $redirectUrl = $this->getUnzerPaymentErrorUrlFromSnippet('recurringError');

                // TODO fragen, ob hier returned werden soll
            }

            $bookingMode = $this->container->get('unzer_payment.services.config_reader')
                ->get('credit_card_bookingmode');

            $recurringType = $this->paymentDataStruct->isRecurring()
                ? RecurrenceTypes::SCHEDULED
                : RecurrenceTypes::ONE_CLICK;

            $this->paymentDataStruct->setRecurrenceType($recurringType);

            if ($bookingMode === BookingMode::CHARGE) {
                $redirectUrl = $this->charge($this->paymentDataStruct->getReturnUrl());
            } else {
                $redirectUrl = $this->authorize($this->paymentDataStruct->getReturnUrl());
            }

            $this->saveToDeviceVault();
        } catch (UnzerApiException $ex) {
            $this->getApiLogger()->logException('Error while creating CreditCard recurring payment', $ex);
            $redirectUrl = $this->getUnzerPaymentErrorUrl($ex->getClientMessage());
        } catch (RuntimeException $ex) {
            $redirectUrl = $this->getUnzerPaymentErrorUrlFromSnippet('communicationError');
        } finally {
            $redirectUrl = $this->handleEmptyRedirectUrl(!empty($redirectUrl) ? $redirectUrl : '', 'CreditCard');

            $this->view->assign('redirectUrl', $redirectUrl);
        }
    }

    private function handleNormalPayment(): void
    {
        $bookingMode = $this->container->get('unzer_payment.services.config_reader')->get('credit_card_bookingmode');
        $remember    = (bool) filter_var($this->request->get('rememberCreditCard'), FILTER_VALIDATE_BOOLEAN);

        try {
            $recurrenceType = $this->paymentDataStruct->isRecurring()
                ? RecurrenceTypes::SCHEDULED
                : ($remember ? RecurrenceTypes::ONE_CLICK : null);

            $this->paymentDataStruct->setRecurrenceType($recurrenceType);

            if ($bookingMode === BookingMode::CHARGE) {
                $redirectUrl = $this->charge($this->paymentDataStruct->getReturnUrl());
            } else {
                $redirectUrl = $this->authorize($this->paymentDataStruct->getReturnUrl());
            }

            $this->saveToDeviceVault();
        } catch (UnzerApiException $apiException) {
            $this->getApiLogger()->logException('Error while creating credit card payment', $apiException);
            $redirectUrl = $this->getUnzerPaymentErrorUrl($apiException->getClientMessage());
        } catch (RuntimeException $runtimeException) {
            $this->getApiLogger()->getPluginLogger()->error('Error while fetching payment', $runtimeException->getTrace());
            $redirectUrl = $this->getUnzerPaymentErrorUrlFromSnippet('communicationError');
        } finally {
            $redirectUrl = $this->handleEmptyRedirectUrl(!empty($redirectUrl) ? $redirectUrl : '', 'CreditCard');

            $this->view->assign('redirectUrl', $redirectUrl);
        }
    }

    private function handleRecurringPayment(): bool
    {
        $this->activateRecurring($this->paymentDataStruct->getReturnUrl());

        if (!$this->recurring) {
            $this->getApiLogger()->getPluginLogger()->warning(
                'Recurring could not be activated for basket',
                [$this->paymentDataStruct->getBasket()->jsonSerialize()]
            );

            return false;
        }

        return true;
    }

    private function saveToDeviceVault(): void
    {
        $typeId   = $this->request->get('typeId');
        $remember = (bool) filter_var($this->request->get('rememberCreditCard'), FILTER_VALIDATE_BOOLEAN);

        if ($remember && $typeId === null) {
            $userData = $this->getUser();

            $this->deviceVault->saveDeviceToVault($this->paymentType, VaultedDeviceStruct::DEVICE_TYPE_CARD, $userData['billingaddress'], $userData['shippingaddress']);
        }
    }
}
