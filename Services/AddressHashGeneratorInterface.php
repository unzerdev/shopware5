<?php

namespace HeidelPayment\Services;

interface AddressHashGeneratorInterface
{
    public function generateHash(array $billingAddress, array $shippingAddress): string;
}
