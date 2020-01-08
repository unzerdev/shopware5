<?php

namespace HeidelPayment\Services\Heidelpay\ResourceHydrators;

use Doctrine\DBAL\Connection;
use HeidelPayment\Services\Heidelpay\HeidelpayResourceHydratorInterface;
use heidelpayPHP\Constants\CompanyCommercialSectorItems;
use heidelpayPHP\Constants\CompanyRegistrationTypes;
use heidelpayPHP\Heidelpay;
use heidelpayPHP\Resources\AbstractHeidelpayResource;
use heidelpayPHP\Resources\Customer;
use heidelpayPHP\Resources\CustomerFactory;
use heidelpayPHP\Resources\EmbeddedResources\Address;
use heidelpayPHP\Resources\EmbeddedResources\CompanyInfo;

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
        $user            = $data['additional']['user'];
        $billingAddress  = $this->getHeidelpayAddress($data['billingaddress']);
        $shippingAddress = $this->getHeidelpayAddress($data['shippingaddress']);
        $vatId           = $data['billingaddress']['vatId'] ?: $data['shippingaddress']['vatId'] ?: null;

        return $this->createNotRegisteredB2bCustomer(
            $user['salutation'],
            $user['firstname'],
            $user['lastname'],
            (string) $user['birthday'],
            $billingAddress,
            $shippingAddress,
            $user['email'],
            $data['billingaddress']['company'],
            $vatId
        );
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

    /**
     * @return bool|string
     */
    private function getCountryIso(int $countryId)
    {
        return $this->connection->createQueryBuilder()
            ->select('countryiso')
            ->from('s_core_countries')
            ->where('id = :countryId')
            ->setParameter('countryId', $countryId)
            ->execute()->fetchColumn();
    }

    /**
     * Added to provide a shipping address which is needed for the form generation
     *
     * @see CustomerFactory::createNotRegisteredB2bCustomer()
     */
    private function createNotRegisteredB2bCustomer(
        string $salutation,
        string $firstname,
        string $lastname,
        string $birthDate,
        Address $billingAddress,
        Address $shippingAddress,
        string $email,
        string $company,
        ?string $commercialRegisterNumber = null,
        string $commercialSector = CompanyCommercialSectorItems::OTHER
    ): Customer {
        $companyInfo = (new CompanyInfo())
            ->setCommercialRegisterNumber($commercialRegisterNumber)
            ->setRegistrationType(CompanyRegistrationTypes::REGISTRATION_TYPE_NOT_REGISTERED)
            ->setFunction('OWNER')
            ->setCommercialSector($commercialSector);

        return (new Customer())
            ->setSalutation($salutation)
            ->setFirstname($firstname)
            ->setLastname($lastname)
            ->setBirthDate($birthDate)
            ->setBillingAddress($billingAddress)
            ->setShippingAddress($shippingAddress)
            ->setEmail($email)
            ->setCompany($company)
            ->setCompanyInfo($companyInfo);
    }
}
