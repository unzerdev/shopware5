<?php

declare(strict_types=1);

namespace HeidelPayment\Services;

use heidelpayPHP\Exceptions\HeidelpayApiException;
use Psr\Log\LoggerInterface;

interface HeidelpayApiLoggerServiceInterface
{
    public function logException(string $message, HeidelpayApiException $apiException);

    public function getPluginLogger(): LoggerInterface;
}
