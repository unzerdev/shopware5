<?php

declare(strict_types=1);

namespace UnzerPayment\Components\DependencyInjection\Factory\StatusMapper;

use UnzerPayment\Components\PaymentStatusMapper\Exception\NoStatusMapperFoundException;
use UnzerPayment\Components\PaymentStatusMapper\StatusMapperInterface;
use heidelpayPHP\Resources\PaymentTypes\BasePaymentType;

interface PaymentStatusMapperFactoryInterface
{
    /**
     * @throws NoStatusMapperFoundException
     */
    public function getStatusMapper(?BasePaymentType $paymentType): ?StatusMapperInterface;

    public function addStatusMapper(StatusMapperInterface $paymentValidator): void;
}
