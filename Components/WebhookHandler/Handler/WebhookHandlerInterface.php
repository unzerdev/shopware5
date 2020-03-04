<?php

declare(strict_types=1);

namespace HeidelPayment\Components\WebhookHandler\Handler;

use HeidelPayment\Components\WebhookHandler\Struct\WebhookStruct;

interface WebhookHandlerInterface
{
    public function execute(WebhookStruct $webhook): void;
}
