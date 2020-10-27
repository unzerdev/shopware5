<?php

declare(strict_types=1);

namespace UnzerPayment\Services\DependencyProvider;

use Enlight_Components_Session_Namespace;

interface DependencyProviderServiceInterface
{
    public function getSession(): ?Enlight_Components_Session_Namespace;

    public function getModule(string $name);

    public function get(string $name);
}
