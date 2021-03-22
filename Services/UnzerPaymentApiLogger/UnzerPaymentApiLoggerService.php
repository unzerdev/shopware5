<?php

declare(strict_types=1);

namespace UnzerPayment\Services\UnzerPaymentApiLogger;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use UnzerPayment\Services\ConfigReader\ConfigReaderServiceInterface;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Interfaces\DebugHandlerInterface;

class UnzerPaymentApiLoggerService implements DebugHandlerInterface, UnzerPaymentApiLoggerServiceInterface
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
    public function logException(string $message, UnzerApiException $apiException): void
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
