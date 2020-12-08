<?php

declare(strict_types=1);

namespace HeidelPayment\Services\HeidelpayClient\Adapter;

use HeidelPayment\Services\HeidelpayApiLogger\HeidelpayApiLoggerService;
use heidelpayPHP\Adapter\CurlAdapter;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use Psr\Log\LogLevel;
use ReflectionClass;

class ExtendedHttpAdapter extends CurlAdapter
{
    /** @var HeidelpayApiLoggerService */
    protected $loggerService;

    public function __construct(HeidelpayApiLoggerService $loggerService)
    {
        $this->loggerService = $loggerService;
        parent::__construct();
    }

    public function execute(): ?string
    {
        $requestReflection = (new ReflectionClass($this))->getParentClass()->getProperty('request');
        $requestReflection->setAccessible(true);
        $request = $requestReflection->getValue($this);

        $this->loggerService->log('Request: ' . \json_encode(\curl_getinfo($request)), [], LogLevel::INFO);

        try {
            $response = parent::execute();
        } catch (HeidelpayApiException $e) {
            $this->loggerService->logException($e->getMessage(), $e);
            throw $e;
        }

        $this->loggerService->log('Response: ' . $response, [], LogLevel::INFO);

        return $response;
    }
}
