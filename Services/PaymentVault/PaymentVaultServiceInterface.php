<?php

declare(strict_types=1);

namespace UnzerPayment\Services\PaymentVault;

use UnzerPayment\Services\PaymentVault\Struct\VaultedDeviceStruct;
use UnzerSDK\Resources\PaymentTypes\BasePaymentType;

interface PaymentVaultServiceInterface
{
    /**
     * @return VaultedDeviceStruct[]
     */
    public function getVaultedDevicesForCurrentUser(array $billingAddress, array $shippingAddress): array;

    public function deviceExists(BasePaymentType $paymentType): bool;

    public function saveDeviceToVault(BasePaymentType $paymentType, string $deviceType, array $billingAddress, array $shippingAddress, array $additionalData = []): void;

    public function deleteDeviceFromVault(int $userId, int $vaultId): void;

    public function hasVaultedSepaMandate(int $userId, string $iban, array $billingAddress, array $shippingAddress): bool;

    public function hasVaultedSepaGuaranteedMandate(int $userId, string $iban, array $billingAddress, array $shippingAddress): bool;
}
