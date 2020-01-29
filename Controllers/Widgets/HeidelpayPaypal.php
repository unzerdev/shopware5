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

    /** @var Paypal */
    protected $paymentType;

    public function createPaymentAction(): void
    {
        parent::pay();
        $bookingMode = $this->container->get('heidel_payment.services.config_reader')->get('paypal_bookingmode');

        try {
            $this->paymentType = $this->heidelpayClient->createPaymentType(new Paypal());

            $resultUrl = $this->charge($this->paymentDataStruct->getReturnUrl());
        } catch (HeidelpayApiException $apiException) {
            $this->getApiLogger()->logException('Error while creating PayPal payment', $apiException);
            $this->redirect($this->getHeidelpayErrorUrl($apiException->getClientMessage()));
        }

        $this->view->assign([
            'success'     => isset($resultUrl),
            'redirectUrl' => $resultUrl,
        ]);
    }
}
