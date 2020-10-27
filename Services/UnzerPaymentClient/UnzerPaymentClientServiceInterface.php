<?php

declare(strict_types=1);

namespace UnzerPayment\Services\UnzerPaymentClient;

use heidelpayPHP\Heidelpay;

interface UnzerPaymentClientServiceInterface
{
    public function getUnzerPaymentClient(): ?Heidelpay;

    public function getPrivateKey(): string;

    public function getPublicKey(): string;
}
