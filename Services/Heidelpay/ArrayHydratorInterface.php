<?php

declare(strict_types=1);

namespace HeidelPayment\Services\Heidelpay;

use heidelpayPHP\Resources\AbstractHeidelpayResource;

interface ArrayHydratorInterface
{
    public function hydrateArray(AbstractHeidelpayResource $resource): array;
}
