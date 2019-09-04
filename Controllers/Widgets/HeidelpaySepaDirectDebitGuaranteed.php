<?php

use HeidelPayment\Components\BookingMode;
use HeidelPayment\Controllers\AbstractHeidelpayPaymentController;
use HeidelPayment\Services\PaymentVault\Struct\VaultedDeviceStruct;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\PaymentTypes\SepaDirectDebitGuaranteed;

class Shopware_Controllers_Widgets_HeidelpaySepaDirectDebitGuaranteed extends AbstractHeidelpayPaymentController
{
    /** @var SepaDirectDebitGuaranteed */
    protected $paymentType;

    public function createPaymentAction(): void
    {
        $typeId                = $this->request->get('typeId');
        $additionalRequestData = $this->request->get('additional');
        $mandateAccepted       = (bool) $additionalRequestData['mandateAccepted'];
        $birthday              = $additionalRequestData['birthday'];

        if (!$mandateAccepted && !$typeId) {
            $this->view->assign([
                'success'     => false,
                'redirectUrl' => $this->getHeidelpayErrorUrl(),
            ]);

            return;
        }

        $bookingMode  = $this->container->get('heidel_payment.services.config_reader')->get('direct_debit_bookingmode');

        $heidelCustomer = $this->getHeidelpayB2cCustomer();
        $heidelCustomer->setBirthDate($birthday);

        $heidelBasket = $this->getHeidelpayBasket();
        $heidelMetadata = $this->getHeidelpayMetadata();
        $returnUrl      = $this->getHeidelpayReturnUrl();

        try {
            $heidelCustomer = $this->heidelpayClient->createOrUpdateCustomer($heidelCustomer);

            $result = $this->paymentType->charge(
                $heidelBasket->getAmountTotalGross(),
                $heidelBasket->getCurrencyCode(),
                $returnUrl,
                $heidelCustomer,
                $heidelBasket->getOrderId(),
                $heidelMetadata,
                $heidelBasket
            );

            $this->getApiLogger()->logResponse('Created SEPA direct debit guaranteed payment', $result);

            if ($bookingMode === BookingMode::CHARGE_REGISTER && $typeId === null) {
                $deviceVault = $this->container->get('heidel_payment.services.payment_device_vault');
                $userData    = $this->getUser();

                if (!$deviceVault->hasVaultedSepaGuaranteedMandate($userData['additional']['user']['id'], $result->getIban(), $userData['billingaddress'], $userData['shippingaddress'])) {
                    $deviceVault->saveDeviceToVault($this->paymentType, VaultedDeviceStruct::DEVICE_TYPE_SEPA_MANDATE_GUARANTEED, $userData['billingaddress'], $userData['shippingaddress']);
                }
            }
        } catch (HeidelpayApiException $apiException) {
            $this->getApiLogger()->logException('Error while creating SEPA direct debit guaranteed payment', $apiException);

            $this->view->assign('redirectUrl', $this->getHeidelpayErrorUrl($apiException->getClientMessage()));
        }

        $this->view->assign('success', isset($result));

        if (isset($result)) {
            $this->session->offsetSet('heidelPaymentId', $result->getPaymentId());
            $this->view->assign('redirectUrl', $result->getPayment()->getRedirectUrl() ?: $returnUrl);
        }
    }
}
