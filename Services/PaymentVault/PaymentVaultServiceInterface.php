<?php

namespace HeidelPayment\Services\PaymentVault;

use HeidelPayment\Services\PaymentVault\Struct\VaultedDeviceStruct;
use heidelpayPHP\Resources\PaymentTypes\BasePaymentType;

interface PaymentVaultServiceInterface
{
    /**
     * @return VaultedDeviceStruct[]
     */
    public function getVaultedDevicesForCurrentUser(array $billingAddress, array $shippingAddress): array;

    public function saveDeviceToVault(BasePaymentType $paymentType, string $deviceType, array $billingAddress, array $shippingAddress): void;

    public function deleteDeviceFromVault(int $userId, int $vaultId): void;

    public function hasVaultedSepaMandate(int $userId, string $iban, array $billingAddress, array $shippingAddress): bool;
}
