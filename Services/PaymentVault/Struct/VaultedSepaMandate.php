<?php

declare(strict_types=1);

namespace UnzerPayment\Services\PaymentVault\Struct;

class VaultedSepaMandate extends VaultedDeviceStruct
{
    /** @var string */
    private $iban;

    /** @var null|string */
    private $birthDate;

    public function getIban(): string
    {
        return $this->iban;
    }

    public function setIban(string $iban): self
    {
        $this->iban = $iban;

        return $this;
    }

    public function getBirthDate(): ?string
    {
        return $this->birthDate;
    }

    public function setBirthDate(?string $birthDate): void
    {
        $this->birthDate = $birthDate;
    }

    public function fromArray(array $data): void
    {
        parent::fromArray($data);

        $deviceData = json_decode($data['data'], true);

        $this->setIban(array_key_exists('iban', $deviceData) && !empty($deviceData['iban']) ? $deviceData['iban'] : '');
        $this->setBirthDate(array_key_exists('birthDate', $deviceData) && !empty($deviceData['birthDate']) ? $deviceData['birthDate'] : '');
    }
}
