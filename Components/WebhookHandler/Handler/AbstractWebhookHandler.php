<?php

declare(strict_types=1);

namespace UnzerPayment\Components\WebhookHandler\Handler;

use UnzerPayment\Components\WebhookHandler\Struct\WebhookStruct;
use UnzerPayment\Services\UnzerPaymentApiLogger\UnzerPaymentApiLoggerServiceInterface;
use UnzerPayment\Services\UnzerPaymentClient\UnzerPaymentClientServiceInterface;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\AbstractUnzerResource;
use UnzerSDK\Unzer;

abstract class AbstractWebhookHandler implements WebhookHandlerInterface
{
    /** @var UnzerPaymentClientServiceInterface */
    protected $unzerPaymentClientService;

    /** @var Unzer */
    protected $unzerPaymentClient;

    /** @var AbstractUnzerResource */
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
        } catch (UnzerApiException $apiException) {
            $this->apiLoggerService->logException(sprintf('Error while fetching the webhook resource from url [%s]', $webhook->getRetrieveUrl()), $apiException);
        }
    }
}
