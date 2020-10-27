<?php

declare(strict_types=1);

namespace UnzerPayment\Components\DependencyInjection\Factory\Webhook;

use UnzerPayment\Components\WebhookHandler\Handler\WebhookHandlerInterface;

class WebhookFactory implements WebhookFactoryInterface
{
    /** @var WebhookHandlerInterface[][] */
    protected $webhookHandlers;

    public function getWebhookHandlers(string $event): array
    {
        if (!array_key_exists($event, $this->webhookHandlers)) {
            return [];
        }

        return $this->webhookHandlers[$event];
    }

    public function addWebhookHandler(WebhookHandlerInterface $webhookHandler, string $event): void
    {
        $this->webhookHandlers[$event][] = $webhookHandler;
    }
}
