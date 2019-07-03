<?php

namespace HeidelPayment\Services\Heidelpay\Webhooks\Handlers;

use HeidelPayment\Services\Heidelpay\HeidelpayClientServiceInterface;
use HeidelPayment\Services\Heidelpay\Webhooks\Struct\WebhookStruct;
use HeidelPayment\Services\OrderStatusServiceInterface;
use heidelpayPHP\Resources\Payment;

class PaymentPendingHandler extends AbstractWebhookHandler
{
    /** @var Payment */
    protected $resource;

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
