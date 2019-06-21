<?php

namespace HeidelPayment\Services\Heidelpay;

use heidelpayPHP\Heidelpay;
use heidelpayPHP\Resources\AbstractHeidelpayResource;

interface DataProviderInterface
{
    public function hydrateOrFetch(array $data, Heidelpay $heidelpayObj, string $resourceId = null): AbstractHeidelpayResource;
}
