<?php

declare(strict_types=1);

namespace UnzerPayment\Components\Converter\BasketConverter;

use UnzerPayment\Components\Converter\BasketConverter\BasketConverterInterface;

class BasketConverter implements BasketConverterInterface
{
    public function populateDeprecatedVariables(array $basket): array
    {
        $basket['amountTotalGross'] = $basket['totalValueGross'];

        foreach ($basket['basketItems'] as &$item) {
            $vat = $item['vat'] / 100;

            $basket['amountTotalVat'] += $this->calculatBasketItemVat($item, $vat);
            $item = $this->updateBasketItem($item, $vat);
        }

        $basket['amountTotalVat'] = round((float) $basket['amountTotalVat'], 4);

        return $basket;
    }

    private function calculatBasketItemVat(array $item, float $vat): float
    {
        $basketItemVat = round((float)(($item['amountPerUnitGross'] * $item['quantity']) / (1 + $vat)) * $vat, 4);

        if ($item['type'] === 'voucher') {
            $basketItemVat *= -1;
        }

        return $basketItemVat;
    }

    private function updateBasketItem(array $item, float $vat): array
    {
        if ($item['type'] === 'voucher') {
            $item['amountDiscount'] = $item['amountDiscountPerUnitGross'] * $item['quantity'];
        }
        
        $item['amountPerUnit'] = $item['amountPerUnitGross'];
        $item['amountGross'] = $item['amountPerUnitGross'] * $item['quantity'];
        $item['amountVat'] = round((float) ($item['amountGross'] / (1 + $vat)) * $vat, 4);
        $item['amountNet'] = round((float) $item['amountGross'] / (1 + $vat), 4);

        return $item;
    }
}
