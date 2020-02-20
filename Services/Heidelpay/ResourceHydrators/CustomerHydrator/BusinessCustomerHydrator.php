<?php

declare(strict_types=1);

namespace HeidelPayment\Services\Heidelpay\ResourceHydrators\CustomerHydrator;

use HeidelPayment\Services\Heidelpay\HeidelpayResourceHydratorInterface;
use heidelpayPHP\Heidelpay;
use heidelpayPHP\Resources\AbstractHeidelpayResource;
use heidelpayPHP\Resources\Customer;
use heidelpayPHP\Resources\CustomerFactory;

class BusinessCustomerHydrator extends AbstractCustomerHydrator implements HeidelpayResourceHydratorInterface
{
    /**
     * {@inheritdoc}
     *
     * @return Customer
     */
    public function hydrateOrFetch(
        array $data,
        Heidelpay $heidelpayObj = null,
        string $resourceId = null
    ): AbstractHeidelpayResource {
        $user            = $data['additional']['user'];
        $shippingAddress = $data['shippingaddress'];
        $billingAddress  = $data['billingaddress'];

        $customer = CustomerFactory::createNotRegisteredB2bCustomer(
            $billingAddress['firstname'],
            $billingAddress['lastname'],
            (string) $user['birthday'],
            $this->getHeidelpayAddress($billingAddress),
            $user['email'],
            $billingAddress['company']
        );

        $customer->setSalutation($this->getSalutation($shippingAddress['salutation'] ?: $user['salutation']));
        /** Workaround due to the js which uses the shippingaddress for field pre-fill */
        $customer->setShippingAddress($this->getHeidelpayAddress($shippingAddress));

        return $customer;
    }
}
