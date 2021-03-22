<?php

declare(strict_types=1);

namespace UnzerPayment\Components\ResourceMapper;

use UnzerSDK\Resources\AbstractUnzerResource;

interface ResourceMapperInterface
{
    public function mapMissingFields(AbstractUnzerResource $leadingResource, AbstractUnzerResource $fallbackResource): AbstractUnzerResource;
}
