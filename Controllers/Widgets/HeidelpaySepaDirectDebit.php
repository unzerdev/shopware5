<?php

declare(strict_types=1);

use HeidelPayment\Components\BookingMode;
use HeidelPayment\Components\PaymentHandler\Traits\CanCharge;
use HeidelPayment\Components\PaymentHandler\Traits\CanRecur;
use HeidelPayment\Controllers\AbstractHeidelpayPaymentController;
use HeidelPayment\Services\PaymentVault\Struct\VaultedDeviceStruct;
use heidelpayPHP\Exceptions\HeidelpayApiException;

class Shopware_Controllers_Widgets_HeidelpaySepaDirectDebit extends AbstractHeidelpayPaymentController
{
    use CanCharge;
    use CanRecur;

    /** @var bool */
    protected $isAsync = true;

    public function createPaymentAction(): void
    {
        $mandateAccepted    = filter_var($this->request->get('mandateAccepted', false), FILTER_VALIDATE_BOOLEAN);
        $isPaymentFromVault = filter_var($this->request->get('isPaymentFromVault', false), FILTER_VALIDATE_BOOLEAN);
        $userData           = $this->getUser();

        if ((!$mandateAccepted && !$isPaymentFromVault) || !$this->isValidData($userData)) {
            $this->view->assign([
                'success'     => false,
                'redirectUrl' => $this->getHeidelpayErrorUrlFromSnippet('communicationError'),
            ]);

            return;
        }

        try {
            parent::pay();
            $redirectUrl = $this->charge($this->paymentDataStruct->getReturnUrl());

            $this->saveToDeviceVault($userData);
        } catch (HeidelpayApiException $ex) {
            $this->getApiLogger()->logException('Error while creating SEPA direct debit payment', $ex);
            $redirectUrl = $this->getHeidelpayErrorUrl($ex->getClientMessage());
        } catch (RuntimeException $ex) {
            $redirectUrl = $this->getHeidelpayErrorUrlFromSnippet('communicationError');
        } finally {
            $this->view->assign('redirectUrl', $redirectUrl);
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

        if (!$this->paymentDataStruct) {
            $this->getApiLogger()->getPluginLogger()->error('The payment data struct could not be created');
            $this->view->assign('success', false);

            return;
        }

        try {
            $resultUrl   = $this->charge($this->paymentDataStruct->getReturnUrl());
            $orderNumber = $this->createRecurringOrder();
        } catch (HeidelpayApiException $ex) {
            $this->getApiLogger()->logException($ex->getMessage(), $ex);
        } catch (RuntimeException $ex) {
            $this->getApiLogger()->getPluginLogger()->error($ex->getMessage(), $ex);
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
        if (empty($this->paymentType) || !$this->paymentType->getIban()
            || !$userData['additional']['user']['id']
            || empty($userData['billingaddress']) || empty($userData['shippingaddress'])) {
            return false;
        }

        return true;
    }

    private function saveToDeviceVault(array $userData): void
    {
        $bookingMode = $this->container->get('heidel_payment.services.config_reader')->get('direct_debit_bookingmode');

        if ($bookingMode === BookingMode::CHARGE_REGISTER && !empty($this->paymentType)) {
            $deviceVault = $this->container->get('heidel_payment.services.payment_device_vault');

            if (!$deviceVault->hasVaultedSepaMandate((int) $userData['additional']['user']['id'], $this->paymentType->getIban(), $userData['billingaddress'], $userData['shippingaddress'])) {
                $deviceVault->saveDeviceToVault($this->paymentType, VaultedDeviceStruct::DEVICE_TYPE_SEPA_MANDATE, $userData['billingaddress'], $userData['shippingaddress']);
            }
        }
    }
}
