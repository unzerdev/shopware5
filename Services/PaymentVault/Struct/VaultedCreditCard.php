<?php

namespace HeidelPayment\Services\PaymentVault\Struct;

class VaultedCreditCard extends VaultedDeviceStruct
{
    /** @var string */
    private $cvc;

    /** @var string */
    private $expiryDate;

    /** @var string */
    private $holder;

    /** @var string */
    private $number;

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

    public function fromArray(array $data)
    {
        parent::fromArray($data);

        $deviceData = json_decode($data['data'], true);
        $this->setCvc($deviceData['cvc']);
        $this->setExpiryDate($deviceData['expiryDate']);
        $this->setHolder($deviceData['holder']);
        $this->setNumber($deviceData['number']);
    }
}
