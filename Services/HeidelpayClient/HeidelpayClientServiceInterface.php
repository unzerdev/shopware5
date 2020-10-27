<?php

declare(strict_types=1);

namespace UnzerPayment\Services\HeidelpayClient;

use heidelpayPHP\Heidelpay;

interface HeidelpayClientServiceInterface
{
    public function getHeidelpayClient(): ?Heidelpay;

    public function getPrivateKey(): string;

    public function getPublicKey(): string;
}
