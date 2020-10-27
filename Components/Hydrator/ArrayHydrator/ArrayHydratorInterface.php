<?php

declare(strict_types=1);

namespace UnzerPayment\Components\Hydrator\ArrayHydrator;

use heidelpayPHP\Resources\AbstractHeidelpayResource;

interface ArrayHydratorInterface
{
    public function hydrateArray(AbstractHeidelpayResource $resource): array;
}
