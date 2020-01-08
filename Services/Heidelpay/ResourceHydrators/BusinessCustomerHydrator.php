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
        $user           = $data['additional']['user'];
        $billingAddress = $data['billingaddress'];

        $address = $this->getHeidelpayAddress($billingAddress);

        return CustomerFactory::createNotRegisteredB2bCustomer(
            $user['firstname'],
            $user['lastname'],
            (string) $user['birthday'],
            $address,
            $user['email'],
            $billingAddress['company']
        );
    }

    private function getHeidelpayAddress(array $shopareAddress): Address
    {
        $result = new Address();
        $result->setName(sprintf('%s %s', $shopareAddress['firstname'], $shopareAddress['lastname']));
        $result->setCity($shopareAddress['city']);
        $result->setCountry($this->getCountryIso($shopareAddress['countryID']));
        $result->setState($shopareAddress['state']);
        $result->setStreet($shopareAddress['street']);
        $result->setZip($shopareAddress['zipcode']);

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
