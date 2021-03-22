<?php

declare(strict_types=1);

namespace UnzerPayment\Components\PaymentHandler\Traits;

use RuntimeException;
use UnzerPayment\Controllers\AbstractUnzerPaymentController;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\TransactionTypes\Charge;

/**
 * @property Charge $paymentResult
 */
trait CanCharge
{
    /**
     * @throws UnzerApiException
     */
    public function charge(string $returnUrl): string
    {
        if (!$this instanceof AbstractUnzerPaymentController) {
            throw new RuntimeException('Trait can only be used in a payment controller context which extends the AbstractUnzerPaymentController class');
        }

        if ($this->paymentType === null) {
            throw new RuntimeException('PaymentType can not be null');
        }

        if (!method_exists($this->paymentType, 'charge')) {
            throw new RuntimeException('This payment type does not support direct charge!');
        }

        $this->paymentResult = $this->paymentType->charge(
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
