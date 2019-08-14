<?php

namespace HeidelPayment\Services;

use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Resources\AbstractHeidelpayResource;
use Shopware\Components\Logger;

class HeidelpayApiLoggerService implements HeidelpayApiLoggerServiceInterface
{
    /** @var Logger */
    private $logger;

    /** @var bool */
    private $extendedLogging;

    public function __construct(Logger $logger, ConfigReaderServiceInterface $configReaderService)
    {
        $this->logger          = $logger;
        $this->extendedLogging = (bool) $configReaderService->get('extended_logging');
    }

    public function logResponse(string $message, AbstractHeidelpayResource $response): void
    {
        if (!$this->extendedLogging) {
            return;
        }

        $this->logger->debug($message, [
            'response' => $response->expose(),
            'uri'      => $response->getUri(),
        ]);
    }

    public function logException(string $message, HeidelpayApiException $apiException): void
    {
        $this->logger->error($message, [
            'merchantMessage' => $apiException->getMerchantMessage(),
            'clientMessage'   => $apiException->getClientMessage(),
            'trace'           => $apiException->getTraceAsString(),
        ]);
    }
}
