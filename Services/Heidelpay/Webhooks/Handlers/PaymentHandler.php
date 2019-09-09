<?php

namespace HeidelPayment\Services\Heidelpay\Webhooks\Handlers;

use HeidelPayment\Services\Heidelpay\HeidelpayClientServiceInterface;
use HeidelPayment\Services\Heidelpay\Webhooks\Struct\WebhookStruct;
use HeidelPayment\Services\HeidelpayApiLoggerServiceInterface;
use HeidelPayment\Services\OrderStatusServiceInterface;
use heidelpayPHP\Resources\Payment;

/**
 * @property Payment $resource
 */
class PaymentHandler extends AbstractWebhookHandler
{
    /** @var OrderStatusServiceInterface */
    private $orderStatusService;

    public function __construct(HeidelpayClientServiceInterface $heidelpayClient, OrderStatusServiceInterface $orderStatusService, HeidelpayApiLoggerServiceInterface $apiLoggerService)
    {
        parent::__construct(
            $heidelpayClient,
            $apiLoggerService
        );

        $this->orderStatusService = $orderStatusService;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(WebhookStruct $webhook)
    {
        parent::execute($webhook);

        $this->orderStatusService->updatePaymentStatusByPayment($this->resource);
    }
}
