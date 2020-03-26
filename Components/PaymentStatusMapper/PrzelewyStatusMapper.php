<?php

declare(strict_types=1);

namespace HeidelPayment\Components\PaymentStatusMapper;

use HeidelPayment\Components\PaymentStatusMapper\Exception\StatusMapperException;
use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\PaymentTypes\BasePaymentType;
use heidelpayPHP\Resources\PaymentTypes\Przelewy24;

class PrzelewyStatusMapper extends AbstractStatusMapper implements StatusMapperInterface
{
    public function supports(BasePaymentType $paymentType): bool
    {
        return $paymentType instanceof Przelewy24;
    }

    public function getTargetPaymentStatus(Payment $paymentObject): int
    {
        if ($paymentObject->isPending()) {
            throw new StatusMapperException(Przelewy24::getResourceName());
        }

        if ($paymentObject->isCanceled()) {
            $status = $this->checkForRefund($paymentObject);

            if ($status !== 0) {
                return $status;
            }

            throw new StatusMapperException(Przelewy24::getResourceName());
        }

        return $this->mapPaymentStatus($paymentObject);
    }
}
