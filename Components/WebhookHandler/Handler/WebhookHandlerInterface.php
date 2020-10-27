<?php

declare(strict_types=1);

namespace UnzerPayment\Components\WebhookHandler\Handler;

use UnzerPayment\Components\WebhookHandler\Struct\WebhookStruct;

interface WebhookHandlerInterface
{
    public function execute(WebhookStruct $webhook): void;
}
