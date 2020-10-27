<?php

declare(strict_types=1);

use UnzerPayment\Components\PaymentHandler\Traits\CanAuthorize;
use UnzerPayment\Controllers\AbstractUnzerPaymentController;
use heidelpayPHP\Exceptions\HeidelpayApiException;

class Shopware_Controllers_Widgets_UnzerPaymentHirePurchase extends AbstractUnzerPaymentController
{
    use CanAuthorize;

    /** @var bool */
    protected $isAsync = true;

    public function createPaymentAction(): void
    {
        try {
            parent::pay();

            $redirectUrl = $this->authorize($this->paymentDataStruct->getReturnUrl());

            if ($this->payment) {
                $charge = $this->payment->charge();

                $this->session->offsetSet('heidelPaymentId', $charge->getPaymentId());
            }
        } catch (HeidelpayApiException $apiException) {
            $this->getApiLogger()->logException('Error while creating Flexipay® Instalment payment', $apiException);
            $redirectUrl = $this->getHeidelpayErrorUrl($apiException->getClientMessage() ?: 'Error while creating Flexipay® Instalment payment');
        } finally {
            $this->view->assign('redirectUrl', $redirectUrl);
        }
    }
}
