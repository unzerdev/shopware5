<?php

declare(strict_types=1);

namespace HeidelPayment\Components\Exception;

class StatusMapperException extends AbstractHeidelPaymentException
{
    protected $code            = 1582120289;
    protected $customerMessage = 'The payment was could not be processed. Please try again';
    protected $message         = 'Payment status not allowed for payment method: %s';

    public function __construct(string $paymentName, $message = '', $code = 0, Throwable $previous = null)
    {
        $this->message = sprintf($this->message, $paymentName);

        $message = !empty($message) ? $message . ' - ' . $paymentName : $this->message;
        $code    = $code ?: $this->code;

        parent::__construct($message, $code, $previous);
    }
}
