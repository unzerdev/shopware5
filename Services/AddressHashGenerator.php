<?php

namespace HeidelPayment\Services;

class AddressHashGenerator implements AddressHashGeneratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function generateHash(array $billingAddress, array $shippingAddress): string
    {
        $data = [
            $billingAddress,
            $shippingAddress,
        ];

        return md5(serialize($data));
    }
}
