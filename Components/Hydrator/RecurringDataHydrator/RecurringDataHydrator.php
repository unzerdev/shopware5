<?php

declare(strict_types=1);

namespace UnzerPayment\Components\Hydrator\RecurringDataHydrator;

use Doctrine\DBAL\Connection;
use PDO;
use Psr\Log\LoggerInterface;
use Shopware\Bundle\AttributeBundle\Service\DataLoader;
use UnzerPayment\Installers\Attributes;

class RecurringDataHydrator implements RecurringDataHydratorInterface
{
    private Connection $connection;

    private DataLoader $dataLoader;

    private LoggerInterface $logger;

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

        if (!array_key_exists(0, $order)) {
            $this->logger->error(sprintf('The order for id %s could not be fetched', $orderId));

            return [];
        }

        if (!array_key_exists(0, $abo)) {
            $this->logger->error(sprintf('The abo for id %s could not be fetched', $orderId));

            return [];
        }

        $abo           = $abo[0];
        $order         = $order[0];
        $transactionId = $order['transactionID'];

        if (array_key_exists(Attributes::UNZER_PAYMENT_ATTRIBUTE_TRANSACTION_ID, $orderAttributes)
            && !empty($orderAttributes[Attributes::UNZER_PAYMENT_ATTRIBUTE_TRANSACTION_ID])) {
            $transactionId = $orderAttributes[Attributes::UNZER_PAYMENT_ATTRIBUTE_TRANSACTION_ID];
        }

        if (!$transactionId) {
            $this->logger->error('The wrong transaction id was provided');

            return [];
        }

        if ($basketAmount <= 0.0) {
            $basketAmount = (float) $order['invoice_amount'];

            if ($basketAmount <= 0.0) {
                $this->logger->error('The basket amount is too low');

                return [];
            }
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
            ->execute()->fetchAllAssociative();
    }

    private function getAboByOrderId(int $orderId): array
    {
        return $this->connection->createQueryBuilder()
            ->select('*')
            ->from('s_plugin_swag_abo_commerce_orders')
            ->where('last_order_id = :orderId')
            ->setParameter('orderId', $orderId)
            ->execute()->fetchAllAssociative();
    }
}
