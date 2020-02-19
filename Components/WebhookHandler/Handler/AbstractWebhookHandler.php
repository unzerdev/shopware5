<?php

declare(strict_types=1);

namespace HeidelPayment\Components\WebhookHandler\Handler;

use HeidelPayment\Components\WebhookHandler\Struct\WebhookStruct;
use HeidelPayment\Services\Heidelpay\HeidelpayClientServiceInterface;
use HeidelPayment\Services\HeidelpayApiLoggerServiceInterface;
use heidelpayPHP\Exceptions\HeidelpayApiException;
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

    /** @var HeidelpayApiLoggerServiceInterface $apiLoggerService */
    protected $apiLoggerService;

    public function __construct(HeidelpayClientServiceInterface $heidelpayClient, HeidelpayApiLoggerServiceInterface $apiLoggerService)
    {
        $this->heidelpayClientService = $heidelpayClient;

        $this->heidelpayClient  = $heidelpayClient->getHeidelpayClient();
        $this->apiLoggerService = $apiLoggerService;
    }

    public function execute(WebhookStruct $webhook): void
    {
        try {
            $this->resource = $this->heidelpayClient->getResourceService()->fetchResourceByUrl($webhook->getRetrieveUrl());
        } catch (HeidelpayApiException $apiException) {
            $this->apiLoggerService->logException(sprintf('Error while fetching the webhook resource from url [%s]', $webhook->getRetrieveUrl()), $apiException);
        }
    }
}
