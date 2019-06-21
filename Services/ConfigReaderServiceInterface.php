<?php

namespace HeidelPayment\Services;

interface ConfigReaderServiceInterface
{
    /**
     * @param null|string $key
     *
     * @return mixed
     */
    public function get(?string $key = null);
}
