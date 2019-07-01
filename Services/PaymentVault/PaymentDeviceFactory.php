<?php

namespace HeidelPayment\Services\PaymentVault;

use HeidelPayment\Services\PaymentVault\Struct\VaultedCreditCard;
use HeidelPayment\Services\PaymentVault\Struct\VaultedDeviceStruct;
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

            default:
                throw new UnsupportedException('This device is not supported!');
        }
    }
}
