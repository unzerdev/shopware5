<?php

declare(strict_types=1);

namespace UnzerPayment\Services;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Psr\Log\LoggerInterface;
use UnzerSDK\Resources\Payment;

class UnzerAsyncOrderBackupService
{
    public const TABLE_NAME        = 's_plugin_unzer_order_ext_backup';
    public const ASYNC_BACKUP_TYPE = 'UnzerAsyncWebhook';

    /** @var Connection */
    private $connection;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(Connection $connection, LoggerInterface $logger)
    {
        $this->connection = $connection;
        $this->logger     = $logger;
    }

    public function insertData(array $userData, array $basketData, string $unzerOrderId, string $paymentName): void
    {
        $encodedBasket = json_encode($basketData);
        $encodedUser   = json_encode($userData);
        $sComment      = Shopware()->Session()->get('sComment');
        $dispatchId    = Shopware()->Session()->get('sDispatch') ?? Shopware()->Session()->get('sOrderVariables')['sDispatch'];

        $queryBuilder = $this->connection->createQueryBuilder();

        $queryBuilder->insert(self::TABLE_NAME)
            ->values([
                'unzer_order_id' => ':unzerOrderId',
                'payment_name'   => ':paymentName',
                'user_data'      => ':userData',
                'basket_data'    => ':basketData',
                's_comment'      => ':sComment',
                'dispatch_id'    => ':dispatchId',
                'created_at'     => ':createdAt',
            ])->setParameters([
                'unzerOrderId' => $unzerOrderId,
                'paymentName'  => $paymentName,
                'userData'     => $encodedUser,
                'basketData'   => $encodedBasket,
                'sComment'     => $sComment,
                'dispatchId'   => $dispatchId ?? 0,
                'createdAt'    => (new \DateTime())->format('YYYY-mm-dd H:i:s'),
            ])->execute();
    }

    public function createOrderFromUnzerOrderId(Payment $payment): void
    {
        $transactionId = $payment->getOrderId();

        $orderId = $this->connection->createQueryBuilder()
            ->select('id')
            ->from('s_order')
            ->where('transactionID = :transactionId')
            ->setParameter('transactionId', $transactionId)
            ->execute()
            ->fetchColumn();

        if (!empty($orderId)) {
            $this->removeBackupData($transactionId);

            return;
        }

        $orderData = $this->readData($transactionId);

        if (empty($orderData)) {
            throw new \RuntimeException('NoOrderFound');
        }

        try {
            $orderNumber = $this->saveOrder($payment, $orderData);
        } catch (\Throwable $t) {
            $this->logger->error(sprintf(
                'Could not create order for unzerOrderId: %s due to %s',
                $transactionId, $t->getMessage()
            ), ['trace' => $t->getTraceAsString(), 'code' => $t->getCode()]);
        }

        if (!empty($orderNumber)) {
            $userData = json_decode($orderData['user_data'], true);

            $this->removeBackupData($transactionId);
            $this->removeBasketData((int) ($userData['additional']['user']['id'] ?? null));
        }
    }

    private function readData(string $unzerOrderId): array
    {
        $data =  $this->connection->createQueryBuilder()
            ->select('*')
            ->from(self::TABLE_NAME)
            ->where('unzer_order_id = :unzerOrderId')
            ->setParameter('unzerOrderId', $unzerOrderId)
            ->execute()->fetchAssociative();

        return $data === false ? [] : $data;
    }

    private function saveOrder(Payment $paymentObject, array $backupData): string
    {
        if (!array_key_exists('user_data', $backupData) || !array_key_exists('basket_data', $backupData)) {
            throw new \RuntimeException('missing data to create order');
        }

        $user       = json_decode($backupData['user_data'], true);
        $basket     = json_decode($backupData['basket_data'], true);
        $dispatchId = (int) $backupData['dispatchId'];
        $sComment   = $backupData['sComment'];

        $order                           = Shopware()->Modules()->Order();
        $order->sUserData                = $user;
        $order->sComment                 = $sComment;
        $order->sBasketData              = $basket;
        $order->sAmount                  = $basket['sAmount'];
        $order->sAmountWithTax           = !empty($basket['AmountWithTaxNumeric']) ? $basket['AmountWithTaxNumeric'] : $basket['AmountNumeric'];
        $order->sAmountNet               = $basket['AmountNetNumeric'];
        $order->sShippingcosts           = $basket['sShippingcosts'];
        $order->sShippingcostsNumeric    = $basket['sShippingcostsWithTax'];
        $order->sShippingcostsNumericNet = $basket['sShippingcostsNet'];
        $order->bookingId                = $paymentObject->getOrderId();
        $order->dispatchId               = $dispatchId;
        $order->sNet                     = empty($user['additional']['charge_vat']);
        $order->uniqueID                 = $paymentObject->getId();
        $order->deviceType               = self::ASYNC_BACKUP_TYPE;

        return $order->sSaveOrder();
    }

    private function removeBackupData(string $unzerOrderId): void
    {
        try {
            $this->connection->delete(
                self::TABLE_NAME,
                ['unzer_order_id' => $unzerOrderId],
                ['unzer_order_id' => ParameterType::STRING]
            );
        } catch (\Throwable $t) {
            $this->logger->error(sprintf(
                'Could not remove order backup for unzerOrderId: %s due to %s',
                $unzerOrderId, $t->getMessage()
            ), ['trace' => $t->getTraceAsString(), 'code' => $t->getCode()]);
        }
    }

    private function removeBasketData(?int $customerId): void
    {
        if ($customerId === 0) { //due to the int cast `null` becomes `0`
            return;
        }

        $sessionId = $this->connection->createQueryBuilder()
            ->select('sessionID')
            ->from('s_user')
            ->where('id = :customerId')
            ->setParameter('customerId', $customerId)
            ->execute()->fetchFirstColumn();

        if (empty($sessionId)) {
            return;
        }

        try {
            $this->connection->delete(
                's_order_basket',
                ['sessionID' => current($sessionId), 'userID' => $customerId, 'lastviewport' => 'checkout'],
                ['sessionID' => ParameterType::STRING, 'userID' => ParameterType::INTEGER, 'lastviewport' => ParameterType::STRING]
            );
        } catch (\Throwable $t) {
            $this->logger->error(sprintf(
                'Could not remove order data userID: %s with sessionID: %s due to %s',
                $customerId, $sessionId, $t->getMessage()
            ), ['trace' => $t->getTraceAsString(), 'code' => $t->getCode()]);
        }
    }
}
