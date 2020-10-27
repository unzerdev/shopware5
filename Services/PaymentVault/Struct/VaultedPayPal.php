<?php

declare(strict_types=1);

namespace UnzerPayment\Services\PaymentVault\Struct;

class VaultedPayPal extends VaultedDeviceStruct
{
    /** @var string */
    private $email = '';

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function fromArray(array $data): void
    {
        parent::fromArray($data);

        $deviceData = json_decode($data['data'], true);

        if (array_key_exists('email', $deviceData) && !empty($deviceData['email'])) {
            $this->setEmail($deviceData['email']);
        }
    }
}
