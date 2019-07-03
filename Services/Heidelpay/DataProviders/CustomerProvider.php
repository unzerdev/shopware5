<?php

namespace HeidelPayment\Services\Heidelpay\DataProviders;

use Doctrine\DBAL\Connection;
use HeidelPayment\Services\Heidelpay\DataProviderInterface;
use heidelpayPHP\Exceptions\HeidelpayApiException;
use heidelpayPHP\Heidelpay;
use heidelpayPHP\Resources\AbstractHeidelpayResource;
use heidelpayPHP\Resources\Customer;
use heidelpayPHP\Resources\EmbeddedResources\Address;

class CustomerProvider implements DataProviderInterface
{
    /** @var Connection */
    private $connection;

    public function __construct(Connection $dbalConnection)
    {
        $this->connection = $dbalConnection;
    }

    public function hydrateOrFetch(
        array $data,
        Heidelpay $heidelpayObj,
        string $resourceId = null
    ): AbstractHeidelpayResource {
        $result = new Customer();

        try {
            $result = $heidelpayObj->fetchCustomerByExtCustomerId($resourceId);
        } catch (HeidelpayApiException $ex) {
            //Customer not found. No need to handle this exception here,
            //because it's being created below
        }

        $result->setCompany($data['billingaddress']['company']);
        $result->setFirstname($data['billingaddress']['firstname']);
        $result->setLastname($data['billingaddress']['lastname']);
        $result->setBirthDate($data['additional']['user']['birthday']);
        $result->setEmail($data['additional']['user']['email']);
        $result->setSalutation($data['billingaddress']['salutation']);
        $result->setCustomerId($data['additional']['user']['customernumber']);
        $result->setPhone($data['billingaddress']['phone']);

        $result->setBillingAddress($this->getBillingAddress($data['billingaddress']));
        $result->setShippingAddress($this->getShippingAddress($data['shippingaddress']));

        return $result;
    }

    private function getBillingAddress(array $billingAddress): Address
    {
        $result = new Address();
        $result->setName(sprintf('%s %s', $billingAddress['firstname'], $billingAddress['lastname']));
        $result->setCity($billingAddress['city']);
        $result->setCountry($this->getCountryIso($billingAddress['countryID']));
        $result->setState($billingAddress['state']);
        $result->setStreet($billingAddress['street']);
        $result->setZip($billingAddress['zipcode']);

        //TODO: CHECK FOR REMOVAL!! This is just for the iDEAL integration
        $result->setCountry('NL');

        return $result;
    }

    private function getShippingAddress(array $billingAddress): Address
    {
        $result = new Address();
        $result->setName(sprintf('%s %s', $billingAddress['firstname'], $billingAddress['lastname']));
        $result->setCity($billingAddress['city']);
        $result->setCountry($this->getCountryIso($billingAddress['countryId']));
        $result->setState($billingAddress['state']);
        $result->setStreet($billingAddress['street']);
        $result->setZip($billingAddress['zipcode']);

        //TODO: CHECK FOR REMOVAL!! This is just for the iDEAL integration
        $result->setCountry('NL');

        return $result;
    }

    private function getCountryIso(int $countryId)
    {
        return $this->connection->createQueryBuilder()
            ->select('countryiso')
            ->from('s_core_countries')
            ->where('id = :countryId')
            ->setParameter('countryId', $countryId)
            ->execute()->fetchColumn();
    }
}
