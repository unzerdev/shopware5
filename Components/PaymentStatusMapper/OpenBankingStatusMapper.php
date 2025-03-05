<?php

declare(strict_types=1);

namespace UnzerPayment\Components\PaymentStatusMapper;

use UnzerPayment\Components\PaymentStatusMapper\Exception\StatusMapperException;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\PaymentTypes\OpenbankingPis;

class OpenBankingStatusMapper extends AbstractStatusMapper implements StatusMapperInterface
{
    public function supports(BasePaymentType $paymentType): bool
    {
        return $paymentType instanceof OpenbankingPis;
    }

    public function getTargetPaymentStatus(Payment $paymentObject, ?bool $isWebhook = false): int
    {
        if ($isWebhook) {
            return $this->mapPaymentStatus($paymentObject);
        }

        if ($paymentObject->isPending()) {
            throw new StatusMapperException(OpenbankingPis::getResourceName(), $paymentObject->getStateName());
        }

        if ($paymentObject->isCanceled()) {
            $status = $this->checkForRefund($paymentObject);

            if ($status !== self::INVALID_STATUS) {
                return $status;
            }

            throw new StatusMapperException(OpenbankingPis::getResourceName(), $paymentObject->getStateName());
        }

        return $this->mapPaymentStatus($paymentObject);
    }
}
