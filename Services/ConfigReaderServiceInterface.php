<?php

namespace HeidelPayment\Services;

interface ConfigReaderServiceInterface
{
    public function get(?string $key = null);
}
