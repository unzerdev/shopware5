<?php

declare(strict_types=1);

namespace UnzerPayment\Services\UnzerPaymentApiLogger;

use Psr\Log\LoggerInterface;
use UnzerSDK\Exceptions\UnzerApiException;

interface UnzerPaymentApiLoggerServiceInterface
{
    public function logException(string $message, UnzerApiException $apiException): void;

    public function getPluginLogger(): LoggerInterface;
}
