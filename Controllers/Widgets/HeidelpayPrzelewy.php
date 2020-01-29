<?php

declare(strict_types=1);

use HeidelPayment\Components\PaymentHandler\Traits\CanCharge;
use HeidelPayment\Controllers\AbstractHeidelpayPaymentController;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\PaymentTypes\Przelewy24;

class Shopware_Controllers_Widgets_HeidelpayPrzelewy extends AbstractHeidelpayPaymentController
{
    use CanCharge;

    /** @var Przelewy24 */
    protected $paymentType;

    public function createPaymentAction(): void
    {
        parent::pay();

        try {
            $this->paymentType = $this->heidelpayClient->createPaymentType(new Przelewy24());
            $resultUrl         = $this->paymentType->charge($this->paymentDataStruct->getReturnUrl());
        } catch (HeidelpayApiException $apiException) {
            $this->getApiLogger()->logException('Error while creating Przelewy24 payment', $apiException);
            $this->redirect($this->getHeidelpayErrorUrl($apiException->getClientMessage()));
        }

        $this->view->assign([
            'success'     => isset($resultUrl),
            'redirectUrl' => $resultUrl,
        ]);
    }
}
