<?php

declare(strict_types=1);

namespace HeidelPayment\Services\PaymentVault;

use HeidelPayment\Services\PaymentVault\Struct\VaultedDeviceStruct;

interface PaymentDeviceFactoryInterface
{
    public function getPaymentDevice(array $deviceData): VaultedDeviceStruct;
}
