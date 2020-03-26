<?php

declare(strict_types=1);

namespace HeidelPayment\Components\PaymentStatusMapper;

use HeidelPayment\Components\PaymentStatusMapper\Exception\StatusMapperException;
use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\PaymentTypes\BasePaymentType;
use heidelpayPHP\Resources\PaymentTypes\PIS;

class FlexipayDirectStatusMapper extends AbstractStatusMapper implements StatusMapperInterface
{
    public function supports(BasePaymentType $paymentType): bool
    {
        return $paymentType instanceof PIS;
    }

    public function getTargetPaymentStatus(Payment $paymentObject): int
    {
        if ($paymentObject->isPending()) {
            throw new StatusMapperException(PIS::getResourceName());
        }

        if ($paymentObject->isCanceled()) {
            $status = $this->checkForRefund($paymentObject);

            if ($status !== 0) {
                return $status;
            }

            throw new StatusMapperException(PIS::getResourceName());
        }

        return $this->mapPaymentStatus($paymentObject);
    }
}
