<?php

declare(strict_types=1);

namespace HeidelPayment\Components\DependencyInjection\Factory\StatusMapper;

use HeidelPayment\Components\PaymentStatusMapper\StatusMapperInterface;
use heidelpayPHP\Resources\PaymentTypes\BasePaymentType;

interface PaymentStatusMapperFactoryInterface
{
    public function getStatusMapper(BasePaymentType $paymentType): ?StatusMapperInterface;

    public function addStatusMapper(StatusMapperInterface $paymentValidator): void;
}
