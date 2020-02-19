<?php

declare(strict_types=1);

namespace HeidelPayment\Components\PaymentHandler\Traits;

use HeidelPayment\Controllers\AbstractHeidelpayPaymentController;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\TransactionTypes\Charge;
use RuntimeException;

trait CanCharge
{
    /**
     * @throws HeidelpayApiException
     */
    public function charge(string $returnUrl): string
    {
        if (!$this instanceof AbstractHeidelpayPaymentController) {
            throw new RuntimeException('Trait can only be used in a payment controller context which extends the AbstractHeidelpayPaymentController class');
        }

        if ($this->paymentType === null) {
            throw new RuntimeException('PaymentType can not be null');
        }

        if (!method_exists($this->paymentType, 'charge')) {
            throw new RuntimeException('This payment type does not support direct charge!');
        }

        $paymentResult = $this->paymentType->charge(
            $this->paymentDataStruct->getAmount(),
            $this->paymentDataStruct->getCurrency(),
            $this->paymentDataStruct->getReturnUrl(),
            $this->paymentDataStruct->getCustomer(),
            $this->paymentDataStruct->getOrderId(),
            $this->paymentDataStruct->getMetadata(),
            $this->paymentDataStruct->getBasket(),
            $this->paymentDataStruct->getCard3ds(),
            $this->paymentDataStruct->getInvoiceId(),
            $this->paymentDataStruct->getPaymentReference()
        );

        $this->payment = $paymentResult->getPayment();

        $this->session->offsetSet('heidelPaymentId', $this->payment->getId());

        if ($this->payment !== null && !empty($paymentResult->getRedirectUrl())) {
            return $paymentResult->getRedirectUrl();
        }

        return $returnUrl;
    }
}
