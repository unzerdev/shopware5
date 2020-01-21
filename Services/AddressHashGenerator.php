<?php

declare(strict_types=1);

namespace HeidelPayment\Services;

class AddressHashGenerator implements AddressHashGeneratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function generateHash(array $billingAddress, array $shippingAddress): string
    {
        $data = [
            'billing' => [
                $billingAddress['company'],
                $billingAddress['firstname'],
                $billingAddress['salutation'],
                $billingAddress['title'],
                $billingAddress['lastname'],
                $billingAddress['street'],
                $billingAddress['zipcode'],
                $billingAddress['city'],
                $billingAddress['countryId'],
            ],
            'shipping' => [
                $shippingAddress['company'],
                $shippingAddress['firstname'],
                $shippingAddress['salutation'],
                $shippingAddress['title'],
                $shippingAddress['lastname'],
                $shippingAddress['street'],
                $shippingAddress['zipcode'],
                $shippingAddress['city'],
                $shippingAddress['countryId'],
            ],
        ];

        return md5(serialize($data));
    }
}
