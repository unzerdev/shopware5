<?php

declare(strict_types=1);

namespace HeidelPayment\Services\Heidelpay\Webhooks;

use HeidelPayment\Services\Heidelpay\Webhooks\Handlers\WebhookHandlerInterface;

interface WebhookFactoryInterface
{
    /**
     * @return WebhookHandlerInterface[]
     */
    public function getWebhookHandlers(string $event): array;

    public function addWebhookHandler(WebhookHandlerInterface $webhookHandler, string $event): void;
}
