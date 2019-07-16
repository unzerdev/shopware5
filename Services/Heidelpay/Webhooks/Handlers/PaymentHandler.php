<?php

namespace HeidelPayment\Services\Heidelpay\Webhooks\Handlers;

use HeidelPayment\Services\Heidelpay\HeidelpayClientServiceInterface;
use HeidelPayment\Services\Heidelpay\Webhooks\Struct\WebhookStruct;
use HeidelPayment\Services\OrderStatusServiceInterface;
use heidelpayPHP\Resources\Payment;

/**
 * @property Payment $resource
 */
class PaymentHandler extends AbstractWebhookHandler
{
    /** @var OrderStatusServiceInterface */
    private $orderStatusService;

    public function __construct(HeidelpayClientServiceInterface $heidelpayClient, OrderStatusServiceInterface $orderStatusService)
    {
        parent::__construct(
            $heidelpayClient
        );

        $this->orderStatusService = $orderStatusService;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(WebhookStruct $webhook): void
    {
        parent::execute($webhook);

        $this->orderStatusService->updatePaymentStatusByPayment($this->resource);
    }
}
