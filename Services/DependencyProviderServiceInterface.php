<?php

namespace HeidelPayment\Services;

use Enlight_Components_Session_Namespace;

interface DependencyProviderServiceInterface
{
    public function getSession(): Enlight_Components_Session_Namespace;

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function getModule(string $name);
}
