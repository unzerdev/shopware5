<?php

declare(strict_types=1);

use UnzerPayment\Components\PaymentHandler\Traits\CanAuthorize;
use UnzerPayment\Components\PaymentHandler\Traits\HasRiskDataTrait;
use UnzerPayment\Components\PaymentHandler\Traits\OrderComment;
use UnzerPayment\Controllers\AbstractUnzerPaymentController;
use UnzerSDK\Exceptions\UnzerApiException;

class Shopware_Controllers_Widgets_UnzerPaymentPaylaterInvoice extends AbstractUnzerPaymentController
{
    use CanAuthorize;
    use HasRiskDataTrait;
    use OrderComment;

    protected bool $isAsync = true;

    protected bool $isRedirectPayment = true;

    public function createPaymentAction(): void
    {
        try {
            parent::pay();
            $riskData = $this->generateRiskDataResource();

            if (null === $riskData) {
                throw new \RuntimeException('Fraud prevention session id is missing from the current request');
            }

            $redirectUrl = $this->authorize(
                $this->paymentDataStruct->getReturnUrl(),
                $riskData
            );

            $this->setOrderComment(self::PAYLATER_INVOICE_SNIPPET_NAMESPACE);
        } catch (UnzerApiException $apiException) {
            $this->getApiLogger()->logException('Error while creating paylater invoice payment', $apiException);
            $redirectUrl = $this->getUnzerPaymentErrorUrl($apiException->getClientMessage());
        } catch (RuntimeException $runtimeException) {
            $this->getApiLogger()->log(
                $runtimeException->getMessage(),
                ['file' => $runtimeException->getFile(), 'trace' => $runtimeException->getTrace()]
            );
            $redirectUrl = $this->getUnzerPaymentErrorUrlFromSnippet('communicationError');
        } finally {
            $this->unsetFraudSessionId();

            $redirectUrl = $this->handleEmptyRedirectUrl(!empty($redirectUrl) ? $redirectUrl : '', 'PaylaterInvoice');

            $this->view->assign('redirectUrl', $redirectUrl);
        }
    }
}
