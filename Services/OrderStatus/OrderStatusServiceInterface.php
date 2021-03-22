<?php

declare(strict_types=1);

namespace UnzerPayment\Services\OrderStatus;

use UnzerSDK\Resources\Payment;

interface OrderStatusServiceInterface
{
    public function updatePaymentStatusByTransactionId(string $transactionId, int $statusId): void;

    public function updatePaymentStatusByPayment(Payment $payment): void;
}
