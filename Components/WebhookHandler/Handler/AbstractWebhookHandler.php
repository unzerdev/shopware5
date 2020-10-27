<?php

declare(strict_types=1);

namespace UnzerPayment\Components\WebhookHandler\Handler;

use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Heidelpay;
use heidelpayPHP\Resources\AbstractHeidelpayResource;
use UnzerPayment\Components\WebhookHandler\Struct\WebhookStruct;
use UnzerPayment\Services\UnzerPaymentApiLogger\UnzerPaymentApiLoggerServiceInterface;
use UnzerPayment\Services\UnzerPaymentClient\UnzerPaymentClientServiceInterface;

abstract class AbstractWebhookHandler implements WebhookHandlerInterface
{
    /** @var UnzerPaymentClientServiceInterface */
    protected $unzerPaymentClientService;

    /** @var Heidelpay */
    protected $unzerPaymentClient;

    /** @var AbstractHeidelpayResource */
    protected $resource;

    /** @var UnzerPaymentApiLoggerServiceInterface $apiLoggerService */
    protected $apiLoggerService;

    public function __construct(UnzerPaymentClientServiceInterface $unzerPaymentClient, UnzerPaymentApiLoggerServiceInterface $apiLoggerService)
    {
        $this->unzerPaymentClientService = $unzerPaymentClient;
        $this->unzerPaymentClient        = $unzerPaymentClient->getUnzerPaymentClient();
        $this->apiLoggerService          = $apiLoggerService;
    }

    public function execute(WebhookStruct $webhook): void
    {
        try {
            $this->resource = $this->unzerPaymentClient->fetchResourceFromEvent($webhook->toJson());
        } catch (HeidelpayApiException $apiException) {
            $this->apiLoggerService->logException(sprintf('Error while fetching the webhook resource from url [%s]', $webhook->getRetrieveUrl()), $apiException);
        }
    }
}
