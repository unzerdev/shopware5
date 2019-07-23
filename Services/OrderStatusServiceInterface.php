<?php

namespace HeidelPayment\Services;

use heidelpayPHP\Resources\Payment;

interface OrderStatusServiceInterface
{
    public function updatePaymentStatusByTransactionId(string $transactionId, int $statusId): void;

    public function updatePaymentStatusByPayment(Payment $payment): void;
}
