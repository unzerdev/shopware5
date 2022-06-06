<?php

declare(strict_types=1);

namespace UnzerPayment\Components\Converter\BasketConverter;

use Doctrine\DBAL\Connection;
use UnzerPayment\Components\Converter\BasketConverter\BasketConverterInterface;

class BasketConverter implements BasketConverterInterface
{
    /** @var Connection */
    private $connection;

    public function __construct(Connection $connection) {
        $this->connection = $connection;
    }
    
    public function populateDeprecatedVariables(int $orderId, array $basket): array
    {
        $basket['amountTotalGross'] = $basket['totalValueGross'];
        $basket['amountTotalVat'] = $this->getAmountTotalVat($orderId);

        foreach ($basket['basketItems'] as &$item) {
            $item = $this->updateBasketItem($item, $item['vat']);
        }

        return $basket;
    }

    private function getAmountTotalVat(int $orderId): float
    {
        $orderAmount = $this->connection->createQueryBuilder()
            ->select('o.invoice_amount AS gross, o.invoice_amount_net AS net')
            ->from('s_order', 'o')
            ->where('o.id = :orderId')
            ->setParameter(':orderId', $orderId)
            ->execute()
            ->fetchAssociative();

        if (!empty($orderAmount['gross']) && 
            !empty($orderAmount['net'])) 
        {
            return (float) round($orderAmount['gross'] - $orderAmount['net'], 4);
        }
        
        return (float) 0;
    }

    private function updateBasketItem(array $item, float $vat): array
    {
        $vat = $vat / 100;

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
