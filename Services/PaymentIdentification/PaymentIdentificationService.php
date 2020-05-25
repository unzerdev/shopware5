<?php

declare(strict_types=1);

namespace HeidelPayment\Services\PaymentIdentification;

use HeidelPayment\Installers\Attributes;

class PaymentIdentificationService implements PaymentIdentificationServiceInterface
{
    /**
     * {@inheritdoc}
     */
    public function isHeidelpayPayment(array $payment): bool
    {
        return strpos($payment['name'], 'heidel') === 0;
    }

    /**
     * {@inheritdoc}
     */
    public function isHeidelpayPaymentWithFrame(array $payment): bool
    {
        return strpos($payment['name'], 'heidel') !== false &&
            !empty($payment['attributes']) &&
            !empty($payment['attributes']['core']) &&
            !empty($payment['attributes']['core']->get(Attributes::HEIDEL_ATTRIBUTE_PAYMENT_FRAME));
    }
}
