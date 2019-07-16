<?php

namespace HeidelPayment\Services\Heidelpay\DataProviders;

use HeidelPayment\Services\Heidelpay\DataProviderInterface;
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
            $amountNet     = str_replace(',', '.', $lineItem['amountnet']);
            $amountGross   = str_replace(',', '.', $lineItem['amount']);
            $amountPerUnit = $lineItem['additional_details']['price_numeric'];

            //Fix for "sw-surcharge"
            if ($lineItem['modus'] === '4') {
                $amountNet     = $lineItem['netprice'];
                $amountGross   = $lineItem['priceNumeric'];
                $amountPerUnit = $amountGross;
            }

            $basketItem = new BasketItem();
            $basketItem->setTitle($lineItem['articlename']);
            $basketItem->setAmountPerUnit($amountPerUnit);
            $basketItem->setAmountGross(number_format($amountGross, 4));
            $basketItem->setAmountNet(number_format($amountNet, 4));
            $basketItem->setAmountVat(number_format(str_replace(',', '.', $lineItem['tax']), 4));
            $basketItem->setQuantity($lineItem['quantity']);
            $basketItem->setVat($lineItem['tax_rate']);

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
        $dispatchBasketItem->setAmountPerUnit($data['sShippingcostsWithTax']);
        $dispatchBasketItem->setAmountNet($data['sShippingcostsNet']);
        $dispatchBasketItem->setAmountVat($data['sShippingcostsWithTax'] - $data['sShippingcostsNet']);
        $dispatchBasketItem->setQuantity(1);
        $dispatchBasketItem->setVat($data['sShippingcostsTax']);

        $result->addBasketItem($dispatchBasketItem);

        return $result;
    }

    private function generateOrderId(): string
    {
        return Random::getAlphanumericString(32);
    }
}
