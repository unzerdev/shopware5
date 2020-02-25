<?php

declare(strict_types=1);

namespace HeidelPayment\Components\PaymentStatusMapper\Exception;

use HeidelPayment\Components\AbstractHeidelPaymentException;

class NoStatusMapperFoundException extends AbstractHeidelPaymentException
{
    public function __construct(string $paymentName)
    {
        parent::__construct(sprintf('No status mapper was found for payment method: %s', $paymentName));
    }
}
