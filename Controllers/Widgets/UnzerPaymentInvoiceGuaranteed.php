<?php

declare(strict_types=1);

use UnzerPayment\Components\PaymentHandler\Traits\CanCharge;
use UnzerPayment\Controllers\AbstractUnzerPaymentController;
use heidelpayPHP\Exceptions\HeidelpayApiException;

class Shopware_Controllers_Widgets_UnzerPaymentInvoiceGuaranteed extends AbstractUnzerPaymentController
{
    use CanCharge;

    /** @var bool */
    protected $isAsync = true;

    public function createPaymentAction(): void
    {
        try {
            parent::pay();
            $redirectUrl = $this->charge($this->paymentDataStruct->getReturnUrl());
        } catch (HeidelpayApiException $apiException) {
            $this->getApiLogger()->logException('Error while creating invoice guaranteed payment', $apiException);
            $redirectUrl = $this->getHeidelpayErrorUrl($apiException->getClientMessage());
        } catch (RuntimeException $runtimeException) {
            $redirectUrl = $this->getHeidelpayErrorUrlFromSnippet('communicationError');
        } finally {
            $this->view->assign('redirectUrl', $redirectUrl);
        }
    }
}