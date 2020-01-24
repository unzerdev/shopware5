<?php

declare(strict_types=1);

namespace HeidelPayment\Components\Payment\HeidelPaymentStruct;

use heidelpayPHP\Resources\Basket;
use heidelpayPHP\Resources\Customer;
use heidelpayPHP\Resources\Metadata;

class HeidelPaymentStruct
{
    /** @var float */
    private $amount;

    /** @var string */
    private $currency;

    /** @var string */
    private $returnUrl;

    /** @var null|Customer */
    private $customer = null;

    /** @var null|string */
    private $orderId = null;

    /** @var null|Metadata */
    private $metadata = null;

    /** @var null|Basket */
    private $basket = null;

    /** @var null|bool */
    private $card3ds = null;

    /** @var null|string */
    private $invoiceId = null;

    /** @var null|string */
    private $paymentReference = null;

    public function __construct(float $amount, string $currency, string $returnUrl)
    {
        $this->amount    = $amount;
        $this->currency  = $currency;
        $this->returnUrl = $returnUrl;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): void
    {
        $this->amount = $amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }

    public function getReturnUrl(): string
    {
        return $this->returnUrl;
    }

    public function setReturnUrl(string $returnUrl): void
    {
        $this->returnUrl = $returnUrl;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): void
    {
        $this->customer = $customer;
    }

    public function getOrderId(): ?string
    {
        return $this->orderId;
    }

    public function setOrderId(?string $orderId): void
    {
        $this->orderId = $orderId;
    }

    public function getMetadata(): ?Metadata
    {
        return $this->metadata;
    }

    public function setMetadata(?Metadata $metadata): void
    {
        $this->metadata = $metadata;
    }

    public function getBasket(): ?Basket
    {
        return $this->basket;
    }

    public function setBasket(?Basket $basket): void
    {
        $this->basket = $basket;
    }

    public function getCard3ds(): ?bool
    {
        return $this->card3ds;
    }

    public function setCard3ds(?bool $card3ds): void
    {
        $this->card3ds = $card3ds;
    }

    public function getInvoiceId(): ?string
    {
        return $this->invoiceId;
    }

    public function setInvoiceId(?string $invoiceId): void
    {
        $this->invoiceId = $invoiceId;
    }

    public function getPaymentReference(): ?string
    {
        return $this->paymentReference;
    }

    public function setPaymentReference(?string $paymentReference): void
    {
        $this->paymentReference = $paymentReference;
    }

    public function fromArray(array $structData): HeidelPaymentStruct
    {
        foreach ($structData as $key => $value) {
            $methodName = 'set' . ucfirst(strtolower($key));

            if (method_exists($this, $methodName)) {
                $this->$methodName($value);
            }
        }

        return $this;
    }
}
