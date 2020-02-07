<?php

declare(strict_types=1);

use HeidelPayment\Components\BookingMode;
use HeidelPayment\Components\PaymentHandler\Traits\CanCharge;
use HeidelPayment\Components\PaymentHandler\Traits\CanRecurring;
use HeidelPayment\Controllers\AbstractHeidelpayPaymentController;
use HeidelPayment\Services\PaymentVault\Struct\VaultedDeviceStruct;
use heidelpayPHP\Exceptions\HeidelpayApiException;

class Shopware_Controllers_Widgets_HeidelpaySepaDirectDebit extends AbstractHeidelpayPaymentController
{
    use CanCharge;
    use CanRecurring;

    /** @var bool */
    protected $isAsync = true;

    public function createPaymentAction(): void
    {
        $mandateAccepted = (bool) $this->request->get('mandateAccepted');
        $typeId          = $this->request->get('typeId');
        $userData        = $this->getUser();

        if ((!$mandateAccepted && !$typeId) || !$this->isValidData($userData)) {
            $this->view->assign([
                'success'     => false,
                'redirectUrl' => $this->getHeidelpayErrorUrl(),
            ]);

            return;
        }

        $bookingMode = $this->container->get('heidel_payment.services.config_reader')->get('direct_debit_bookingmode');

        try {
            parent::pay();
            $resultUrl = $this->charge($this->paymentDataStruct->getReturnUrl());

            if ($bookingMode === BookingMode::CHARGE_REGISTER && $typeId === null) {
                $deviceVault = $this->container->get('heidel_payment.services.payment_device_vault');

                if (!$deviceVault->hasVaultedSepaMandate((int) $userData['additional']['user']['id'], $this->paymentType->getIban(), $userData['billingaddress'], $userData['shippingaddress'])) {
                    $deviceVault->saveDeviceToVault($this->paymentType, VaultedDeviceStruct::DEVICE_TYPE_SEPA_MANDATE, $userData['billingaddress'], $userData['shippingaddress']);
                }
            }
        } catch (HeidelpayApiException $apiException) {
            $this->getApiLogger()->logException('Error while creating SEPA direct debit payment', $apiException);
            $resultUrl = $this->getHeidelpayErrorUrl($apiException->getClientMessage());
        } finally {
            $this->view->assign([
                'success'     => isset($this->payment),
                'redirectUrl' => $resultUrl,
            ]);
        }
    }

    /**
     * Special case
     *
     * @see https://docs.heidelpay.com/docs/recurring#section-sepa-direct-debit
     */
    public function chargeRecurringPaymentAction(): void
    {
        parent::recurring();

        if (!$this->view->getAssign('success')) {
            return;
        }

        try {
            $resultUrl   = $this->charge($this->paymentDataStruct->getReturnUrl());
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

    private function isValidData(array $userData): bool
    {
        if (!$this->paymentType || !$this->paymentType->getIban()
            || !$userData['additional']['user']['id']
            || empty($userData['billingaddress']) || empty($userData['shippingaddress'])) {
            return false;
        }

        return true;
    }
}
