<?php

declare(strict_types=1);

namespace HeidelPayment\Components\DependencyInjection\Factory\Webhook;

use HeidelPayment\Components\WebhookHandler\Handler\WebhookHandlerInterface;

interface WebhookFactoryInterface
{
    public function getWebhookHandlers(string $event): array;

    public function addWebhookHandler(WebhookHandlerInterface $behaviorHandler, string $event): void;
}
