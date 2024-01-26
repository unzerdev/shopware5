<?php

declare(strict_types=1);

namespace UnzerPayment\Services\UnzerPaymentClient;

use UnzerSDK\Unzer;

interface UnzerPaymentClientServiceInterface
{
    public function getUnzerPaymentClientByType(string $keypairType, ?string $locale = null, ?int $shopId = null): ?Unzer;

    public function getUnzerPaymentClientByPublicKey(string $publicKey): ?Unzer;

    public function getUnzerPaymentClientByPaymentId(string $paymentId, ?string $locale = null): ?Unzer;

    public function getGeneralUnzerPaymentClient(?string $locale = null, ?int $shopId = null): ?Unzer;

    /**
     * @return null|Unzer[]
     */
    public function getExistingKeypairClients(?string $locale = null, ?int $shopId = null): ?array;

    public function getPrivateKeyByType(string $keypairType, ?int $shopId = null): ?string;

    public function getKeypairType(string $paymentName, string $currency, bool $isB2b): string;

    public function getPrivateKey(?int $shopId = null, string $configKey = 'private_key'): string;

    public function getPublicKey(?int $shopId = null): string;
}
