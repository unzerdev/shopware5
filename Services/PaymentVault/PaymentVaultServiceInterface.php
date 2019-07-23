<?php

namespace HeidelPayment\Services\PaymentVault;

use HeidelPayment\Services\PaymentVault\Struct\VaultedDeviceStruct;
use heidelpayPHP\Resources\PaymentTypes\BasePaymentType;

interface PaymentVaultServiceInterface
{
    /**
     * @return VaultedDeviceStruct[]
     */
    public function getVaultedDevicesForCurrentUser(): array;

    public function saveDeviceToVault(BasePaymentType $paymentType, string $deviceType): void;

    public function deleteDeviceFromVault(int $userId, int $vaultId): void;
}
