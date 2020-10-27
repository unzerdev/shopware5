<?php

declare(strict_types=1);

namespace UnzerPayment\Components\WebhookHandler\Struct;

class WebhookStruct
{
    /** @var string */
    private $event;

    /** @var string */
    private $publicKey;

    /** @var string */
    private $retrieveUrl;

    public function __construct(string $jsonData)
    {
        if ($jsonData) {
            $this->fromJson($jsonData);
        }
    }

    public function fromJson(string $jsonData): void
    {
        $webhookData = json_decode($jsonData, true);

        $this->setEvent($webhookData['event']);
        $this->setPublicKey($webhookData['publicKey']);
        $this->setRetrieveUrl($webhookData['retrieveUrl']);
    }

    public function toJson(): string
    {
        return json_encode(
            [
                'event'       => $this->getEvent(),
                'publicKey'   => $this->getPublicKey(),
                'retrieveUrl' => $this->getRetrieveUrl(),
            ]
        );
    }

    public function getEvent(): string
    {
        return $this->event;
    }

    public function setEvent(string $event): self
    {
        $this->event = $event;

        return $this;
    }

    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    public function setPublicKey(string $publicKey): self
    {
        $this->publicKey = $publicKey;

        return $this;
    }

    public function getRetrieveUrl(): string
    {
        return $this->retrieveUrl;
    }

    public function setRetrieveUrl(string $retrieveUrl): self
    {
        $this->retrieveUrl = $retrieveUrl;

        return $this;
    }
}
