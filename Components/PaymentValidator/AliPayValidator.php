<?php

declare(strict_types=1);

namespace HeidelPayment\Components\PaymentValidator;

use HeidelPayment\Installers\PaymentMethods;
use heidelpayPHP\Resources\Payment;

class AliPayValidator extends AbstractPaymentValidator implements PaymentValidatorInterface
{
    protected const PAYMENT_METHOD_SHORT_NAME = PaymentMethods::PAYMENT_NAME_ALIPAY;

    public function isValidPayment(Payment $paymentObject): bool
    {
        if ($paymentObject->isPending() || $paymentObject->isCanceled()) {
            return false;
        }

        return true;
    }

    public function getErrorMessage(Payment $paymentObject): string
    {
        if ($paymentObject->isPending()) {
            return $this->getMessageFromSnippet('paymentCancelled');
        }

        if ($paymentObject->isCanceled()) {
            return $this->getMessageFromPaymentTransaction($paymentObject);
        }

        return '';
    }
}
