<?php

declare(strict_types=1);

namespace UnzerPayment\Components\Hydrator\ArrayHydrator;

use UnzerSDK\Resources\AbstractUnzerResource;

interface ArrayHydratorInterface
{
    public function hydrateArray(AbstractUnzerResource $resource): array;
}
