<?php

namespace HeidelPayment\Services\Heidelpay;

use heidelpayPHP\Heidelpay;
use heidelpayPHP\Resources\AbstractHeidelpayResource;
use heidelpayPHP\Resources\Basket;
use heidelpayPHP\Resources\EmbeddedResources\BasketItem;
use Shopware\Components\Random;

class BasketProvider implements DataProviderInterface
{
    public function hydrateOrFetch(
        array $data,
        Heidelpay $heidelpayObj,
        string $resourceId = null
    ): AbstractHeidelpayResource {
        if ($resourceId !== null) {
            return $heidelpayObj->fetchBasket($resourceId);
        }

        $result = new Basket();
        $result->setAmountTotal(number_format($data['sAmount'], 4));
        $result->setAmountTotalVat(number_format($data['sAmountTax'], 4));
        $result->setCurrencyCode($data['sCurrencyName']);
        $result->setOrderId($this->generateOrderId());

        //Actual line items
        foreach ($data['content'] as $lineItem) {
            $basketItem = new BasketItem();
            $basketItem->setTitle($lineItem['articlename']);
            $basketItem->setAmountGross($lineItem['additional_details']['price_numeric']);
            $basketItem->setAmountNet(number_format($lineItem['netprice'], 4));
            $basketItem->setAmountVat(number_format(str_replace(',', '.', $lineItem['tax']), 4));
            $basketItem->setQuantity($lineItem['quantity']);

            $result->addBasketItem($basketItem);
        }

        //No dispatch selected!
        if (empty($data['sDispatch'])) {
            return $result;
        }

        //Shipping cost line item
        $dispatchBasketItem = new BasketItem();
        $dispatchBasketItem->setTitle($data['sDispatch']['name']);
        $dispatchBasketItem->setAmountGross($data['sShippingcostsWithTax']);
        $dispatchBasketItem->setAmountNet($data['sShippingcostsNet']);
        $dispatchBasketItem->setAmountVat($data['sShippingcostsWithTax'] - $data['sShippingcostsNet']);
        $dispatchBasketItem->setQuantity(1);

        $result->addBasketItem($dispatchBasketItem);

        return $result;
    }

    private function generateOrderId(): string
    {
        return Random::getAlphanumericString(32);
    }
}
