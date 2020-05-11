<?php

declare(strict_types=1);

namespace HeidelPayment\Components\PaymentStatusMapper;

use HeidelPayment\Components\PaymentStatusMapper\Exception\StatusMapperException;
use HeidelPayment\Installers\PaymentMethods;
use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\PaymentTypes\BasePaymentType;

interface StatusMapperInterface
{
    public const PAYMENT_STATUS_PENDING_ALLOWED = [
        PaymentMethods::PAYMENT_NAME_PRE_PAYMENT,
        PaymentMethods::PAYMENT_NAME_INVOICE,
    ];

    public function supports(BasePaymentType $paymentType): bool;

    /**
     * @throws StatusMapperException
     */
    public function getTargetPaymentStatus(Payment $paymentObject): int;
}
