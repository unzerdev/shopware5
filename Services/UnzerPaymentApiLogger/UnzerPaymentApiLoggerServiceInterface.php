<?php

declare(strict_types=1);

namespace UnzerPayment\Services\UnzerPaymentApiLogger;

use heidelpayPHP\Exceptions\HeidelpayApiException;
use Psr\Log\LoggerInterface;

interface UnzerPaymentApiLoggerServiceInterface
{
    public function logException(string $message, HeidelpayApiException $apiException): void;

    public function getPluginLogger(): LoggerInterface;
}
