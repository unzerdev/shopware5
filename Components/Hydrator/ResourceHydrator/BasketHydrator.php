<?php

declare(strict_types=1);

namespace HeidelPayment\Components\Hydrator\ResourceHydrator;

use heidelpayPHP\Constants\BasketItemTypes;
use heidelpayPHP\Heidelpay;
use heidelpayPHP\Resources\AbstractHeidelpayResource;
use heidelpayPHP\Resources\Basket;
use heidelpayPHP\Resources\EmbeddedResources\BasketItem;
use Shopware\Components\Random;

class BasketHydrator implements ResourceHydratorInterface
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
        Heidelpay $heidelpayObj = null,
        string $resourceId = null
    ): AbstractHeidelpayResource {
        if ($resourceId !== null && $heidelpayObj !== null) {
            return $heidelpayObj->fetchBasket($resourceId);
        }

        $isAmountInNet    = isset($data['sAmountWithTax']);
        $amountTotalGross = $isAmountInNet ? $data['sAmountWithTax'] : $data['sAmount'];

        $result = new Basket();
        $result->setAmountTotalGross(round($amountTotalGross, 4));
        $result->setAmountTotalVat(round($data['sAmountTax'], 4));
        $result->setCurrencyCode($data['sCurrencyName']);
        $result->setOrderId($this->generateOrderId());

        //Actual line items
        foreach ($data['content'] as $lineItem) {
            $amountNet     = $lineItem['amountnetNumeric'];
            $amountGross   = $isAmountInNet ? $lineItem['amountWithTax'] : $lineItem['amountNumeric'];
            $amountPerUnit = $isAmountInNet
                ? $amountGross / $lineItem['quantity']
                : $lineItem['additional_details']['price_numeric'];

            if (!$amountPerUnit) {
                $amountPerUnit = (float) $lineItem['priceNumeric'];
            }

            $type = BasketItemTypes::GOODS;

            if ($lineItem['esd'] || $lineItem['esdarticle']) {
                $type = BasketItemTypes::DIGITAL;
            }

            //Fix for "sw-surcharge"
            if ($lineItem['modus'] === self::SW_SURCHARGE_MODE) {
                $amountNet     = $lineItem['amountnetNumeric'];
                $amountGross   = $isAmountInNet ? $lineItem['amountWithTax'] : $lineItem['amountNumeric'];
                $amountPerUnit = $amountGross / $lineItem['quantity'];
            }

            //Fix for "sw-abo-discount" and "voucher"
            if ($lineItem['modus'] === self::SW_ABO_DISCOUNT_MODE || $lineItem['modus'] === self::SW_VOUCHER_MODE) {
                $lineItem['tax'] = str_replace(',', '.', $lineItem['tax']) * -1;

                $type          = BasketItemTypes::VOUCHER;
                $amountNet     = $lineItem['amountnetNumeric'] * -1;
                $amountGross   = $isAmountInNet ? $lineItem['amountWithTax'] * -1 : $lineItem['amountNumeric'] * -1;
                $amountPerUnit = $amountGross / $lineItem['quantity'];
            }

            $basketItem = new BasketItem();
            $basketItem->setType($type);
            $basketItem->setTitle($lineItem['articlename']);
            $basketItem->setAmountPerUnit(round($amountPerUnit, 4));
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
        $dispatchBasketItem->setAmountGross(round($data['sShippingcostsWithTax'], 4));
        $dispatchBasketItem->setAmountPerUnit(round($data['sShippingcostsWithTax'], 4));
        $dispatchBasketItem->setAmountNet(round($data['sShippingcostsNet'], 4));
        $dispatchBasketItem->setAmountVat(round($data['sShippingcostsWithTax'] - $data['sShippingcostsNet'], 4));
        $dispatchBasketItem->setQuantity(1);
        $dispatchBasketItem->setVat((float) $data['sShippingcostsTax']);

        $result->addBasketItem($dispatchBasketItem);

        return $result;
    }

    private function generateOrderId(): string
    {
        return Random::getAlphanumericString(32);
    }
}