<?php

declare(strict_types=1);

namespace HeidelPayment\Components\PaymentValidator;

use heidelpayPHP\Resources\Payment;

interface PaymentValidatorInterface
{
    public const PAYMENT_STATUS_PENDING_ALLOWED = [
        PaymentMethods::PAYMENT_NAME_PRE_PAYMENT,
        PaymentMethods::PAYMENT_NAME_INVOICE,
    ];

    public function isValidPayment(Payment $paymentObject): bool;

    public function getErrorMessage(Payment $paymentObject): string;
}
