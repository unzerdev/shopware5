<?php

declare(strict_types=1);

namespace HeidelPayment\Components\PaymentStatusMapper;

use HeidelPayment\Components\PaymentStatusMapper\Exception\StatusMapperException;
use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\PaymentTypes\BasePaymentType;
use heidelpayPHP\Resources\PaymentTypes\Paypal;
use Shopware\Models\Order\Status;

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

            if ($charge->isSuccess()) {
                return Status::PAYMENT_STATE_COMPLETELY_PAID;
            }
            throw new StatusMapperException(Paypal::getResourceName());
        }

        if ($paymentObject->isCanceled()) {
            $status = $this->checkForRefund($paymentObject);

            if ($status !== 0) {
                return $status;
            }

            throw new StatusMapperException(Paypal::getResourceName());
        }

        return $this->mapPaymentStatus($paymentObject);
    }
}
