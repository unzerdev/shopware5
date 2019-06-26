<?php

namespace HeidelPayment\Services;

use heidelpayPHP\Resources\Payment;

interface PaymentStatusFactoryInterface
{
    public function getPaymentStatusId(Payment $payment): int;
}
