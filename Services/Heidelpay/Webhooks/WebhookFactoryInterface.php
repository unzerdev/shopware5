<?php

namespace HeidelPayment\Services\Heidelpay\Webhooks;

use HeidelPayment\Services\Heidelpay\Webhooks\Handlers\WebhookHandlerInterface;

interface WebhookFactoryInterface
{
    /**
     * @param string $event
     *
     * @return WebhookHandlerInterface[]
     */
    public function getWebhookHandlers(string $event): array;

    public function addWebhookHandler(WebhookHandlerInterface $webhookHandler, string $event);
}
