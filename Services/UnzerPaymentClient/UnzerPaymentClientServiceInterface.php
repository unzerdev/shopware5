<?php

declare(strict_types=1);

namespace UnzerPayment\Services\UnzerPaymentClient;

use UnzerSDK\Unzer;

interface UnzerPaymentClientServiceInterface
{
    public function getUnzerPaymentClient(): ?Unzer;

    public function getPrivateKey(): string;

    public function getPublicKey(): string;
}
