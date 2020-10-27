<?php

declare(strict_types=1);

namespace UnzerPayment\Services\AddressHashGenerator;

interface AddressHashGeneratorInterface
{
    public function generateHash(array $billingAddress, array $shippingAddress): string;
}
