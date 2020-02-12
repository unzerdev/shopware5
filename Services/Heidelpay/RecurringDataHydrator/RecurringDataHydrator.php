<?php

declare(strict_types=1);

namespace HeidelPayment\Services\Heidelpay\RecurringDataHydrator;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

class RecurringDataHydrator implements RecurringDataHydratorInterface
{
    /** @var Connection */
    private $connection;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(Connection $connection, LoggerInterface $logger)
    {
        $this->connection = $connection;
        $this->logger     = $logger;
    }

    public function hydrateRecurringData(float $basketAmount, int $orderId): array
    {
        $order = $this->getOrderDataById($orderId);
        $abo   = $this->getAboByOrderId($orderId);

        if (!array_key_exists(0, $order) || !array_key_exists(0, $abo)) {
            $this->logger->error('The order/abo could not be fetched');

            return [];
        }

        $abo          = $abo[0];
        $order        = $order[0];
        $initialOrder = $this->getOrderDataById((int) $abo['order_id']);

        if (!array_key_exists(0, $initialOrder)) {
            $this->logger->error('The initial order could not be fetched');

            return [];
        }

        $initialOrder  = $initialOrder[0];
        $transactionId = $initialOrder['transactionID'];

        if ($basketAmount === 0.0) {
            $basketAmount = (float) $order['invoice_amount'];

            if ($basketAmount === 0.0) {
                $this->logger->error('The basket amount is to low');

                return [];
            }
        }

        if (!$transactionId) {
            $this->logger->error('The wrong transaction id was provided');

            return [];
        }

        return [
            'order'         => $order,
            'aboId'         => (int) $abo['id'],
            'basketAmount'  => (float) $basketAmount,
            'transactionId' => (string) $transactionId,
        ];
    }

    private function getOrderDataById(int $orderId): array
    {
        return $this->connection->createQueryBuilder()
            ->select('*')
            ->from('s_order')
            ->where('id = :orderId')
            ->setParameter('orderId', $orderId)
            ->execute()->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function getAboByOrderId(int $orderId): array
    {
        return $this->connection->createQueryBuilder()
            ->select('*')
            ->from('s_plugin_swag_abo_commerce_orders')
            ->where('last_order_id = :orderId')
            ->setParameter('orderId', $orderId)
            ->execute()->fetchAll(\PDO::FETCH_ASSOC);
    }
}
