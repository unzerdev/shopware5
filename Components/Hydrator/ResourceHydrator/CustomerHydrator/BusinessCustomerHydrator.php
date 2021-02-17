<?php

declare(strict_types=1);

namespace HeidelPayment\Components\Hydrator\ResourceHydrator\CustomerHydrator;

use HeidelPayment\Components\Hydrator\ResourceHydrator\ResourceHydratorInterface;
use heidelpayPHP\Heidelpay;
use heidelpayPHP\Resources\AbstractHeidelpayResource;
use heidelpayPHP\Resources\Customer;
use heidelpayPHP\Resources\CustomerFactory;

class BusinessCustomerHydrator extends AbstractCustomerHydrator implements ResourceHydratorInterface
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
        $user                    = $data['additional']['user'];
        $shippingAddress         = $data['shippingaddress'];
        $billingAddress          = $data['billingaddress'];
        $billingAddress['phone'] = \preg_replace(self::PHONE_NUMBER_REGEX, '', $billingAddress['phone']);

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
