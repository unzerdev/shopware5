<?php

declare(strict_types=1);

use UnzerPayment\Components\PaymentHandler\Traits\CanCharge;
use UnzerPayment\Components\PaymentHandler\Traits\CanRecur;
use UnzerPayment\Controllers\AbstractUnzerPaymentController;
use UnzerPayment\Services\PaymentVault\Struct\VaultedDeviceStruct;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\PaymentTypes\SepaDirectDebit;

class Shopware_Controllers_Widgets_UnzerPaymentSepaDirectDebit extends AbstractUnzerPaymentController
{
    use CanCharge;
    use CanRecur;

    protected bool $isAsync = true;

    /** @var ?SepaDirectDebit */
    protected ?BasePaymentType $paymentType;

    public function createPaymentAction(): void
    {
        $mandateAccepted    = filter_var($this->request->get('mandateAccepted', false), FILTER_VALIDATE_BOOLEAN);
        $isPaymentFromVault = filter_var($this->request->get('isPaymentFromVault', false), FILTER_VALIDATE_BOOLEAN);
        $userData           = $this->getUser();

        if ((!$mandateAccepted && !$isPaymentFromVault) || !$this->isValidData($userData)) {
            $this->view->assign([
                'success'     => false,
                'redirectUrl' => $this->getUnzerPaymentErrorUrlFromSnippet('communicationError'),
            ]);

            return;
        }

        try {
            parent::pay();
            $redirectUrl = $this->charge($this->paymentDataStruct->getReturnUrl());

            $this->saveToDeviceVault($userData);
        } catch (UnzerApiException $ex) {
            $this->getApiLogger()->logException('Error while creating SEPA direct debit payment', $ex);
            $redirectUrl = $this->getUnzerPaymentErrorUrl($ex->getClientMessage());
        } catch (RuntimeException $ex) {
            $redirectUrl = $this->getUnzerPaymentErrorUrlFromSnippet('communicationError');
        } finally {
            $redirectUrl = $this->handleEmptyRedirectUrl(!empty($redirectUrl) ? $redirectUrl : '', 'SepaDirectDebit');

            $this->view->assign('redirectUrl', $redirectUrl);
        }
    }

    /**
     * Special case
     *
     * @see https://docs.unzer.com/docs/recurring#section-sepa-direct-debit
     */
    public function chargeRecurringPaymentAction(): void
    {
        parent::recurring();

        if (empty($this->paymentDataStruct)) {
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

    private function isValidData(array $userData): bool
    {
        if (!$userData['additional']['user']['id']
            || empty($userData['billingaddress'])
            || empty($userData['shippingaddress'])
            || empty($this->paymentType) || !$this->paymentType->getIban()) {
            return false;
        }

        return true;
    }

    private function saveToDeviceVault(array $userData): void
    {
        $remember = (bool) filter_var($this->request->get('rememberSepaMandate'), FILTER_VALIDATE_BOOLEAN);

        if (!empty($this->paymentType) && $remember) {
            $deviceVault = $this->container->get('unzer_payment.services.payment_device_vault');

            if (!$deviceVault->hasVaultedSepaMandate((int) $userData['additional']['user']['id'], $this->paymentType->getIban(), $userData['billingaddress'], $userData['shippingaddress'])) {
                $deviceVault->saveDeviceToVault($this->paymentType, VaultedDeviceStruct::DEVICE_TYPE_SEPA_MANDATE, $userData['billingaddress'], $userData['shippingaddress']);
            }
        }
    }
}
