<?php

declare(strict_types=1);

namespace HeidelPayment\Components\ResourceMapper;

use heidelpayPHP\Resources\AbstractHeidelpayResource;

interface ResourceMapperInterface
{
    public function mapMissingFields(AbstractHeidelpayResource $leadingResource, AbstractHeidelpayResource $fallbackResource): AbstractHeidelpayResource;
}
