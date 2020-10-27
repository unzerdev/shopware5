<?php

declare(strict_types=1);

namespace UnzerPayment\Services\PaymentIdentification;

interface PaymentIdentificationServiceInterface
{
    public function isHeidelpayPayment(array $payment): bool;

    public function isHeidelpayPaymentWithFrame(array $payment): bool;
}
