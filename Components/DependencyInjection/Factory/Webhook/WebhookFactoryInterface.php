<?php

declare(strict_types=1);

namespace HeidelPayment\Components\DependencyInjection\Factory\Webhook;

use HeidelPayment\Components\WebhookHandler\Handler\WebhookHandlerInterface;

interface WebhookFactoryInterface
{
    public function getBehaviorHandler(string $event): array;

    public function addBehaviorHandler(WebhookHandlerInterface $behaviorHandler, string $event): void;
}
