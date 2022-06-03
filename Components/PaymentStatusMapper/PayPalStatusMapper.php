<?php

declare(strict_types=1);

namespace UnzerPayment\Components\PaymentStatusMapper;

use Shopware\Models\Order\Status;
use UnzerPayment\Components\PaymentStatusMapper\Exception\StatusMapperException;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\PaymentTypes\Paypal;
use UnzerSDK\Resources\TransactionTypes\Charge;

class PayPalStatusMapper extends AbstractStatusMapper implements StatusMapperInterface
{
    public function supports(BasePaymentType $paymentType): bool
    {
        return $paymentType instanceof Paypal;
    }

    public function getTargetPaymentStatus(Payment $paymentObject): int
    {
        if ($paymentObject->isPending() && $paymentObject->getChargeByIndex(0) !== null) {
            $charge = $paymentObject->getChargeByIndex(0);

            if ($charge instanceof Charge && $charge->isSuccess()) {
                return Status::PAYMENT_STATE_COMPLETELY_PAID;
            }

            throw new StatusMapperException(Paypal::getResourceName(), $paymentObject->getStateName());
        }

        if ($paymentObject->isCanceled()) {
            $status = $this->checkForRefund($paymentObject);

            if ($status !== self::INVALID_STATUS) {
                return $status;
            }

            throw new StatusMapperException(Paypal::getResourceName(), $paymentObject->getStateName());
        }

        return $this->mapPaymentStatus($paymentObject);
    }
}
