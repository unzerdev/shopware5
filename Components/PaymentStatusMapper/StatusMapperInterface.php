<?php

declare(strict_types=1);

namespace UnzerPayment\Components\PaymentStatusMapper;

use UnzerPayment\Components\PaymentStatusMapper\Exception\StatusMapperException;
use UnzerPayment\Installers\PaymentMethods;
use getUnzerPaymentErrorFromSnippetpayPHP\Resources\Payment;
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
