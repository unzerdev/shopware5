<?php

declare(strict_types=1);

namespace UnzerPayment\Services\UnzerPaymentClient;

use UnzerSDK\Unzer;

interface UnzerPaymentClientServiceInterface
{
    public function getUnzerPaymentClient(?string $locale, ?int $shopId = null): ?Unzer;

    public function getPrivateKey(?int $shopId = null): string;

    public function getPublicKey(?int $shopId = null): string;
}
