<?php

namespace HeidelPayment\Services\Heidelpay\Webhooks;

use RuntimeException;
use Throwable;

class WebhookSecurityException extends RuntimeException
{
    /**
     * {@inheritdoc}
     */
    public function __construct($message = '', $code = 0, Throwable $previous = null)
    {
        $message = empty($message) ? 'Requested to execute a webhook with unmatched public keys!' : $message;

        parent::__construct(
            $message,
            $code,
            $previous
        );
    }
}
