<?php

declare(strict_types=1);

namespace UnzerPayment\Components\Hydrator\ResourceHydrator\CustomerHydrator;

use UnzerPayment\Components\Hydrator\ResourceHydrator\ResourceHydratorInterface;
use UnzerSDK\Exceptions\UnzerApiException;
use UnzerSDK\Resources\AbstractUnzerResource;
use UnzerSDK\Resources\Customer;
use UnzerSDK\Unzer;

class PrivateCustomerHydrator extends AbstractCustomerHydrator implements ResourceHydratorInterface
{
    /**
     * {@inheritdoc}
     *
     * @return Customer
     */
    public function hydrateOrFetch(
        array $data,
        Unzer $unzerPaymentInstance = null,
        string $resourceId = null
    ): AbstractUnzerResource {
        $result          = new Customer();
        $user            = $data['additional']['user'];
        $shippingAddress = $data['shippingaddress'];
        $billingAddress  = $data['billingaddress'];

        $phoneNumber = \preg_replace(self::PHONE_NUMBER_REGEX, '', $billingAddress['phone'] ?? '');

        try {
            if ($unzerPaymentInstance) {
                $result = $unzerPaymentInstance->fetchCustomerByExtCustomerId($resourceId);
            }
        } catch (UnzerApiException $ex) {
            //Customer not found. No need to handle this exception here,
            //because it's being created below
        }

        $result->setCompany($billingAddress['company']);
        $result->setFirstname($billingAddress['firstname']);
        $result->setLastname($billingAddress['lastname']);
        $result->setBirthDate((string) $user['birthday']);
        $result->setEmail($user['email']);
        $result->setSalutation($this->getSalutation($billingAddress['salutation']));
        $result->setCustomerId($user['customernumber']);
        $result->setPhone($phoneNumber);

        $result->setBillingAddress($this->getUnzerPaymentAddress($billingAddress));
        $result->setShippingAddress($this->getUnzerPaymentAddress($shippingAddress));

        return $result;
    }
}
