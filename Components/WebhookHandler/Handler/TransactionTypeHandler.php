<?php

declare(strict_types=1);

namespace UnzerPayment\Components\WebhookHandler\Handler;

use UnzerPayment\Components\WebhookHandler\Struct\WebhookStruct;
use UnzerPayment\Services\OrderStatus\OrderStatusServiceInterface;
use UnzerPayment\Services\UnzerAsyncOrderBackupService;
use UnzerPayment\Services\UnzerPaymentApiLogger\UnzerPaymentApiLoggerServiceInterface;
use UnzerPayment\Services\UnzerPaymentClient\UnzerPaymentClientServiceInterface;
use UnzerSDK\Resources\Payment;

/**
 * @property UnzerPaymentClientServiceInterface    $unzerPaymentClientService
 * @property UnzerPaymentApiLoggerServiceInterface $apiLoggerService
 */
class TransactionTypeHandler extends AbstractWebhookHandler
{
    /** @var OrderStatusServiceInterface */
    private $orderStatusService;

    /** @var UnzerAsyncOrderBackupService */
    private $unzerAsyncOrderBackupService;

    public function __construct(
        UnzerPaymentClientServiceInterface $unzerPaymentClientService,
        UnzerPaymentApiLoggerServiceInterface $apiLoggerService,
        OrderStatusServiceInterface $orderStatusService,
        UnzerAsyncOrderBackupService $unzerAsyncOrderBackupService
    ) {
        parent::__construct($unzerPaymentClientService, $apiLoggerService);

        $this->orderStatusService           = $orderStatusService;
        $this->unzerAsyncOrderBackupService = $unzerAsyncOrderBackupService;
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

        $this->unzerAsyncOrderBackupService->createOrderFromUnzerOrderId($payment);

        $this->orderStatusService->updatePaymentStatusByPayment($payment);
    }
}
