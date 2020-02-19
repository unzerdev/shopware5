<?php

declare(strict_types=1);

namespace HeidelPayment\Components\PaymentValidator;

use heidelpayPHP\Resources\Payment;

interface PaymentValidatorInterface
{
    public function validatePayment(Payment $paymentObject, string $paymentShortName): bool;
}
