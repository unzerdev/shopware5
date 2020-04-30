<?php

declare(strict_types=1);

namespace HeidelPayment\Services\OrderStatus;

use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\TransactionTypes\Authorization;
use heidelpayPHP\Resources\TransactionTypes\Charge;

interface OrderStatusServiceInterface
{
    public function updatePaymentStatusByTransactionId(string $transactionId, int $statusId): void;

    public function updatePaymentStatusByPayment(Payment $payment): void;

    public function updatePaymentStatusByCharge(Charge $authorization): void;

    public function updatePaymentStatusByAuthorization(Authorization $authorization): void;
}
