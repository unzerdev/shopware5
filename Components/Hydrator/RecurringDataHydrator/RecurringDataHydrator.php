<?php

declare(strict_types=1);

namespace HeidelPayment\Components\Hydrator\RecurringDataHydrator;

use Doctrine\DBAL\Connection;
use HeidelPayment\Installers\Attributes;
use PDO;
use Psr\Log\LoggerInterface;
use Shopware\Bundle\AttributeBundle\Service\DataLoader;

class RecurringDataHydrator implements RecurringDataHydratorInterface
{
    /** @var Connection */
    private $connection;

    /** @var DataLoader */
    private $dataLoader;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(Connection $connection, DataLoader $dataLoader, LoggerInterface $logger)
    {
        $this->connection = $connection;
        $this->dataLoader = $dataLoader;
        $this->logger     = $logger;
    }

    public function hydrateRecurringData(float $basketAmount, int $orderId): array
    {
        $order           = $this->getOrderDataById($orderId);
        $abo             = $this->getAboByOrderId($orderId);
        $orderAttributes = $this->dataLoader->load('s_order_attributes', $orderId);
        $transactionId   = $orderAttributes[Attributes::HEIDEL_ATTRIBUTE_TRANSACTION_ID];

        if (!array_key_exists(0, $order) || !array_key_exists(0, $abo)) {
            $this->logger->error('The order/abo could not be fetched');

            return [];
        }

        $abo   = $abo[0];
        $order = $order[0];

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
            ->execute()->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getAboByOrderId(int $orderId): array
    {
        return $this->connection->createQueryBuilder()
            ->select('*')
            ->from('s_plugin_swag_abo_commerce_orders')
            ->where('last_order_id = :orderId')
            ->setParameter('orderId', $orderId)
            ->execute()->fetchAll(PDO::FETCH_ASSOC);
    }
}
