<?php

declare(strict_types=1);

namespace UnzerPayment\Components\WebhookHandler\Handler;

use UnzerPayment\Components\WebhookHandler\Struct\WebhookStruct;
use UnzerPayment\Services\UnzerPaymentApiLogger\UnzerPaymentApiLoggerServiceInterface;
use UnzerPayment\Services\UnzerPaymentClient\UnzerPaymentClientServiceInterface;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Heidelpay;
use heidelpayPHP\Resources\AbstractHeidelpayResource;

abstract class AbstractWebhookHandler implements WebhookHandlerInterface
{
    /** @var UnzerPaymentClientServiceInterface */
    protected $heidelpayClientService;

    /** @var Heidelpay */
    protected $heidelpayClient;

    /** @var AbstractHeidelpayResource */
    protected $resource;

    /** @var UnzerPaymentApiLoggerServiceInterface $apiLoggerService */
    protected $apiLoggerService;

    public function __construct(UnzerPaymentClientServiceInterface $heidelpayClient, UnzerPaymentApiLoggerServiceInterface $apiLoggerService)
    {
        $this->heidelpayClientService = $heidelpayClient;
        $this->heidelpayClient        = $heidelpayClient->getHeidelpayClient();
        $this->apiLoggerService       = $apiLoggerService;
    }

    public function execute(WebhookStruct $webhook): void
    {
        try {
            $this->resource = $this->heidelpayClient->fetchResourceFromEvent($webhook->toJson());
        } catch (HeidelpayApiException $apiException) {
            $this->apiLoggerService->logException(sprintf('Error while fetching the webhook resource from url [%s]', $webhook->getRetrieveUrl()), $apiException);
        }
    }
}
