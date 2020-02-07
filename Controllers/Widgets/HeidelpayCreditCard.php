<?php

declare(strict_types=1);

use HeidelPayment\Components\BookingMode;
use HeidelPayment\Components\PaymentHandler\Traits\CanAuthorize;
use HeidelPayment\Components\PaymentHandler\Traits\CanCharge;
use HeidelPayment\Components\PaymentHandler\Traits\CanRecurring;
use HeidelPayment\Controllers\AbstractHeidelpayPaymentController;
use HeidelPayment\Services\PaymentVault\Struct\VaultedDeviceStruct;
use heidelpayPHP\Exceptions\HeidelpayApiException;

class Shopware_Controllers_Widgets_HeidelpayCreditCard extends AbstractHeidelpayPaymentController
{
    use CanAuthorize;
    use CanCharge;
    use CanRecurring;

    /** @var bool */
    protected $isAsync = true;

    public function createPaymentAction(): void
    {
        parent::pay();

        if ($this->paymentDataStruct->isRecurring()) {
            $this->handleRecurringPayment();

            if (!$this->view->getAssign('success')) {
                return;
            }
        }

        $this->handleNormalPayment();
    }

    public function chargeRecurringPaymentAction(): void
    {
        parent::recurring();

        if (!$this->view->getAssign('success')) {
            return;
        }

        $this->request->set('typeId', 'notNull');

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

    private function handleNormalPayment(): void
    {
        try {
            $bookingMode = $this->container->get('heidel_payment.services.config_reader')->get('credit_card_bookingmode');
            $typeId      = $this->request->get('typeId');

            if ($bookingMode === BookingMode::CHARGE || $bookingMode === BookingMode::CHARGE_REGISTER) {
                $resultUrl = $this->charge($this->paymentDataStruct->getReturnUrl());
            } else {
                $resultUrl = $this->authorize($this->paymentDataStruct->getReturnUrl());
            }

            if (($bookingMode === BookingMode::CHARGE_REGISTER || $bookingMode === BookingMode::AUTHORIZE_REGISTER) && $typeId === null) {
                $deviceVault = $this->container->get('heidel_payment.services.payment_device_vault');
                $userData    = $this->getUser();

                $deviceVault->saveDeviceToVault($this->paymentType, VaultedDeviceStruct::DEVICE_TYPE_CARD, $userData['billingaddress'], $userData['shippingaddress']);
            }
        } catch (HeidelpayApiException $apiException) {
            $this->getApiLogger()->logException('Error while creating credit card payment', $apiException);
            $resultUrl = $this->getHeidelpayErrorUrl($apiException->getClientMessage());
        } finally {
            $this->view->assign([
                'success'     => isset($this->payment),
                'redirectUrl' => $resultUrl,
            ]);
        }
    }

    private function handleRecurringPayment(): void
    {
        $this->activateRecurring($this->paymentDataStruct->getReturnUrl());

        if (!$this->recurring) {
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
}
