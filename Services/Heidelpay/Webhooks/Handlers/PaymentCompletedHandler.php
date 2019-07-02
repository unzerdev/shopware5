<?php

namespace HeidelPayment\Services\Heidelpay\Webhooks\Handlers;

use HeidelPayment\Services\Heidelpay\Webhooks\Struct\WebhookStruct;

class PaymentCompletedHandler extends AbstractWebhookHandler
{
    /**
     * {@inheritdoc}
     */
    public function execute(WebhookStruct $webhook): void
    {
        parent::execute($webhook);
    }
}
