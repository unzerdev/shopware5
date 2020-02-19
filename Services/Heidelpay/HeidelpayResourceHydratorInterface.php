<?php

declare(strict_types=1);

namespace HeidelPaymentTest\Services\Heidelpay;

use heidelpayPHP\Heidelpay;
use heidelpayPHP\Resources\AbstractHeidelpayResource;

interface HeidelpayResourceHydratorInterface
{
    /**
     * Will create a customer object from provided data inside the array
     */
    public function hydrateOrFetch(array $data, Heidelpay $heidelpayObj = null, string $resourceId = null): AbstractHeidelpayResource;
}
