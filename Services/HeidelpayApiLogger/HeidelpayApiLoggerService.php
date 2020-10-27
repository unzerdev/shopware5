<?php

declare(strict_types=1);

namespace UnzerPayment\Services\HeidelpayApiLogger;

use UnzerPayment\Services\ConfigReader\ConfigReaderServiceInterface;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Interfaces\DebugHandlerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

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
    public function logException(string $message, HeidelpayApiException $apiException): void
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
    public function log(string $message, array $context = [], string $logType = LogLevel::DEBUG): void
    {
        if (!$this->extendedLogging && $logType === LogLevel::DEBUG) {
            return;
        }

        $this->logger->log($logType, $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function getPluginLogger(): LoggerInterface
    {
        return $this->logger;
    }
}
