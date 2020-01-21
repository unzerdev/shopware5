<?php

declare(strict_types=1);

namespace HeidelPayment\Services\Heidelpay\ResourceHydrators;

use HeidelPayment\Services\Heidelpay\HeidelpayResourceHydratorInterface;
use heidelpayPHP\Constants\BasketItemTypes;
use heidelpayPHP\Heidelpay;
use heidelpayPHP\Resources\AbstractHeidelpayResource;
use heidelpayPHP\Resources\Basket;
use heidelpayPHP\Resources\EmbeddedResources\BasketItem;
use Shopware\Components\Random;

class BasketHydrator implements HeidelpayResourceHydratorInterface
{
    private const SW_VOUCHER_MODE      = '2';
    private const SW_SURCHARGE_MODE    = '4';
    private const SW_ABO_DISCOUNT_MODE = '10';

    /**
     * {@inheritdoc}
     *
     * @return Basket
     */
    public function hydrateOrFetch(
        array $data,
        Heidelpay $heidelpayObj,
        string $resourceId = null
    ): AbstractHeidelpayResource {
        if ($resourceId !== null) {
            return $heidelpayObj->fetchBasket($resourceId);
        }

        $result = new Basket();
        $result->setAmountTotalGross(round($data['sAmount'], 4));
        $result->setAmountTotalVat(round($data['sAmountTax'], 4));
        $result->setCurrencyCode($data['sCurrencyName']);
        $result->setOrderId($this->generateOrderId());

        //Actual line items
        foreach ($data['content'] as $lineItem) {
            $amountNet     = str_replace(',', '.', $lineItem['amountnet']);
            $amountGross   = str_replace(',', '.', $lineItem['amount']);
            $amountPerUnit = $lineItem['additional_details']['price_numeric'];

            $type = BasketItemTypes::GOODS;

            if ($lineItem['esd'] || $lineItem['esdarticle']) {
                $type = BasketItemTypes::DIGITAL;
            }

            //Fix for "voucher"
            if ($lineItem['modus'] === self::SW_VOUCHER_MODE) {
                $type = BasketItemTypes::VOUCHER;

                $amountNet   = $lineItem['netprice'] * -1;
                $amountGross = $lineItem['priceNumeric'] * -1;

                $amountPerUnit = $amountGross;
            }

            //Fix for "sw-surcharge"
            if ($lineItem['modus'] === self::SW_SURCHARGE_MODE) {
                $amountNet     = $lineItem['netprice'];
                $amountGross   = $lineItem['priceNumeric'];
                $amountPerUnit = $amountGross;
            }

            //Fix for "sw-abo-discount"
            if ($lineItem['modus'] === self::SW_ABO_DISCOUNT_MODE) {
                $type        = BasketItemTypes::VOUCHER;
                $amountNet   = $lineItem['amountnetNumeric'] * -1;
                $amountGross = $lineItem['amountNumeric'] * -1;
                $lineItem['tax'] *= -1;
                $amountPerUnit = $amountGross;
            }

            $basketItem = new BasketItem();
            $basketItem->setType($type);
            $basketItem->setTitle($lineItem['articlename']);
            $basketItem->setAmountPerUnit($amountPerUnit);
            $basketItem->setAmountGross(round($amountGross, 4));
            $basketItem->setAmountNet(round($amountNet, 4));
            $basketItem->setAmountVat(round(str_replace(',', '.', $lineItem['tax']), 4));
            $basketItem->setQuantity((int) $lineItem['quantity']);
            $basketItem->setVat((float) $lineItem['tax_rate']);

            if ($lineItem['abo_attributes']['isAboArticle']) {
                $result->setSpecialParams(array_merge($result->getSpecialParams(), ['isAbo' => true]));
                $basketItem->setSpecialParams([
                    'aboCommerce' => $lineItem['aboCommerce'],
                ]);
            }

            $result->addBasketItem($basketItem);
        }

        //No dispatch selected!
        if (empty($data['sDispatch'])) {
            return $result;
        }

        //Shipping cost line item
        $dispatchBasketItem = new BasketItem();
        $dispatchBasketItem->setType(BasketItemTypes::SHIPMENT);
        $dispatchBasketItem->setTitle($data['sDispatch']['name']);
        $dispatchBasketItem->setAmountGross($data['sShippingcostsWithTax']);
        $dispatchBasketItem->setAmountPerUnit($data['sShippingcostsWithTax']);
        $dispatchBasketItem->setAmountNet($data['sShippingcostsNet']);
        $dispatchBasketItem->setAmountVat($data['sShippingcostsWithTax'] - $data['sShippingcostsNet']);
        $dispatchBasketItem->setQuantity(1);
        $dispatchBasketItem->setVat($data['sShippingcostsTax'] ?? 0);

        $result->addBasketItem($dispatchBasketItem);

        return $result;
    }

    private function generateOrderId(): string
    {
        return Random::getAlphanumericString(32);
    }
}
