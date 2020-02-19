<?php

declare(strict_types=1);

namespace HeidelPayment\Components\PaymentValidator;

use HeidelPayment\Installers\PaymentMethods;
use heidelpayPHP\Resources\Payment;

class PayPalValidator extends AbstractPaymentValidator implements PaymentValidatorInterface
{
    protected const PAYMENT_METHOD_SHORT_NAME = PaymentMethods::PAYMENT_NAME_PAYPAL;

    public function isValidPayment(Payment $paymentObject): bool
    {
        // TODO: Implement isValidPayment() method.
    }

    public function getErrorMessage(Payment $paymentObject): string
    {
        // TODO: Implement getErrorMessage() method.
    }
}
