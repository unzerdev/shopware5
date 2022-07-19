<?php

declare(strict_types=1);

namespace UnzerPayment\Components\PaymentStatusMapper;

use UnzerPayment\Components\PaymentStatusMapper\Exception\StatusMapperException;
use UnzerPayment\Installers\PaymentMethods;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;

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
    public function getTargetPaymentStatus(Payment $paymentObject, ?bool $isWebhook = false): int;
}
