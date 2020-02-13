<?php

declare(strict_types=1);

namespace HeidelPayment\Services\PaymentVault;

interface PaymentDeviceFactoryInterface
{
    public function getPaymentDevice(array $deviceData);
}
