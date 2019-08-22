<?php

namespace HeidelPayment\Services\PaymentVault;

interface PaymentDeviceFactoryInterface
{
    public function getPaymentDevice(array $deviceData);
}
