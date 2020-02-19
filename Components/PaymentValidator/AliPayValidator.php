<?php

declare(strict_types=1);

namespace HeidelPayment\Components\PaymentValidator;

use heidelpayPHP\Resources\Payment;

class AliPayValidator implements PaymentValidatorInterface
{
    public function validatePayment(Payment $paymentObject, string $paymentShortName): bool
    {
        // TODO: Implement validatePayment() method.
    }
}
