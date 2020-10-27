<?php

declare(strict_types=1);

namespace UnzerPayment\Services\HeidelpayApiLogger;

use heidelpayPHP\Exceptions\HeidelpayApiException;
use Psr\Log\LoggerInterface;

interface HeidelpayApiLoggerServiceInterface
{
    public function logException(string $message, HeidelpayApiException $apiException): void;

    public function getPluginLogger(): LoggerInterface;
}
