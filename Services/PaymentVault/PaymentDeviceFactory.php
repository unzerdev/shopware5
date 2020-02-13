<?php

declare(strict_types=1);

namespace HeidelPayment\Services\PaymentVault;

use HeidelPayment\Services\PaymentVault\Struct\VaultedCreditCard;
use HeidelPayment\Services\PaymentVault\Struct\VaultedDeviceStruct;
use HeidelPayment\Services\PaymentVault\Struct\VaultedSepaMandate;
use Symfony\Component\Serializer\Exception\UnsupportedException;

class PaymentDeviceFactory implements PaymentDeviceFactoryInterface
{
    public function getPaymentDevice(array $deviceData)
    {
        switch ($deviceData['device_type']) {
            case VaultedDeviceStruct::DEVICE_TYPE_CARD:
                $creditCard = new VaultedCreditCard();
                $creditCard->fromArray($deviceData);

                return $creditCard;
            case VaultedDeviceStruct::DEVICE_TYPE_SEPA_MANDATE:
            case VaultedDeviceStruct::DEVICE_TYPE_SEPA_MANDATE_GUARANTEED:
                $sepaMandate = new VaultedSepaMandate();
                $sepaMandate->fromArray($deviceData);

                return $sepaMandate;
            default:
                throw new UnsupportedException('This device is not supported!');
        }
    }
}
