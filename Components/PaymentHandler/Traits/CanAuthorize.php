<?php

declare(strict_types=1);

namespace UnzerPayment\Components\PaymentHandler\Traits;

use UnzerPayment\Controllers\AbstractUnzerPaymentController;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\TransactionTypes\Authorization;
use RuntimeException;

/**
 * @property Authorization $paymentResult
 */
trait CanAuthorize
{
    /**
     * @throws HeidelpayApiException
     */
    public function authorize(string $returnUrl): string
    {
        if (!$this instanceof AbstractUnzerPaymentController) {
            throw new RuntimeException('Trait can only be used in a payment controller context which extends the AbstractUnzerPaymentController class');
        }

        if ($this->paymentType === null) {
            throw new RuntimeException('PaymentType can not be null');
        }

        if (!method_exists($this->paymentType, 'authorize')) {
            throw new RuntimeException('This payment type does not support authorization');
        }

        $this->paymentResult = $this->paymentType->authorize(
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

        $this->payment = $this->paymentResult->getPayment();

        $this->session->offsetSet('unzerPaymentId', $this->payment->getId());

        if ($this->payment !== null && !empty($this->paymentResult->getRedirectUrl())) {
            return $this->paymentResult->getRedirectUrl();
        }

        return $returnUrl;
    }
}
