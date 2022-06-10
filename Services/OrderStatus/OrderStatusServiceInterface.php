<?php

declare(strict_types=1);

namespace UnzerPayment\Services\OrderStatus;

use UnzerSDK\Resources\Payment;

interface OrderStatusServiceInterface
{
    public function getPaymentStatusForPayment(Payment $payment, ?bool $isWebhook = false): int;

    public function updatePaymentStatusByTransactionId(string $transactionId, int $statusId): void;

    public function updatePaymentStatusByPayment(Payment $payment, ?bool $isWebhook = false): void;
}
