<?php

namespace HeidelPayment\Services\PaymentVault\Struct;

class VaultedDeviceStruct
{
    public const DEVICE_TYPE_CARD = 0;

    /** @var int */
    private $id;

    /** @var int */
    private $userId;

    /** @var int */
    private $deviceType;

    /** @var string */
    private $typeId;

    /** @var string */
    private $data;

    /** @var string */
    private $date;

    public function __construct(array $data = null)
    {
        if ($data !== null) {
            $this->fromArray($data);
        }
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    public function getDeviceType(): int
    {
        return $this->deviceType;
    }

    public function setDeviceType(int $deviceType): self
    {
        $this->deviceType = $deviceType;

        return $this;
    }

    public function getTypeId(): string
    {
        return $this->typeId;
    }

    public function setTypeId(string $typeId): self
    {
        $this->typeId = $typeId;

        return $this;
    }

    public function getData(): string
    {
        return $this->data;
    }

    public function setData(string $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function getDate(): string
    {
        return $this->date;
    }

    public function setDate(string $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function fromArray(array $data): void
    {
        $this->setDate($data['date']);
        $this->setData($data['data']);
        $this->setDeviceType($data['device_type']);
        $this->setTypeId($data['type_id']);
        $this->setUserId($data['user_id']);
    }
}
