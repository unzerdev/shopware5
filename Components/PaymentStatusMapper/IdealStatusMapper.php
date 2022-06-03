<?php

declare(strict_types=1);

namespace UnzerPayment\Components\PaymentStatusMapper;

use UnzerPayment\Components\PaymentStatusMapper\Exception\StatusMapperException;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\PaymentTypes\Ideal;

class IdealStatusMapper extends AbstractStatusMapper implements StatusMapperInterface
{
    public function supports(BasePaymentType $paymentType): bool
    {
        return $paymentType instanceof Ideal;
    }

    public function getTargetPaymentStatus(Payment $paymentObject): int
    {
        if ($paymentObject->isPending()) {
            throw new StatusMapperException(Ideal::getResourceName(), $paymentObject->getStateName());
        }

        if ($paymentObject->isCanceled()) {
            $status = $this->checkForRefund($paymentObject);

            if ($status !== self::INVALID_STATUS) {
                return $status;
            }

            throw new StatusMapperException(Ideal::getResourceName(), $paymentObject->getStateName());
        }

        return $this->mapPaymentStatus($paymentObject);
    }
}
