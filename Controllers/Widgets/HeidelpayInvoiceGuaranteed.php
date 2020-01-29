<?php

declare(strict_types=1);

use HeidelPayment\Components\PaymentHandler\Traits\CanCharge;
use HeidelPayment\Controllers\AbstractHeidelpayPaymentController;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\PaymentTypes\InvoiceGuaranteed;

class Shopware_Controllers_Widgets_HeidelpayInvoiceGuaranteed extends AbstractHeidelpayPaymentController
{
    use CanCharge;

    /** @var InvoiceGuaranteed */
    protected $paymentType;

    /** @var bool */
    protected $isAsync = true;

    /** @var bool */
    protected $isB2bCustomerAllowed = true;

    public function createPaymentAction(): void
    {
        if (!$this->paymentType) {
            $this->handleCommunicationError();

            return;
        }

        parent::pay();

        try {
            $resultUrl = $this->charge($this->paymentDataStruct->getReturnUrl());
        } catch (HeidelpayApiException $apiException) {
            $this->getApiLogger()->logException('Error while creating invoice guaranteed payment', $apiException);
            $this->view->assign('redirectUrl', $this->getHeidelpayErrorUrl($apiException->getClientMessage()));
        }

        $this->view->assign([
            'success'     => isset($resultUrl),
            'redirectUrl' => $resultUrl,
        ]);
    }
}
