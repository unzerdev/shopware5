<?php

namespace HeidelPayment\Services\Heidelpay\Webhooks\Handlers;

use HeidelPayment\Services\Heidelpay\Webhooks\Struct\WebhookStruct;

interface WebhookHandlerInterface
{
    public function execute(WebhookStruct $webhook);
}
