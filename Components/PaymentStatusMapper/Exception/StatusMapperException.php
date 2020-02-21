<?php

declare(strict_types=1);

namespace HeidelPayment\Components\PaymentStatusMapper\Exception;

use HeidelPayment\Components\AbstractHeidelPaymentException;

class StatusMapperException extends AbstractHeidelPaymentException
{
    /** @var int */
    protected $code = 1582120289;

    /** @var string */
    protected $message = 'Payment status is not allowed for payment method: %s';

    public function __construct(string $paymentName, string $message = '', int $code = 0, Throwable $previous = null)
    {
        $this->message = sprintf($this->message, $paymentName);

        $message = !empty($message) ? $message . ' - ' . $paymentName : $this->message;
        $code    = $code ?: $this->code;

        parent::__construct($message, $code, $previous);
    }
}
