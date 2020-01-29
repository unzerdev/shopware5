<?php

declare(strict_types=1);

use HeidelPayment\Components\PaymentHandler\Traits\CanCharge;
use HeidelPayment\Controllers\AbstractHeidelpayPaymentController;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\PaymentTypes\PIS;

class Shopware_Controllers_Widgets_HeidelpayFlexipay extends AbstractHeidelpayPaymentController
{
    use CanCharge;

    /** @var PIS */
    protected $paymentType;

    public function createPaymentAction(): void
    {
        parent::pay();

        try {
            $this->paymentType = $this->heidelpayClient->createPaymentType(new PIS());
            $resultUrl         = $this->charge($this->paymentDataStruct->getReturnUrl());
        } catch (HeidelpayApiException $apiException) {
            $this->getApiLogger()->logException('Error while creating Flexipay payment', $apiException);
            $this->redirect($this->getHeidelpayErrorUrl($apiException->getClientMessage()));
        }

        $this->view->assign([
            'success'     => isset($resultUrl),
            'redirectUrl' => $resultUrl,
        ]);
    }
}
