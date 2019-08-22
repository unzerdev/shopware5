<?php

namespace HeidelPayment\Services;

use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\AbstractHeidelpayResource;

interface HeidelpayApiLoggerServiceInterface
{
    public function logResponse(string $message, AbstractHeidelpayResource $response): void;

    public function logException(string $message, HeidelpayApiException $apiException): void;
}
