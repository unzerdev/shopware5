<?php

declare(strict_types=1);

use HeidelPayment\Components\BookingMode;
use HeidelPayment\Components\PaymentHandler\Traits\CanCharge;
use HeidelPayment\Controllers\AbstractHeidelpayPaymentController;
use HeidelPayment\Services\PaymentVault\Struct\VaultedDeviceStruct;
use heidelpayPHP\Exceptions\HeidelpayApiException;

class Shopware_Controllers_Widgets_HeidelpaySepaDirectDebit extends AbstractHeidelpayPaymentController
{
    use CanCharge;

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
            $redirectUrl = $this->charge($this->paymentDataStruct->getReturnUrl());

            if ($bookingMode === BookingMode::CHARGE_REGISTER && $typeId === null) {
                $deviceVault = $this->container->get('heidel_payment.services.payment_device_vault');

                if (!$deviceVault->hasVaultedSepaMandate((int) $userData['additional']['user']['id'], $this->paymentType->getIban(), $userData['billingaddress'], $userData['shippingaddress'])) {
                    $deviceVault->saveDeviceToVault($this->paymentType, VaultedDeviceStruct::DEVICE_TYPE_SEPA_MANDATE, $userData['billingaddress'], $userData['shippingaddress']);
                }
            }
        } catch (HeidelpayApiException $apiException) {
            $this->getApiLogger()->logException('Error while creating SEPA direct debit payment', $apiException);
            $redirectUrl = $this->getHeidelpayErrorUrl($apiException->getClientMessage());
        } finally {
            $this->view->assign('redirectUrl', $redirectUrl);
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
