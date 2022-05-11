<?php

declare(strict_types=1);

use UnzerPayment\Components\PaymentHandler\Traits\CanCharge;
use UnzerPayment\Components\PaymentHandler\Traits\OrderComment;
use UnzerPayment\Controllers\AbstractUnzerPaymentController;
use UnzerSDK\Exceptions\UnzerApiException;

class Shopware_Controllers_Widgets_UnzerPaymentInvoiceSecured extends AbstractUnzerPaymentController
{
    use CanCharge;
    use OrderComment;

    /** @var bool */
    protected $isAsync = true;

    public function createPaymentAction(): void
    {
        try {
            parent::pay();
            $redirectUrl = $this->charge($this->paymentDataStruct->getReturnUrl());
            $this->setOrderComment(Shopware_Controllers_Widgets_UnzerPaymentInvoice::SNIPPET_NAMESPACE);
        } catch (UnzerApiException $apiException) {
            $this->getApiLogger()->logException('Error while creating invoice guaranteed payment', $apiException);
            $redirectUrl = $this->getUnzerPaymentErrorUrl($apiException->getClientMessage());
        } catch (RuntimeException $runtimeException) {
            $redirectUrl = $this->getUnzerPaymentErrorUrlFromSnippet('communicationError');
        } finally {
            $this->view->assign('redirectUrl', $redirectUrl);
        }
    }
}
