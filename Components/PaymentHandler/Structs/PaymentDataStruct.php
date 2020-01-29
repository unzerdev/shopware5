<?php

declare(strict_types=1);

namespace HeidelPayment\Components\PaymentHandler\Structs;

use heidelpayPHP\Resources\Basket;
use heidelpayPHP\Resources\Customer;
use heidelpayPHP\Resources\Metadata;

class PaymentDataStruct
{
    /** @var string */
    private $amount;

    /** @var string */
    private $currency;

    /** @var string */
    private $returnUrl;

    /** @var null|Customer */
    private $customer;

    /** @var null|string */
    private $orderId;

    /** @var null|Metadata */
    private $metadata;

    /** @var null|Basket */
    private $basket;

    /** @var null|bool */
    private $card3ds;

    /** @var null|string */
    private $invoiceId;

    /** @var null|string */
    private $paymentReference;

    public function __construct(string $amount, string $currency, string $returnUrl)
    {
        $this->amount    = $amount;
        $this->currency  = $currency;
        $this->returnUrl = $returnUrl;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function getReturnUrl(): string
    {
        return $this->returnUrl;
    }

    public function setReturnUrl(string $returnUrl): self
    {
        $this->returnUrl = $returnUrl;

        return $this;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): self
    {
        $this->customer = $customer;

        return $this;
    }

    public function getOrderId(): ?string
    {
        return $this->orderId;
    }

    public function setOrderId(?string $orderId): self
    {
        $this->orderId = $orderId;

        return $this;
    }

    public function getMetadata(): ?Metadata
    {
        return $this->metadata;
    }

    public function setMetadata(?Metadata $metadata): self
    {
        $this->metadata = $metadata;

        return $this;
    }

    public function getBasket(): ?Basket
    {
        return $this->basket;
    }

    public function setBasket(?Basket $basket): self
    {
        $this->basket = $basket;

        return $this;
    }

    public function getCard3ds(): ?bool
    {
        return $this->card3ds;
    }

    public function setCard3ds(?bool $card3ds): self
    {
        $this->card3ds = $card3ds;

        return $this;
    }

    public function getInvoiceId(): ?string
    {
        return $this->invoiceId;
    }

    public function setInvoiceId(?string $invoiceId): self
    {
        $this->invoiceId = $invoiceId;

        return $this;
    }

    public function getPaymentReference(): ?string
    {
        return $this->paymentReference;
    }

    public function setPaymentReference(?string $paymentReference): self
    {
        $this->paymentReference = $paymentReference;

        return $this;
    }

    public function fromArray(array $data)
    {
        foreach ($data as $key => $value) {
            $setterMethod = 'set' . ucfirst(strtolower($key));

            if (method_exists($this, $setterMethod)) {
                $this->$setterMethod($value);
            }
        }
    }
}
