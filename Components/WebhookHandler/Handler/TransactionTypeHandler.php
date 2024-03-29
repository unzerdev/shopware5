<?php

declare(strict_types=1);

namespace UnzerPayment\Components\WebhookHandler\Handler;

use UnzerPayment\Components\PaymentStatusMapper\AbstractStatusMapper;
use UnzerPayment\Components\WebhookHandler\Struct\WebhookStruct;
use UnzerPayment\Services\OrderStatus\OrderStatusServiceInterface;
use UnzerPayment\Services\UnzerAsyncOrderBackupService;
use UnzerPayment\Services\UnzerPaymentApiLogger\UnzerPaymentApiLoggerServiceInterface;
use UnzerPayment\Services\UnzerPaymentClient\UnzerPaymentClientServiceInterface;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\TransactionTypes\Authorization;
use UnzerSDK\Resources\TransactionTypes\Charge;

/**
 * @property UnzerPaymentClientServiceInterface    $unzerPaymentClientService
 * @property UnzerPaymentApiLoggerServiceInterface $apiLoggerService
 */
class TransactionTypeHandler extends AbstractWebhookHandler
{
    private OrderStatusServiceInterface $orderStatusService;

    private UnzerAsyncOrderBackupService $unzerAsyncOrderBackupService;

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
            $this->handlePaymentResource($this->resource);

            return;
        }

        if (!method_exists($this->resource, 'getPayment')) {
            $this->apiLoggerService->getPluginLogger()->alert('Could not get payment from resource', $this->resource->expose());

            return;
        }

        /** @var null|Payment $payment */
        $payment = $this->resource->getPayment();

        if ($payment === null) {
            $this->apiLoggerService->getPluginLogger()->alert('Could not get payment from resource', $this->resource->expose());

            return;
        }

        $this->handlePaymentResource($payment);
    }

    protected function handlePaymentResource(Payment $payment): void
    {
        if ($this->orderHandlingIsAllowed($payment)) {
            $isOrderCreateCall = $this->unzerAsyncOrderBackupService->createOrderFromUnzerOrderId($payment);

            if ($isOrderCreateCall) {
                $this->orderStatusService->updatePaymentStatusByPayment($payment);

                return;
            }
        }

        $this->orderStatusService->updatePaymentStatusByPayment($payment, true);
    }

    /**
     * Case 1: Transfer to the payment provider and closing the page -> Could be ignored due to invalid customer workflow
     * Case 2: Transfer to the payment provider and canceling the order (back to the store) -> Already covered by shopware defaults
     * Case 3: Transfer to the payment provider, completing PayPal handling and closing (or whatever) the page before redirecting to the store -> check payment state and authorization/charge
     * Case 4: Transfer to the payment provider, completing the handling and returning to the store -> Already covered by shopware defaults
     */
    protected function orderHandlingIsAllowed(Payment $payment): bool
    {
        $paymentStatusId = $this->orderStatusService->getPaymentStatusForPayment($payment);

        if ($paymentStatusId === AbstractStatusMapper::INVALID_STATUS) {
            return false;
        }

        /** @var null|Authorization $authorization */
        $authorization = $payment->getAuthorization();

        if ($authorization !== null && $authorization->isSuccess()) {
            return true;
        }

        /** @var null|Charge $charge */
        $charge = $payment->getChargeByIndex(0);

        return $charge !== null && $charge->isSuccess();
    }
}
