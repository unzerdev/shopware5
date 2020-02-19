<?php

declare(strict_types=1);

namespace HeidelPayment\Components\PaymentStatusMapper;

use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\PaymentTypes\BasePaymentType;
use heidelpayPHP\Resources\PaymentTypes\Sofort;

class SofortStatusMapper extends AbstractStatusMapper implements StatusMapperInterface
{
    public function supports(BasePaymentType $paymentType): bool
    {
        return $paymentType instanceof Sofort;
    }

    public function getTargetPaymentStatus(Payment $paymentObject): int
    {
        if ($paymentObject->isPending() || $paymentObject->isCanceled()) {
            throw new StatusMapperException($paymentObject->getPaymentType()::getResourceName());
        }

        $this->mapPaymentStatus($paymentObject);
    }
}
