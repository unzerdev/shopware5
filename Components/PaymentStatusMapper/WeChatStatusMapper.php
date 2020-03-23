<?php

declare(strict_types=1);

namespace HeidelPayment\Components\PaymentStatusMapper;

use HeidelPayment\Components\PaymentStatusMapper\Exception\StatusMapperException;
use heidelpayPHP\Resources\Payment;
use heidelpayPHP\Resources\PaymentTypes\BasePaymentType;
use heidelpayPHP\Resources\PaymentTypes\Wechatpay;

class WeChatStatusMapper extends AbstractStatusMapper implements StatusMapperInterface
{
    public function supports(BasePaymentType $paymentType): bool
    {
        return $paymentType instanceof Wechatpay;
    }

    public function getTargetPaymentStatus(Payment $paymentObject): int
    {
        if ($paymentObject->isPending()) {
            throw new StatusMapperException(Wechatpay::getResourceName());
        }

        if ($paymentObject->isCanceled()) {
            $status = $this->mapRefundStatus($paymentObject);

            if ($status !== 0) {
                return $status;
            }

            throw new StatusMapperException(Wechatpay::getResourceName());
        }

        return $this->mapPaymentStatus($paymentObject);
    }
}
