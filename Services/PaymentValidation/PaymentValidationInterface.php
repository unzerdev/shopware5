<?php

declare(strict_types=1);

namespace HeidelPayment\Services\PaymentValidation;

use HeidelPayment\Installers\PaymentMethods;
use heidelpayPHP\Resources\Payment;

interface PaymentValidationInterface
{
    public const PAYMENT_STATUS_PENDING_ALLOWED = [
        PaymentMethods::PAYMENT_NAME_PRE_PAYMENT,
        PaymentMethods::PAYMENT_NAME_INVOICE,
    ];

    /**
     * Will return an empty string if the object is valid
     * Else the reason for the invalidity will be the return
     */
    public function validatePaymentObject(Payment $paymentObject, string $paymentMethodShortName): string;
}
