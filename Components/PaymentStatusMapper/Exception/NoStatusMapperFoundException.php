<?php

declare(strict_types=1);

namespace HeidelPayment\Components\PaymentStatusMapper\Exception;

use HeidelPayment\Components\AbstractHeidelPaymentException;
use Throwable;

class NoStatusMapperFoundException extends AbstractHeidelPaymentException
{
    /** @var int */
    protected $code = 1582124283;

    /** @var string */
    protected $message = 'No validator was found for payment method: %s';

    public function __construct(string $paymentName, string $message = '', int $code = 0, Throwable $previous = null)
    {
        $this->message = sprintf($this->message, $paymentName);

        $message = !empty($message) ? $message . ' - ' . $paymentName : $this->message;
        $code    = $code ?: $this->code;

        parent::__construct($message, $code, $previous);
    }
}
