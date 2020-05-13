<?php

declare(strict_types=1);

namespace HeidelPayment\Services\AddressHashGenerator;

interface AddressHashGeneratorInterface
{
    public function generateHash(array $billingAddress, array $shippingAddress): string;
}
