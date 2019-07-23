<?php

namespace HeidelPayment\Services;

interface DependencyProviderServiceInterface
{
    /**
     * @param string $name
     *
     * @return mixed
     */
    public function getModule(string $name);
}
