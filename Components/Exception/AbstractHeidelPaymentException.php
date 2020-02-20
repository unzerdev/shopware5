<?php

declare(strict_types=1);

namespace HeidelPayment\Components\Exception;

use Exception;

abstract class AbstractHeidelPaymentException extends Exception
{
    protected $customerMessage = 'exception/statusMapper';

    public function getCustomerMessage(): string
    {
        return $this->customerMessage;
    }
}
