<?php

declare(strict_types=1);

namespace HeidelPayment\Services\PaymentIdentification;

class PaymentIdentificationService implements PaymentIdentificationServiceInterface
{
    /**
     * {@inheritdoc}
     */
    public function isHeidelpayPayment(array $payment): bool
    {
        return (int) strpos($payment['name'], 'heidel') === 0;
    }

    /**
     * {@inheritdoc}
     */
    public function isHeidelpayPaymentWithFrame(array $payment): bool
    {
        return strpos($payment['name'], 'heidel') !== false && !empty($payment['embediframe']);
    }
}
