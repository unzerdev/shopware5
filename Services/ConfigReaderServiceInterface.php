<?php

declare(strict_types=1);

namespace HeidelPayment\Services;

interface ConfigReaderServiceInterface
{
    public function get(string $key = null, int $shopId = null);
}
