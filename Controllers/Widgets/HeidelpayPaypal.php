<?php

declare(strict_types=1);

use HeidelPayment\Components\PaymentHandler\Traits\CanAuthorize;
use HeidelPayment\Components\PaymentHandler\Traits\CanCharge;
use HeidelPayment\Controllers\AbstractHeidelpayPaymentController;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\PaymentTypes\Paypal;

class Shopware_Controllers_Widgets_HeidelpayPaypal extends AbstractHeidelpayPaymentController
{
    use CanAuthorize;
    use CanCharge;

    public function createPaymentAction(): void
    {
        try {
            parent::pay();
            $this->paymentType = $this->heidelpayClient->createPaymentType(new Paypal());
            $resultUrl         = $this->charge($this->paymentDataStruct->getReturnUrl());
        } catch (HeidelpayApiException $apiException) {
            $this->getApiLogger()->logException('Error while creating PayPal payment', $apiException);
            $resultUrl = $this->getHeidelpayErrorUrl($apiException->getClientMessage());
        } finally {
            $this->view->assign([
                'success'     => isset($this->payment),
                'redirectUrl' => $resultUrl,
            ]);
        }
    }
}
