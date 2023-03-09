<?php

declare(strict_types=1);

namespace UnzerPayment\Components;

use UnzerSDK\Constants\CompanyTypes as UnzerCompanyTypes;

final class CompanyTypes
{
    static function getConstants(): array
    {
        return (new \ReflectionClass(UnzerCompanyTypes::class))->getConstants();
    }
}
