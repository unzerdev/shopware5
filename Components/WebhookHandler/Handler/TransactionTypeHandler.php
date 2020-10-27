<?php

declare(strict_types=1);

namespace UnzerPayment\Components\WebhookHandler\Handler;

use heidelpayPHP\Resources\AbstractHeidelpayResource;
use heidelpayPHP\Resources\Payment;
use UnzerPayment\Components\WebhookHandler\Struct\WebhookStruct;
use UnzerPayment\Services\OrderStatus\OrderStatusServiceInterface;
use UnzerPayment\Services\UnzerPaymentApiLogger\UnzerPaymentApiLoggerServiceInterface;
use UnzerPayment\Services\UnzerPaymentClient\UnzerPaymentClientServiceInterface;

/**
 * @property AbstractHeidelpayResource $resource
 */
class TransactionTypeHandler extends AbstractWebhookHandler
{
    /** @var OrderStatusServiceInterface */
    private $orderStatusService;

    public function __construct(
        UnzerPaymentClientServiceInterface $unzerPaymentClient,
        OrderStatusServiceInterface $orderStatusService,
        UnzerPaymentApiLoggerServiceInterface $apiLoggerService
    ) {
        parent::__construct($unzerPaymentClient, $apiLoggerService);

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
