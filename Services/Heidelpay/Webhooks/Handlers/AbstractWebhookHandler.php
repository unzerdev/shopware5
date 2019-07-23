<?php

namespace HeidelPayment\Services\Heidelpay\Webhooks\Handlers;

use HeidelPayment\Services\Heidelpay\HeidelpayClientServiceInterface;
use HeidelPayment\Services\Heidelpay\Webhooks\Struct\WebhookStruct;
use heidelpayPHP\Heidelpay;
use heidelpayPHP\Resources\AbstractHeidelpayResource;

abstract class AbstractWebhookHandler implements WebhookHandlerInterface
{
    /** @var HeidelpayClientServiceInterface */
    protected $heidelpayClientService;

    /** @var Heidelpay */
    protected $heidelpayClient;

    /** @var AbstractHeidelpayResource */
    protected $resource;

    public function __construct(HeidelpayClientServiceInterface $heidelpayClient)
    {
        $this->heidelpayClientService = $heidelpayClient;

        $this->heidelpayClient = $heidelpayClient->getHeidelpayClient();
    }

    public function execute(WebhookStruct $webhook): void
    {
        $this->resource = $this->heidelpayClient->getResourceService()->fetchResourceByUrl($webhook->getRetrieveUrl());
    }
}
