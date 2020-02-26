<?php

declare(strict_types=1);

namespace HeidelPayment\Components\WebhookHandler\Handler;

use HeidelPayment\Components\WebhookHandler\Struct\WebhookStruct;
use HeidelPayment\Services\HeidelpayApiLogger\HeidelpayApiLoggerServiceInterface;
use HeidelPayment\Services\HeidelpayClient\HeidelpayClientServiceInterface;
use HeidelPayment\Services\OrderStatus\OrderStatusServiceInterface;
use heidelpayPHP\Resources\Payment;

/**
 * @property Payment $resource
 */
class PaymentHandler extends AbstractWebhookHandler
{
    /** @var OrderStatusServiceInterface */
    private $orderStatusService;

    public function __construct(
        HeidelpayClientServiceInterface $heidelpayClient,
        OrderStatusServiceInterface $orderStatusService,
        HeidelpayApiLoggerServiceInterface $apiLoggerService
    ) {
        parent::__construct(
            $heidelpayClient,
            $apiLoggerService
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
