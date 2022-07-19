<?php

declare(strict_types=1);

namespace UnzerPayment\Components\PaymentStatusMapper;

use UnzerPayment\Components\PaymentStatusMapper\Exception\StatusMapperException;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\PaymentTypes\Prepayment;

class PrepaymentStatusMapper extends AbstractStatusMapper implements StatusMapperInterface
{
    public function supports(BasePaymentType $paymentType): bool
    {
        return $paymentType instanceof Prepayment;
    }

    public function getTargetPaymentStatus(Payment $paymentObject, ?bool $isWebhook = false): int
    {
        if ($isWebhook) {
            return $this->mapPaymentStatus($paymentObject);
        }

        if ($paymentObject->isCanceled()) {
            $status = $this->checkForRefund($paymentObject);

            if ($status !== self::INVALID_STATUS) {
                return $status;
            }

            throw new StatusMapperException(Prepayment::getResourceName(), $paymentObject->getStateName());
        }

        return $this->mapPaymentStatus($paymentObject);
    }
}
