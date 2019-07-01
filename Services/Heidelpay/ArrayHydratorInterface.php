<?php

namespace HeidelPayment\Services\Heidelpay;

use heidelpayPHP\Resources\AbstractHeidelpayResource;

interface ArrayHydratorInterface
{
    public function hydrateArray(AbstractHeidelpayResource $resource): array;
}
