<?php

declare(strict_types=1);

namespace UnzerPayment\Components\WebhookHandler\Handler;

use Shopware\Models\Order\Status;
use UnzerPayment\Components\PaymentStatusMapper\AbstractStatusMapper;
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
                $this->orderStatusService->updatePaymentStatusByTransactionId($payment->getOrderId(), Status::PAYMENT_STATE_RESERVED);

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

        /** @var null|\UnzerSDK\Resources\TransactionTypes\Authorization $authorization */
        $authorization = $payment->getAuthorization();

        if ($authorization !== null && $authorization->isSuccess()) {
            return true;
        }

        /** @var null|\UnzerSDK\Resources\TransactionTypes\Charge $charge */
        $charge = $payment->getChargeByIndex(0);

        if ($charge !== null && $charge->isSuccess()) {
            return true;
        }

        return false;
    }
}
// Case 1: Aufruf von PayPal und schließen der Seite -> Könnte :tm: ignoriert werden
// Case 2: Aufruf von PayPal und Stornieren der PayPal-Bestellung (zurück in den Shop) -> Bereits abgedeckt
// Case 3: Aufruf von PayPal, Abschluss des PayPal Handlings und schließen (oder was auch immer) der Seite vor Weiterleitung in den Shop ->
// Case 4: Aufruf von PayPal, Abschluss des Handlings und Rückkehr in den Shop -> Bereits abgedeckt
