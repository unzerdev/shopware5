<?php

declare(strict_types=1);

namespace UnzerPayment\Services\PaymentVault\Struct;

class VaultedCreditCard extends VaultedDeviceStruct
{
    private string $cvc;

    private string $expiryDate;

    private string $holder;

    private string $number;

    public function getCvc(): string
    {
        return $this->cvc;
    }

    public function setCvc(string $cvc): self
    {
        $this->cvc = $cvc;

        return $this;
    }

    public function getExpiryDate(): string
    {
        return $this->expiryDate;
    }

    public function setExpiryDate(string $expiryDate): self
    {
        $this->expiryDate = $expiryDate;

        return $this;
    }

    public function getHolder(): string
    {
        return $this->holder;
    }

    public function setHolder(string $holder): self
    {
        $this->holder = $holder;

        return $this;
    }

    public function getNumber(): string
    {
        return $this->number;
    }

    public function setNumber(string $number): self
    {
        $this->number = $number;

        return $this;
    }

    public function fromArray(array $data): void
    {
        parent::fromArray($data);

        $deviceData = json_decode($data['data'], true);

        $this->setCvc(
            array_key_exists('cvc', $deviceData) && !empty($deviceData['cvc']) ? $deviceData['cvc'] : ''
        );

        $this->setExpiryDate(
            array_key_exists('expiryDate', $deviceData) && !empty($deviceData['expiryDate']) ? $deviceData['expiryDate'] : ''
        );

        $this->setHolder(
            array_key_exists('cardHolder', $deviceData) && !empty($deviceData['cardHolder']) ? $deviceData['cardHolder'] : ''
        );

        $this->setNumber(
            array_key_exists('number', $deviceData) && !empty($deviceData['number']) ? $deviceData['number'] : ''
        );
    }
}
