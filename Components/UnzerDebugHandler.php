<?php

declare(strict_types=1);

namespace UnzerPayment\Components;

use heidelpayPHP\Interfaces\DebugHandlerInterface;
use Psr\Log\LoggerInterface;

class UnzerDebugHandler implements DebugHandlerInterface
{
    /** @var LoggerInterface */
    protected $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function log(string $message): void
    {
        $this->logger->info($message);
    }
}
