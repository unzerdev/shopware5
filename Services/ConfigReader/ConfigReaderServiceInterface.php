<?php

declare(strict_types=1);

namespace HeidelPayment\Services\ConfigReader;

interface ConfigReaderServiceInterface
{
    public function get(string $key = null, int $shopId = null);
}
