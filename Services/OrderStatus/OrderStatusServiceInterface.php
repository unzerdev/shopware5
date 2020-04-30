<?php

declare(strict_types=1);

namespace HeidelPayment\Services\OrderStatus;

use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\TransactionTypes\Authorization;
use heidelpayPHP\Resources\TransactionTypes\Cancellation;
use heidelpayPHP\Resources\TransactionTypes\Charge;
use heidelpayPHP\Resources\TransactionTypes\Payout;
use heidelpayPHP\Resources\TransactionTypes\Shipment;

interface OrderStatusServiceInterface
{
    public function updatePaymentStatusByTransactionId(string $transactionId, int $statusId): void;

    public function updatePaymentStatusByAuthorization(Authorization $authorization): void;

    public function updatePaymentStatusByCharge(Charge $charge): void;

    public function updatePaymentStatusByChargeback(Cancellation $cancellation): void;

    public function updatePaymentStatusByPayment(Payment $payment): void;

    public function updatePaymentStatusByPayout(Payout $payout): void;

    public function updatePaymentStatusByShipment(Shipment $shipment): void;
}
