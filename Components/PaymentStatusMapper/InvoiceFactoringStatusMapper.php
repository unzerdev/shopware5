<?php

declare(strict_types=1);

namespace UnzerPayment\Components\PaymentStatusMapper;

use UnzerPayment\Components\PaymentStatusMapper\Exception\StatusMapperException;
use UnzerSDK\Resources\Payment;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;
use UnzerSDK\Resources\PaymentTypes\InvoiceFactoring;

class InvoiceFactoringStatusMapper extends AbstractStatusMapper implements StatusMapperInterface
{
    public function supports(BasePaymentType $paymentType): bool
    {
        return $paymentType instanceof InvoiceFactoring;
    }

    public function getTargetPaymentStatus(Payment $paymentObject): int
    {
        if ($paymentObject->isCanceled()) {
            $status = $this->checkForRefund($paymentObject);

            if ($status !== self::INVALID_STATUS) {
                return $status;
            }

            throw new StatusMapperException(InvoiceFactoring::getResourceName());
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
