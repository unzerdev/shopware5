<?php

declare(strict_types=1);

namespace HeidelPayment\Services\Heidelpay\ResourceHydrators;

use Doctrine\DBAL\Connection;
use HeidelPayment\Services\Heidelpay\HeidelpayResourceHydratorInterface;
use heidelpayPHP\Heidelpay;
use heidelpayPHP\Resources\AbstractHeidelpayResource;
use heidelpayPHP\Resources\Customer;
use heidelpayPHP\Resources\CustomerFactory;
use heidelpayPHP\Resources\EmbeddedResources\Address;

class BusinessCustomerHydrator implements HeidelpayResourceHydratorInterface
{
    /** @var Connection */
    private $connection;

    public function __construct(Connection $dbalConnection)
    {
        $this->connection = $dbalConnection;
    }

    /**
     * {@inheritdoc}
     *
     * @return Customer
     */
    public function hydrateOrFetch(
        array $data,
        Heidelpay $heidelpayObj,
        string $resourceId = null
    ): AbstractHeidelpayResource {
        $user  = $data['additional']['user'];
        $vatId = $data['billingaddress']['vatId'] ?: $data['shippingaddress']['vatId'] ?: null;

        $customer = CustomerFactory::createNotRegisteredB2bCustomer(
            $user['firstname'],
            $user['lastname'],
            (string) $user['birthday'],
            $this->getHeidelpayAddress($data['billingaddress']),
            $user['email'],
            $data['billingaddress']['company']
        );

        /** Workaround due to the js which uses the shippingaddress for field pre-fill */
        $customer->setSalutation($user['salutation']);
        $customer->setShippingAddress($this->getHeidelpayAddress($data['shippingaddress']));
        $customer->getCompanyInfo()->setCommercialRegisterNumber($vatId);

        return $customer;
    }

    private function getHeidelpayAddress(array $shopwareAddress): Address
    {
        $result = new Address();
        $result->setName(sprintf('%s %s', $shopwareAddress['firstname'], $shopwareAddress['lastname']));
        $result->setCity($shopwareAddress['city']);
        $result->setCountry($this->getCountryIso($shopwareAddress['countryID']));
        $result->setState($shopwareAddress['state']);
        $result->setStreet($shopwareAddress['street']);
        $result->setZip($shopwareAddress['zipcode']);

        return $result;
    }

    private function getCountryIso(int $countryId): ?string
    {
        $countryIso = $this->connection->createQueryBuilder()
            ->select('countryiso')
            ->from('s_core_countries')
            ->where('id = :countryId')
            ->setParameter('countryId', $countryId)
            ->execute()->fetchColumn();

        return $countryIso ?: null;
    }
}
