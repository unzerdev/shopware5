<?php

declare(strict_types=1);

namespace UnzerPayment\Services;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Psr\Log\LoggerInterface;
use UnzerPayment\Components\DependencyInjection\Factory\StatusMapper\PaymentStatusMapperFactoryInterface;
use UnzerPayment\Components\PaymentStatusMapper\AbstractStatusMapper;
use UnzerPayment\Components\PaymentStatusMapper\Exception\NoStatusMapperFoundException;
use UnzerPayment\Components\PaymentStatusMapper\Exception\StatusMapperException;
use UnzerPayment\Services\UnzerPaymentClient\UnzerPaymentClientServiceInterface;
use UnzerSDK\Resources\Payment;

class UnzerAsyncOrderBackupService
{
    public const TABLE_NAME        = 's_plugin_unzer_order_ext_backup';
    public const ASYNC_BACKUP_TYPE = 'UnzerAsyncWebhook';

    /** @var Connection */
    private $connection;

    /** @var UnzerPaymentClientServiceInterface */
    private $unzerApiClient;

    /** @var PaymentStatusMapperFactoryInterface */
    private $statusMapperFactory;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        Connection $connection,
        UnzerPaymentClientServiceInterface $unzerApiClient,
        PaymentStatusMapperFactoryInterface $statusMapperFactory,
        LoggerInterface $logger
    ) {
        $this->connection          = $connection;
        $this->unzerApiClient      = $unzerApiClient; //TODO: check if needed
        $this->statusMapperFactory = $statusMapperFactory;
        $this->logger              = $logger;
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

        dump(['insertData' => [$userData, $unzerOrderId, $paymentName, $basketData, $encodedBasket]]); //TODO: Remove before release
    }

    public function createOrderFromUnzerOrderId(Payment $payment): void
    {
        // TODO: Add handling for already created order
        $orderData = $this->readData($payment->getOrderId());

        if (empty($orderData)) {
            throw new \RuntimeException('NoOrderFound');
        }

        $paymentStatusId = $this->getPaymentStatusId($payment);

        try {
            $orderNumber = $this->saveOrder($payment, $orderData);
        } catch (\Throwable $t) {
            $this->logger->error(sprintf(
                'Could not create order for unzerOrderId: %s due to %s',
                $payment->getOrderId(), $t->getMessage()
            ), ['trace' => $t->getTraceAsString(), 'code' => $t->getCode()]);
        }

        if (!empty($orderNumber)) {
            $this->connection->delete(
                self::TABLE_NAME,
                ['unzer_order_id' => $payment->getOrderId()],
                ['unzer_order_id' => ParameterType::STRING]
            );
            dump('deleted'); //TODO: Remove before release
        }

        dd(['createOrderFromUnzerOrderId' => [$payment, $paymentStatusId]]); //TODO: Remove before release
    }

    private function readData(string $unzerOrderId): array
    {
        $data = $this->connection->createQueryBuilder()
            ->select('*')
            ->from(self::TABLE_NAME)
            ->where('unzer_order_id = :unzerOrderId')
            ->setParameter('unzerOrderId', $unzerOrderId)
            ->execute()->fetchAssociative();

        dump($data); //TODO: Remove before release

        return $data;
    }

    private function getPaymentStatusId(Payment $paymentObject): int
    {
        $paymentStatusId = AbstractStatusMapper::INVALID_STATUS;

        try {
            $paymentStatusMapper = $this->statusMapperFactory->getStatusMapper($paymentObject->getPaymentType());

            $paymentStatusId = $paymentStatusMapper->getTargetPaymentStatus($paymentObject);
        } catch (NoStatusMapperFoundException $ex) {
            $this->logger->error($ex->getMessage(), $ex->getTrace());
        } catch (StatusMapperException $ex) {
            $this->logger->warning($ex->getMessage(), $ex->getTrace());
        }

        return $paymentStatusId;
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
}
