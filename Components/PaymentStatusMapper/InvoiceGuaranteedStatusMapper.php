<?php

declare(strict_types=1);

namespace UnzerPayment\Components\PaymentStatusMapper;

use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\PaymentTypes\BasePaymentType;
use heidelpayPHP\Resources\PaymentTypes\InvoiceGuaranteed;
use UnzerPayment\Components\PaymentStatusMapper\Exception\StatusMapperException;

class InvoiceGuaranteedStatusMapper extends AbstractStatusMapper implements StatusMapperInterface
{
    public function supports(BasePaymentType $paymentType): bool
    {
        return $paymentType instanceof InvoiceGuaranteed;
    }

    public function getTargetPaymentStatus(Payment $paymentObject): int
    {
        if ($paymentObject->isCanceled()) {
            $status = $this->checkForRefund($paymentObject);

            if ($status !== self::INVALID_STATUS) {
                return $status;
            }

            throw new StatusMapperException(InvoiceGuaranteed::getResourceName());
        }

        if (count($paymentObject->getShipments()) > 0) {
            $status = $this->checkForShipment($paymentObject);

            if ($status !== self::INVALID_STATUS) {
                return $status;
            }
        }

        return $this->mapPaymentStatus($paymentObject);
    }
}
