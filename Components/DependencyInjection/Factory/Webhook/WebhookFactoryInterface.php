<?php

declare(strict_types=1);

namespace HeidelPayment\Components\DependencyInjection\Factory\PaymentValidator;

use HeidelPayment\Services\Heidelpay\Webhooks\Handlers\WebhookHandlerInterface;

interface WebhookFactoryInterface
{
    public function getBehaviorHandler(string $event): array;

    public function addBehaviorHandler(WebhookHandlerInterface $behaviorHandler, string $event): void;
}
