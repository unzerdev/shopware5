<?php

declare(strict_types=1);

namespace UnzerPayment\Components\Converter\BasketConverter;

use Doctrine\DBAL\Connection;
use UnzerPayment\Components\Hydrator\ResourceHydrator\BasketHydrator;
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

        unset($item);

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
            ->fetch();

        if (!empty($orderAmount['gross']) && !empty($orderAmount['net'])) {
            return round((float) $orderAmount['gross'] - (float) $orderAmount['net'], BasketHydrator::UNZER_DEFAULT_PRECISION);
        }
        
        return 0.0;
    }

    private function updateBasketItem(array $item, float $vat): array
    {
        $vat += 100;

        if ($item['type'] === 'voucher') {
            $item['amountDiscount'] = round((float) $item['amountDiscountPerUnitGross'] * (int) $item['quantity'], BasketHydrator::UNZER_DEFAULT_PRECISION);
        }
        
        $item['amountPerUnit'] = $item['amountPerUnitGross'];
        $item['amountGross'] = round((float) $item['amountPerUnitGross'] * (int) $item['quantity'], BasketHydrator::UNZER_DEFAULT_PRECISION);
        $item['amountNet'] = round(((float) $item['amountGross'] / $vat) * 100, BasketHydrator::UNZER_DEFAULT_PRECISION);
        $item['amountVat'] = round((float) $item['amountGross'] - (float) $item['amountNet'], BasketHydrator::UNZER_DEFAULT_PRECISION);

        return $item;
    }
}
