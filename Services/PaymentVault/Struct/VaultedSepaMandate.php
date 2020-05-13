<?php

declare(strict_types=1);

namespace HeidelPayment\Services\PaymentVault\Struct;

class VaultedSepaMandate extends VaultedDeviceStruct
{
    /** @var string */
    private $iban;

    public function getIban(): string
    {
        return $this->iban;
    }

    public function setIban(string $iban): self
    {
        $this->iban = $iban;

        return $this;
    }

    public function fromArray(array $data): void
    {
        parent::fromArray($data);

        $deviceData = json_decode($data['data'], true);
        $this->setIban($deviceData['iban']);
    }
}