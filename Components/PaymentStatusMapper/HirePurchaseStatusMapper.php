<?php

declare(strict_types=1);

namespace HeidelPayment\Components\PaymentStatusMapper;

use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\PaymentTypes\BasePaymentType;
use heidelpayPHP\Resources\PaymentTypes\HirePurchaseDirectDebit;

class HirePurchaseStatusMapper extends AbstractStatusMapper implements StatusMapperInterface
{
    public function supports(BasePaymentType $paymentType): bool
    {
        return $paymentType instanceof HirePurchaseDirectDebit;
    }

    public function getTargetPaymentStatus(Payment $paymentObject): int
    {
        // TODO: Implement isValidPayment() method.
    }
}
