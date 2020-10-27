<?php

declare(strict_types=1);

namespace UnzerPayment\Services\PaymentVault;

use UnzerPayment\Services\PaymentVault\Struct\VaultedDeviceStruct;

interface PaymentDeviceFactoryInterface
{
    public function getPaymentDevice(array $deviceData): VaultedDeviceStruct;
}
