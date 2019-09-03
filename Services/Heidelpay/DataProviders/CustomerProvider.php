<?php

namespace HeidelPayment\Services\Heidelpay\DataProviders;

use Doctrine\DBAL\Connection;
use HeidelPayment\Services\Heidelpay\DataProviderInterface;
use heidelpayPHP\Constants\Salutations;
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
        $result->setSalutation($this->getSalutation($data['billingaddress']['salutation']));
        $result->setCustomerId($data['additional']['user']['customernumber']);
        $result->setPhone($data['billingaddress']['phone']);

        $result->setBillingAddress($this->getHeidelpayAddress($data['billingaddress']));
        $result->setShippingAddress($this->getHeidelpayAddress($data['shippingaddress']));

        return $result;
    }

    private function getHeidelpayAddress(array $shopwareAddress): Address
    {
        $result = new Address();
        $result->setName(sprintf('%s %s', $shopwareAddress['firstname'], $shopwareAddress['lastname']));
        $result->setCity($shopwareAddress['city']);
        $result->setCountry($this->getCountryIso($shopwareAddress['countryId']));
        $result->setState($shopwareAddress['state']);
        $result->setStreet($shopwareAddress['street']);
        $result->setZip($shopwareAddress['zipcode']);

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

    private function getSalutation(string $salutation): string
    {
        switch ($salutation) {
            case 'ms':
            case 'mrs':
                return Salutations::MRS;
            case 'mr':
                return Salutations::MR;
            default:
                return Salutations::UNKNOWN;
        }
    }
}
