<?php

declare(strict_types=1);

namespace UnzerPayment\Components\PaymentStatusMapper;

use UnzerPayment\Components\PaymentStatusMapper\Exception\StatusMapperException;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\PaymentTypes\SepaDirectDebitSecured;

class SepaDirectDebitSecuredStatusMapper extends AbstractStatusMapper implements StatusMapperInterface
{
    public function supports(BasePaymentType $paymentType): bool
    {
        return $paymentType instanceof SepaDirectDebitSecured;
    }

    public function getTargetPaymentStatus(Payment $paymentObject): int
    {
        if ($paymentObject->isCanceled()) {
            $status = $this->checkForRefund($paymentObject);

            if ($status !== self::INVALID_STATUS) {
                return $status;
            }

            throw new StatusMapperException(SepaDirectDebitSecured::getResourceName(), $paymentObject->getStateName());
        }

        return $this->mapPaymentStatus($paymentObject);
    }
}
