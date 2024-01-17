<?php

declare(strict_types=1);

namespace UnzerPayment\Components\WebhookHandler\Handler;

use UnzerPayment\Components\WebhookHandler\Struct\WebhookStruct;
use UnzerPayment\Services\UnzerPaymentApiLogger\UnzerPaymentApiLoggerServiceInterface;
use UnzerPayment\Services\UnzerPaymentClient\UnzerPaymentClientServiceInterface;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\AbstractUnzerResource;

abstract class AbstractWebhookHandler implements WebhookHandlerInterface
{
    protected UnzerPaymentClientServiceInterface $unzerPaymentClientService;

    protected AbstractUnzerResource $resource;

    protected UnzerPaymentApiLoggerServiceInterface $apiLoggerService;

    public function __construct(UnzerPaymentClientServiceInterface $unzerPaymentClientService, UnzerPaymentApiLoggerServiceInterface $apiLoggerService)
    {
        $this->unzerPaymentClientService = $unzerPaymentClientService;
        $this->apiLoggerService          = $apiLoggerService;
    }

    public function execute(WebhookStruct $webhook): void
    {
        try {
            $client = $this->unzerPaymentClientService->getUnzerPaymentClientByPublicKey($webhook->getPublicKey());

            if ($client === null) {
                $this->apiLoggerService->getPluginLogger()->error('Could not initialize Unzer Payment client from webhook', ['event' => $webhook->getEvent(), 'publicKey' => $webhook->getPublicKey()]);

                return;
            }

            $this->resource = $client->fetchResourceFromEvent($webhook->toJson());
        } catch (UnzerApiException $apiException) {
            $this->apiLoggerService->logException(sprintf('Error while fetching the webhook resource from url [%s]', $webhook->getRetrieveUrl()), $apiException);
        }
    }
}
