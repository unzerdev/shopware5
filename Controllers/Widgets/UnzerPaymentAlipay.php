<?php

declare(strict_types=1);

use UnzerPayment\Components\PaymentHandler\Traits\CanCharge;
use UnzerPayment\Controllers\AbstractUnzerPaymentController;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\PaymentTypes\Alipay;

class Shopware_Controllers_Widgets_UnzerPaymentAlipay extends AbstractUnzerPaymentController
{
    use CanCharge;

    /** @var bool */
    protected $isRedirectPayment = true;

    public function createPaymentAction(): void
    {
        try {
            parent::pay();
            $this->paymentType = $this->unzerPaymentClient->createPaymentType(new Alipay());
            $redirectUrl       = $this->charge($this->paymentDataStruct->getReturnUrl());
        } catch (UnzerApiException $apiException) {
            $this->getApiLogger()->logException('Error while creating Alipay payment', $apiException);
            $redirectUrl = $this->getUnzerPaymentErrorUrl($apiException->getClientMessage());
        } catch (RuntimeException $runtimeException) {
            $redirectUrl = $this->getUnzerPaymentErrorUrlFromSnippet('communicationError');
        } finally {
            $redirectUrl = $this->handleEmptyRedirectUrl(!empty($redirectUrl) ? $redirectUrl : '', 'Alipay');

            $this->view->assign('redirectUrl', $redirectUrl);
        }
    }
}
