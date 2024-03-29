<?php

declare(strict_types=1);

namespace UnzerPayment\Components\DependencyInjection\Factory\StatusMapper;

use UnzerPayment\Components\PaymentStatusMapper\Exception\NoStatusMapperFoundException;
use UnzerPayment\Components\PaymentStatusMapper\StatusMapperInterface;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;

class PaymentStatusMapperFactory implements PaymentStatusMapperFactoryInterface
{
    /** @var StatusMapperInterface[] */
    protected array $statusMapperCollection;

    /**
     * {@inheritdoc}
     */
    public function getStatusMapper(?BasePaymentType $paymentType): StatusMapperInterface
    {
        foreach ($this->statusMapperCollection as $statusMapper) {
            if ($statusMapper !== null && $paymentType !== null && $statusMapper->supports($paymentType)) {
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
