<?php

declare(strict_types=1);

namespace UnzerPayment\Components;

use Psr\Log\LoggerInterface;
use UnzerSDK\Interfaces\DebugHandlerInterface;

class UnzerDebugHandler implements DebugHandlerInterface
{
    protected LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function log(string $message): void
    {
        $this->logger->info($message);
    }
}
