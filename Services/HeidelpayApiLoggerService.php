<?php

declare(strict_types=1);

namespace HeidelPayment\Services;

use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Interfaces\DebugHandlerInterface;
use Psr\Log\LoggerInterface;

class HeidelpayApiLoggerService implements DebugHandlerInterface, HeidelpayApiLoggerServiceInterface
{
    /** @var LoggerInterface */
    private $logger;

    /** @var bool */
    private $extendedLogging;

    public function __construct(LoggerInterface $logger, ConfigReaderServiceInterface $configReaderService)
    {
        $this->logger          = $logger;
        $this->extendedLogging = (bool) $configReaderService->get('extended_logging');
    }

    /**
     * {@inheritdoc}
     */
    public function logException(string $message, HeidelpayApiException $apiException)
    {
        $this->logger->error($message, [
            'merchantMessage' => $apiException->getMerchantMessage(),
            'clientMessage'   => $apiException->getClientMessage(),
            'trace'           => $apiException->getTraceAsString(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function log(string $message)
    {
        if (!$this->extendedLogging) {
            return;
        }

        $this->logger->alert($message);
    }

    /**
     * {@inheritdoc}
     */
    public function getPluginLogger(): LoggerInterface
    {
        return $this->logger;
    }
}
