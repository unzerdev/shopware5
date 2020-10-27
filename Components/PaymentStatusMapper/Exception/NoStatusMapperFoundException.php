<?php

declare(strict_types=1);

namespace UnzerPayment\Components\PaymentStatusMapper\Exception;

use UnzerPayment\Components\AbstractUnzerPaymentException;

class NoStatusMapperFoundException extends AbstractUnzerPaymentException
{
    public function __construct(string $paymentName)
    {
        parent::__construct(sprintf('No status mapper was found for payment method: %s', $paymentName));
    }
}
