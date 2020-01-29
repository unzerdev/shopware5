<?php

declare(strict_types=1);

use HeidelPayment\Components\BookingMode;
use HeidelPayment\Components\PaymentHandler\Traits\CanCharge;
use HeidelPayment\Controllers\AbstractHeidelpayPaymentController;
use HeidelPayment\Services\PaymentVault\Struct\VaultedDeviceStruct;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\PaymentTypes\SepaDirectDebit;

class Shopware_Controllers_Widgets_HeidelpaySepaDirectDebit extends AbstractHeidelpayPaymentController
{
    use CanCharge;

    /** @var SepaDirectDebit */
    protected $paymentType;

    /** @var bool */
    protected $isAsync = true;

    public function createPaymentAction(): void
    {
        if (!$this->paymentType) {
            $this->handleCommunicationError();

            return;
        }

        $mandateAccepted = (bool) $this->request->get('mandateAccepted');
        $typeId          = $this->request->get('typeId');

        if (!$mandateAccepted && !$typeId) {
            $this->view->assign([
                'success'     => false,
                'redirectUrl' => $this->getHeidelpayErrorUrl(),
            ]);

            return;
        }

        $bookingMode = $this->container->get('heidel_payment.services.config_reader')->get('direct_debit_bookingmode');
        parent::pay();

        try {
            $resultUrl = $this->charge($this->paymentDataStruct->getReturnUrl());

            if ($bookingMode === BookingMode::CHARGE_REGISTER && $typeId === null) {
                $deviceVault = $this->container->get('heidel_payment.services.payment_device_vault');
                $userData    = $this->getUser();

                if (!$deviceVault->hasVaultedSepaMandate($userData['additional']['user']['id'], $this->paymentType->getIban(), $userData['billingaddress'], $userData['shippingaddress'])) {
                    $deviceVault->saveDeviceToVault($this->paymentType, VaultedDeviceStruct::DEVICE_TYPE_SEPA_MANDATE, $userData['billingaddress'], $userData['shippingaddress']);
                }
            }
        } catch (HeidelpayApiException $apiException) {
            $this->getApiLogger()->logException('Error while creating SEPA direct debit payment', $apiException);
            $this->view->assign('redirectUrl', $this->getHeidelpayErrorUrl($apiException->getClientMessage()));
        }

        $this->view->assign([
            'success'     => isset($resultUrl),
            'redirectUrl' => $resultUrl,
        ]);
    }
}
