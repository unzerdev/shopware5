<?php

declare(strict_types=1);

use HeidelPayment\Components\BookingMode;
use HeidelPayment\Components\PaymentHandler\Traits\CanCharge;
use HeidelPayment\Controllers\AbstractHeidelpayPaymentController;
use HeidelPayment\Services\PaymentVault\Struct\VaultedDeviceStruct;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\PaymentTypes\SepaDirectDebitGuaranteed;

class Shopware_Controllers_Widgets_HeidelpaySepaDirectDebitGuaranteed extends AbstractHeidelpayPaymentController
{
    use CanCharge;

    /** @var SepaDirectDebitGuaranteed */
    protected $paymentType;

    /** @var bool */
    protected $isAsync = true;

    /** @var bool */
    protected $isB2bCustomerAllowed = true;

    public function createPaymentAction(): void
    {
        if (!$this->paymentType) {
            $this->handleCommunicationError();

            return;
        }

        $typeId                = $this->request->get('typeId');
        $additionalRequestData = $this->request->get('additional');
        $mandateAccepted       = (bool) $additionalRequestData['mandateAccepted'];

        if (!$mandateAccepted && !$typeId) {
            $this->view->assign([
                'success'     => false,
                'redirectUrl' => $this->getHeidelpayErrorUrl(),
            ]);

            return;
        }

        parent::pay();

        $bookingMode = $this->container->get('heidel_payment.services.config_reader')->get('direct_debit_bookingmode');

        try {
            $resultUrl = $this->charge($this->paymentDataStruct->getReturnUrl());

            if ($bookingMode === BookingMode::CHARGE_REGISTER && $typeId === null) {
                $deviceVault = $this->container->get('heidel_payment.services.payment_device_vault');
                $userData    = $this->getUser();

                if (!$deviceVault->hasVaultedSepaGuaranteedMandate($userData['additional']['user']['id'], $this->paymentType->getIban(), $userData['billingaddress'], $userData['shippingaddress'])) {
                    $deviceVault->saveDeviceToVault($this->paymentType, VaultedDeviceStruct::DEVICE_TYPE_SEPA_MANDATE_GUARANTEED, $userData['billingaddress'], $userData['shippingaddress']);
                }
            }
        } catch (HeidelpayApiException $apiException) {
            $this->getApiLogger()->logException('Error while creating SEPA direct debit guaranteed payment', $apiException);
            $this->view->assign('redirectUrl', $this->getHeidelpayErrorUrl($apiException->getClientMessage()));
        }

        $this->view->assign([
            'success'     => isset($resultUrl),
            'redirectUrl' => $resultUrl,
        ]);
    }
}
