<?php

declare(strict_types=1);

use UnzerPayment\Components\PaymentHandler\Traits\CanCharge;
use UnzerPayment\Controllers\AbstractUnzerPaymentController;
use UnzerSDK\Exceptions\UnzerApiException;

class Shopware_Controllers_Widgets_UnzerPaymentIdeal extends AbstractUnzerPaymentController
{
    use CanCharge;

    /** @var bool */
    protected $isAsync = true;

    /** @var bool */
    protected $isRedirectPayment = true;

    public function createPaymentAction(): void
    {
        try {
            parent::pay();
            $redirectUrl = $this->charge($this->paymentDataStruct->getReturnUrl());
        } catch (UnzerApiException $apiException) {
            $this->getApiLogger()->logException('Error while creating Ideal payment', $apiException);
            $redirectUrl = $this->getUnzerPaymentErrorUrl($apiException->getClientMessage());
        } catch (RuntimeException $runtimeException) {
            $redirectUrl = $this->getUnzerPaymentErrorUrlFromSnippet('communicationError');
        } finally {
            $redirectUrl = $this->handleEmptyRedirectUrl(!empty($redirectUrl) ? $redirectUrl : '', 'Ideal');

            $this->view->assign('redirectUrl', $redirectUrl);
        }
    }
}
