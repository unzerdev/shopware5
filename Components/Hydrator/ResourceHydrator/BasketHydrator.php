<?php

declare(strict_types=1);

namespace UnzerPayment\Components\Hydrator\ResourceHydrator;

use Shopware\Components\Random;
use UnzerSDK\Constants\BasketItemTypes;
use UnzerSDK\Resources\AbstractUnzerResource;
use UnzerSDK\Resources\Basket;
use UnzerSDK\Resources\EmbeddedResources\BasketItem;
use UnzerSDK\Unzer;

class BasketHydrator implements ResourceHydratorInterface
{
    public const UNZER_DEFAULT_PRECISION = 4;

    private const SW_VOUCHER_MODE      = '2';
    private const SW_DISCOUNT          = '3';
    private const SW_ABO_DISCOUNT_MODE = '10';

    /**
     * {@inheritdoc}
     *
     * @return Basket
     */
    public function hydrateOrFetch(
        array $data,
        Unzer $unzerPaymentInstance = null,
        string $resourceId = null
    ): AbstractUnzerResource {
        if ($resourceId !== null && $unzerPaymentInstance !== null) {
            return $unzerPaymentInstance->fetchBasket($resourceId);
        }

        $isTaxFree     = $data['taxFree'] ?? false;
        $amountWithTax = $data['sAmount'] ?? 0.00;

        if (array_key_exists('sAmountWithTax', $data)) {
            $amountWithTax = $data['sAmountWithTax'] ?? 0.00;
        }
        $totalValueGross = $isTaxFree ? ($data['sAmount'] ?? 0.00) : ($amountWithTax);

        $basket = new Basket();
        $basket->setOrderId($this->generateOrderId());
        $basket->setTotalValueGross(round((float) $totalValueGross, self::UNZER_DEFAULT_PRECISION));
        $basket->setCurrencyCode($data['sCurrencyName']);

        $this->hydrateBasketItems($basket, $data['content'], $isTaxFree);
        $this->hydrateDispatch($basket, $data, $isTaxFree);

        return $basket;
    }

    protected function hydrateBasketItems(Basket $basket, array $lineItems, bool $isTaxFree): void
    {
        foreach ($lineItems as $lineItem) {
            $basketItem = new BasketItem();
            $basketItem->setType($this->getBasketItemType($lineItem));
            $basketItem->setTitle($lineItem['articlename']);
            $basketItem->setQuantity((int) $lineItem['quantity']);

            $amountWithTax = $lineItem['amountNumeric'];

            if (array_key_exists('amountWithTax', $lineItem)) {
                $amountWithTax = $lineItem['amountWithTax'];
            }

            $amountGross = round(abs(
                $isTaxFree ? $lineItem['amountNumeric'] ?? 0.00 : $amountWithTax
            ), self::UNZER_DEFAULT_PRECISION);

            if ($this->isBasketItemVoucher($lineItem)) {
                $basketItem->setAmountDiscountPerUnitGross($amountGross);
            } else {
                $amountPerUnit = $amountGross / $lineItem['quantity'];

                $basketItem->setAmountPerUnitGross(round((float) $amountPerUnit, self::UNZER_DEFAULT_PRECISION));
                $basketItem->setVat((float) $lineItem['tax_rate']);
            }

            if (array_key_exists('abo_attributes', $lineItem) && !empty($lineItem['abo_attributes'])
                && array_key_exists('isAboArticle', $lineItem['abo_attributes']) && !empty($lineItem['abo_attributes']['isAboArticle'])) {
                $basket->setSpecialParams(array_merge($basket->getSpecialParams(), ['isAbo' => true]));
                $basketItem->setSpecialParams([
                    'aboCommerce' => $lineItem['aboCommerce'],
                ]);
            }

            // Skip free lineItems for compatibility with Unzer API-Endpoint /v2/baskets
            if ($this->isFreeBasketItem($basketItem, self::UNZER_DEFAULT_PRECISION)) {
                continue;
            }

            $basket->addBasketItem($basketItem);
        }
    }

    protected function hydrateDispatch(Basket $basket, array $data, bool $isTaxFree): void
    {
        if (!array_key_exists('sDispatch', $data) || empty($data['sDispatch'])) {
            return;
        }

        $amountGross = $isTaxFree ? $data['sShippingcostsNet'] : $data['sShippingcostsWithTax'];

        //Shipping cost line item
        $dispatchBasketItem = new BasketItem();
        $dispatchBasketItem->setType(BasketItemTypes::SHIPMENT);
        $dispatchBasketItem->setTitle($data['sDispatch']['name']);
        $dispatchBasketItem->setAmountPerUnitGross(round((float) $amountGross, self::UNZER_DEFAULT_PRECISION));
        $dispatchBasketItem->setVat((float) $data['sShippingcostsTax']);
        $dispatchBasketItem->setQuantity(1);

        // Skip free shipping costs for compatibility with Unzer API-Endpoint /v2/baskets
        if ($this->isFreeBasketItem($dispatchBasketItem, self::UNZER_DEFAULT_PRECISION)) {
            return;
        }

        $basket->addBasketItem($dispatchBasketItem);
    }

    private function generateOrderId(): string
    {
        return Random::getAlphanumericString(32);
    }

    private function getBasketItemType(array $lineItem): string
    {
        if ($lineItem['esd'] || $lineItem['esdarticle']) {
            return BasketItemTypes::DIGITAL;
        }

        if ($this->isBasketItemVoucher($lineItem)) {
            return BasketItemTypes::VOUCHER;
        }

        return BasketItemTypes::GOODS;
    }

    private function isBasketItemVoucher(array $lineItem): bool
    {
        return in_array(
                $lineItem['modus'],
                [self::SW_ABO_DISCOUNT_MODE, self::SW_VOUCHER_MODE, self::SW_DISCOUNT],
                true
            )
            || !empty($lineItem['__s_order_basket_attributes_swag_promotion_id']);
    }

    private function isFreeBasketItem(BasketItem $basketItem, int $currencyPrecision): bool
    {
        if ((int) (round($basketItem->getAmountPerUnitGross(), $currencyPrecision) * $currencyPrecision) === 0 && (int) (round($basketItem->getAmountDiscountPerUnitGross(), $currencyPrecision) * $currencyPrecision) === 0) {
            return true;
        }

        return false;
    }
}
