<?php

declare(strict_types=1);

namespace HeidelPayment\Components\PaymentStatusMapper;

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
        if ($paymentObject->isCanceled() || $paymentObject->isPending()) {
            throw new StatusMapperException($paymentObject->getPaymentType()::getResourceName());
        }

        return $this->mapPaymentStatus($paymentObject);
    }
}
