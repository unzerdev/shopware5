<?php

namespace HeidelPayment\Services;

use Enlight_Components_Session_Namespace;

interface DependencyProviderServiceInterface
{
    /**
     * @return null|Enlight_Components_Session_Namespace
     */
    public function getSession();

    public function getModule(string $name);

    public function get(string $name);
}
