<?php

declare(strict_types=1);

namespace HeidelPayment\Components\DependencyInjection\Factory\StatusMapper;

use HeidelPayment\Components\PaymentStatusMapper\Exception\NoStatusMapperFoundException;
use HeidelPayment\Components\PaymentStatusMapper\StatusMapperInterface;
use heidelpayPHP\Resources\PaymentTypes\BasePaymentType;

class PaymentStatusMapperFactory implements PaymentStatusMapperFactoryInterface
{
    /** @var StatusMapperInterface[] */
    protected $statusMapperCollection;

    /**
     * {@inheritdoc}
     */
    public function getStatusMapper(?BasePaymentType $paymentType): ?StatusMapperInterface
    {
        foreach ($this->statusMapperCollection as $statusMapper) {
            if (!empty($paymentType) && $statusMapper->supports($paymentType)) {
                return $statusMapper;
            }
        }

        throw new NoStatusMapperFoundException($paymentType::getResourceName());
    }

    public function addStatusMapper(StatusMapperInterface $statusMapper): void
    {
        $this->statusMapperCollection[] = $statusMapper;
    }
}
