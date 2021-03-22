<?php

declare(strict_types=1);

namespace UnzerPayment\Components\WebhookHandler\Handler;

use UnzerPayment\Components\WebhookHandler\Struct\WebhookStruct;
use UnzerPayment\Services\OrderStatus\OrderStatusServiceInterface;
use UnzerPayment\Services\UnzerPaymentApiLogger\UnzerPaymentApiLoggerServiceInterface;
use UnzerPayment\Services\UnzerPaymentClient\UnzerPaymentClientServiceInterface;
use UnzerSDK\Resources\AbstractUnzerResource;
use UnzerSDK\Resources\Payment;

/**
 * @property AbstractUnzerResource $resource
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
