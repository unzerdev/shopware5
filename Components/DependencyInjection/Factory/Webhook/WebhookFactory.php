<?php

declare(strict_types=1);

namespace HeidelPayment\Components\DependencyInjection\Factory\Webhook;

use HeidelPayment\Components\WebhookHandler\Handler\WebhookHandlerInterface;

class WebhookFactory implements WebhookFactoryInterface
{
    /** @var WebhookHandlerInterface[][] */
    protected $webhookHandlers;

    public function getBehaviorHandler(string $event): array
    {
        if (!array_key_exists($event, $this->webhookHandlers)) {
            return [];
        }

        return $this->webhookHandlers[$event];
    }

    public function addBehaviorHandler(WebhookHandlerInterface $webhookHandler, string $event): void
    {
        $this->webhookHandlers[$event][] = $webhookHandler;
    }
}
