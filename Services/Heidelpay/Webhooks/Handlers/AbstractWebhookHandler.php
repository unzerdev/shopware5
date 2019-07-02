<?php

namespace HeidelPayment\Services\Heidelpay\Webhooks\Handlers;

use HeidelPayment\Services\Heidelpay\HeidelpayClientServiceInterface;
use HeidelPayment\Services\Heidelpay\Webhooks\Struct\WebhookStruct;
use HeidelPayment\Services\Heidelpay\Webhooks\WebhookSecurityException;
use heidelpayPHP\Heidelpay;
use heidelpayPHP\Resources\AbstractHeidelpayResource;
use heidelpayPHP\Resources\Customer;
use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\TransactionTypes\Authorization;
use heidelpayPHP\Resources\TransactionTypes\Charge;

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
        if ($webhook->getPublicKey() !== $this->heidelpayClientService->getPublicKey()) {
            throw new WebhookSecurityException('Requested to execute a webhook with unmatched public keys!');
        }

        $resourceUrlExploded = explode('/', $webhook->getRetrieveUrl());
        $resourceId          = end($resourceUrlExploded);
        $dto                 = $this->getDTO($webhook->getEvent(), $resourceId);

        $response = $this->heidelpayClient->getResourceService()->send($dto);
        $dto->handleResponse($response);

        $this->resource = $dto;
    }

    private function getDTO(string $eventName, $resourceId): ?AbstractHeidelpayResource
    {
        $dtoName = explode('.', $eventName)[0];

        switch ($dtoName) {
            case 'payment':
                $payment = new Payment($this->heidelpayClient);
                $payment->setId('s-pay-35');

                return $payment;
            case 'customer':
                $customer = new Customer();
                $customer->setParentResource($this->heidelpayClient);
                $customer->setId($resourceId);

                return $customer;
            case 'charge':
                $charge = new Charge();
                $charge->setParentResource($this->heidelpayClient);
                $charge->setId($resourceId);

                return $charge;
            case 'authorize':
                $authorization = new Authorization();
                $authorization->setParentResource($this->heidelpayClient);
                $authorization->setId($resourceId);

                return $authorization;
        }

        return null;
    }
}
