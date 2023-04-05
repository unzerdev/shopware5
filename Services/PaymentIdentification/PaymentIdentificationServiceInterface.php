<?php

declare(strict_types=1);

namespace UnzerPayment\Services\PaymentIdentification;

interface PaymentIdentificationServiceInterface
{
    public function isUnzerPayment(array $payment): bool;

    public function isUnzerPaymentWithFrame(array $payment): bool;

    public function isUnzerPaymentWithFraudPrevention(array $payment): bool;

    public function chargeCancellationNeedsCancellationObject(string $paymentId, int $shopId): bool;
}
