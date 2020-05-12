<?php

declare(strict_types=1);

namespace HeidelPayment\Components\WebhookHandler\Handler;

use HeidelPayment\Components\WebhookHandler\Struct\WebhookStruct;
use HeidelPayment\Services\HeidelpayApiLogger\HeidelpayApiLoggerServiceInterface;
use HeidelPayment\Services\HeidelpayClient\HeidelpayClientServiceInterface;
use HeidelPayment\Services\OrderStatus\OrderStatusServiceInterface;
use heidelpayPHP\Resources\AbstractHeidelpayResource;
use heidelpayPHP\Resources\Payment;

/**
 * @property AbstractHeidelpayResource $resource
 */
class TransactionTypeHandler extends AbstractWebhookHandler
{
    /** @var OrderStatusServiceInterface */
    private $orderStatusService;

    public function __construct(
        HeidelpayClientServiceInterface $heidelpayClient,
        OrderStatusServiceInterface $orderStatusService,
        HeidelpayApiLoggerServiceInterface $apiLoggerService
    ) {
        parent::__construct($heidelpayClient, $apiLoggerService);

        $this->orderStatusService = $orderStatusService;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(WebhookStruct $webhook): void
    {
        parent::execute($webhook);

        if (empty($this->resource)) {
            return;
        }

        if ($this->resource instanceof Payment) {
            $this->orderStatusService->updatePaymentStatusByPayment($this->resource);

            return;
        }

        if (!method_exists($this->resource, 'getPayment')) {
            $this->apiLoggerService->getPluginLogger()->alert('Could not get payment from resource', $this->resource->expose());

            return;
        }

        $payment = $this->resource->getPayment();

        if (empty($payment)) {
            $this->apiLoggerService->getPluginLogger()->alert('Could not get payment from resource', $this->resource->expose());

            return;
        }

        $this->orderStatusService->updatePaymentStatusByPayment($payment);
    }
}
