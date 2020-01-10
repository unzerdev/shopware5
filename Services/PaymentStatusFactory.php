<?php

declare(strict_types=1);

namespace HeidelPayment\Services;

use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\PaymentTypes\EPS;
use heidelpayPHP\Resources\PaymentTypes\Giropay;
use heidelpayPHP\Resources\PaymentTypes\Ideal;
use heidelpayPHP\Resources\PaymentTypes\Paypal;
use heidelpayPHP\Resources\PaymentTypes\PIS;
use heidelpayPHP\Resources\PaymentTypes\Przelewy24;
use heidelpayPHP\Resources\PaymentTypes\Sofort;
use Shopware\Models\Order\Status;

class PaymentStatusFactory implements PaymentStatusFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function getPaymentStatusId(Payment $payment): int
    {

        $status = Status::PAYMENT_STATE_OPEN;

        if ($payment->isCanceled()) {
            $status = Status::PAYMENT_STATE_THE_PROCESS_HAS_BEEN_CANCELLED;
        } elseif ($payment->isChargeBack()) {
            $status = Status::PAYMENT_STATE_REVIEW_NECESSARY;
        } elseif ($payment->isCompleted()) {
            $status = Status::PAYMENT_STATE_COMPLETELY_PAID;
        } elseif ($payment->isPartlyPaid()) {
            $status = Status::PAYMENT_STATE_PARTIALLY_PAID;
        } elseif ($payment->isPaymentReview()) {
            $status = Status::PAYMENT_STATE_REVIEW_NECESSARY;
        } elseif ($payment->isPending()) {
            switch (true) {
                case $payment->getPaymentType() instanceof Paypal:
                case $payment->getPaymentType() instanceof Sofort:
                case $payment->getPaymentType() instanceof Giropay:
                case $payment->getPaymentType() instanceof PIS:
                case $payment->getPaymentType() instanceof Przelewy24:
                case $payment->getPaymentType() instanceof Ideal:
                case $payment->getPaymentType() instanceof EPS:
                    $status = Status::PAYMENT_STATE_REVIEW_NECESSARY;

                    break;
                default:
                    $status = Status::PAYMENT_STATE_RESERVED;
            }
        }

        return $status;
    }
}
