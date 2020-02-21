<?php

declare(strict_types=1);

namespace HeidelPayment\Components\DependencyInjection\Factory\StatusMapper;

use HeidelPayment\Components\PaymentStatusMapper\Exception\NoStatusMapperFoundException;
use HeidelPayment\Components\PaymentStatusMapper\StatusMapperInterface;
use heidelpayPHP\Resources\PaymentTypes\BasePaymentType;

class PaymentStatusMapperFactory implements PaymentStatusMapperFactoryInterface
{
    /** @var StatusMapperInterface[] */
    protected $paymentValidator;

    public function getStatusMapper(BasePaymentType $paymentType): ?StatusMapperInterface
    {
        foreach ($this->paymentValidator as $validator) {
            if ($validator->supports($paymentType)) {
                return $validator;
            }
        }

        throw new NoStatusMapperFoundException($paymentType::getResourceName());
    }

    public function addStatusMapper(StatusMapperInterface $paymentValidator): void
    {
        $this->paymentValidator[] = $paymentValidator;
    }
}
