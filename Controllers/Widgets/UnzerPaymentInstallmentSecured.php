<?php

declare(strict_types=1);

use UnzerPayment\Components\PaymentHandler\Traits\CanAuthorize;
use UnzerPayment\Controllers\AbstractUnzerPaymentController;
use UnzerSDK\Exceptions\UnzerApiException;

class Shopware_Controllers_Widgets_UnzerPaymentInstallmentSecured extends AbstractUnzerPaymentController
{
    use CanAuthorize;

    protected bool $isAsync = true;

    public function createPaymentAction(): void
    {
        try {
            parent::pay();

            $redirectUrl = $this->authorize($this->paymentDataStruct->getReturnUrl());

            if ($this->payment) {
                $charge = $this->payment->charge();

                $this->session->offsetSet('unzerPaymentId', $charge->getPaymentId());
            }
        } catch (UnzerApiException $apiException) {
            $this->getApiLogger()->logException('Error while creating Installment secured payment', $apiException);
            $redirectUrl = $this->getUnzerPaymentErrorUrl($apiException->getClientMessage() ?: 'Error while creating Installment secured payment');
        } finally {
            $redirectUrl = $this->handleEmptyRedirectUrl(!empty($redirectUrl) ? $redirectUrl : '', 'InstallmentSecured');

            $this->view->assign('redirectUrl', $redirectUrl);
        }
    }
}
