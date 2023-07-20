<?php

declare(strict_types=1);

use UnzerPayment\Components\PaymentHandler\Traits\CanCharge;
use UnzerPayment\Controllers\AbstractUnzerPaymentController;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\PaymentTypes\Bancontact;

class Shopware_Controllers_Widgets_UnzerPaymentBancontact extends AbstractUnzerPaymentController
{
    use CanCharge;

    /** @var bool */
    protected $isRedirectPayment = true;

    public function createPaymentAction(): void
    {
        try {
            parent::pay();
            $this->paymentType = $this->unzerPaymentClient->createPaymentType(new Bancontact());
            $redirectUrl       = $this->charge($this->paymentDataStruct->getReturnUrl());
        } catch (UnzerApiException $apiException) {
            $this->getApiLogger()->logException('Error while creating Bancontact payment', $apiException);
            $redirectUrl = $this->getUnzerPaymentErrorUrl($apiException->getClientMessage());
        } catch (RuntimeException $runtimeException) {
            $redirectUrl = $this->getUnzerPaymentErrorUrlFromSnippet('communicationError');
        } finally {
            $redirectUrl = $this->handleEmptyRedirectUrl(!empty($redirectUrl) ? $redirectUrl : '', 'Bancontact');

            $this->view->assign('redirectUrl', $redirectUrl);
        }
    }
}
