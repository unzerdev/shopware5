<?php

declare(strict_types=1);

namespace UnzerPayment\Components\Hydrator\ResourceHydrator;

use UnzerSDK\Resources\AbstractUnzerResource;
use UnzerSDK\Unzer;

interface ResourceHydratorInterface
{
    /**
     * Will create a customer object from provided data inside the array
     */
    public function hydrateOrFetch(array $data, Unzer $unzerPaymentInstance = null, string $resourceId = null): AbstractUnzerResource;
}
