<?php

namespace HeidelPayment\Services;

use heidelpayPHP\Resources\Payment;
use Shopware\Models\Order\Status;

class PaymentStatusFactory implements PaymentStatusFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function getPaymentStatusId(Payment $payment): int
    {
        if ($payment->isCanceled()) {
            return Status::PAYMENT_STATE_THE_PROCESS_HAS_BEEN_CANCELLED;
        } elseif ($payment->isChargeBack()) {
            return Status::PAYMENT_STATE_RE_CREDITING;
        } elseif ($payment->isCompleted()) {
            return Status::PAYMENT_STATE_COMPLETELY_PAID;
        } elseif ($payment->isPartlyPaid()) {
            return Status::PAYMENT_STATE_PARTIALLY_PAID;
        } elseif ($payment->isPaymentReview()) {
            return Status::PAYMENT_STATE_REVIEW_NECESSARY;
        } elseif ($payment->isPending()) {
            return Status::PAYMENT_STATE_RESERVED;
        }

        return Status::PAYMENT_STATE_OPEN;
    }
}
