<?php

declare(strict_types=1);

namespace HeidelPayment\Components\Exception;

use Throwable;

class NoStatusMapperFoundException extends AbstractHeidelPaymentException
{
    protected $code    = 1582124283;
    protected $message = 'No validator was found for payment method: %s';

    public function __construct(string $paymentName, $message = '', $code = 0, Throwable $previous = null)
    {
        $this->message = sprintf($this->message, $paymentName);

        $message = !empty($message) ? $message . ' - ' . $paymentName : $this->message;
        $code    = $code ?: $this->code;

        parent::__construct($message, $code, $previous);
    }
}
