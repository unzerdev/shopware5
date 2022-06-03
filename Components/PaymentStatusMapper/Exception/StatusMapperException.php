<?php

declare(strict_types=1);

namespace UnzerPayment\Components\PaymentStatusMapper\Exception;

use UnzerPayment\Components\AbstractUnzerPaymentException;

class StatusMapperException extends AbstractUnzerPaymentException
{
    public function __construct(string $paymentName, ?string $paymentStatusName = 'UNKNOWN STATUS')
    {
        parent::__construct(sprintf('Payment status "%s" is not allowed for payment method: "%s"', $paymentStatusName, $paymentName));
    }
}
