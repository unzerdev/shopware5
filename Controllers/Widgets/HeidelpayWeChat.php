<?php

declare(strict_types=1);

use HeidelPayment\Components\PaymentHandler\Traits\CanCharge;
use HeidelPayment\Controllers\AbstractHeidelpayPaymentController;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\PaymentTypes\Wechatpay;

class Shopware_Controllers_Widgets_HeidelpayWeChat extends AbstractHeidelpayPaymentController
{
    use CanCharge;

    /** @var Wechatpay */
    protected $paymentType;

    public function createPaymentAction(): void
    {
        parent::pay();

        try {
            $this->paymentType = $this->heidelpayClient->createPaymentType(new Wechatpay());
            $resultUrl         = $this->charge($this->paymentDataStruct->getReturnUrl());
        } catch (HeidelpayApiException $apiException) {
            $this->getApiLogger()->logException('Error while creating WeChatPay payment', $apiException);
            $this->redirect($this->getHeidelpayErrorUrl($apiException->getClientMessage()));
        }

        $this->view->assign([
            'success'     => isset($resultUrl),
            'redirectUrl' => $resultUrl,
        ]);
    }
}
