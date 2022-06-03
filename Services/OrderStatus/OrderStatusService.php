<?php

declare(strict_types=1);

namespace UnzerPayment\Services\OrderStatus;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use RuntimeException;
use sOrder;
use UnzerPayment\Components\DependencyInjection\Factory\StatusMapper\PaymentStatusMapperFactoryInterface;
use UnzerPayment\Components\PaymentStatusMapper\AbstractStatusMapper;
use UnzerPayment\Components\PaymentStatusMapper\Exception\NoStatusMapperFoundException;
use UnzerPayment\Components\PaymentStatusMapper\Exception\StatusMapperException;
use UnzerPayment\Services\ConfigReader\ConfigReaderServiceInterface;
use UnzerPayment\Services\DependencyProvider\DependencyProviderServiceInterface;
use UnzerSDK\Resources\Payment;

class OrderStatusService implements OrderStatusServiceInterface
{
    /** @var Connection */
    private $connection;

    /** @var sOrder */
    private $orderModule;

    /** @var ConfigReaderServiceInterface */
    private $configReaderService;

    /** @var PaymentStatusMapperFactoryInterface */
    private $paymentStatusFactory;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        Connection $connection,
        DependencyProviderServiceInterface $dependencyProviderService,
        ConfigReaderServiceInterface $configReaderService,
        PaymentStatusMapperFactoryInterface $paymentStatusFactory,
        LoggerInterface $logger
    ) {
        $this->connection           = $connection;
        $this->orderModule          = $dependencyProviderService->getModule('order');
        $this->configReaderService  = $configReaderService;
        $this->paymentStatusFactory = $paymentStatusFactory;
        $this->logger               = $logger;
    }

    public function getPaymentStatusForPayment(Payment $payment): int
    {
        try {
            $paymentStatusMapper = $this->paymentStatusFactory->getStatusMapper($payment->getPaymentType());

            return $paymentStatusMapper->getTargetPaymentStatus($payment);
        } catch (NoStatusMapperFoundException | StatusMapperException $ex) {
            // silentfail
        }

        return AbstractStatusMapper::INVALID_STATUS;
    }

    public function updatePaymentStatusByTransactionId(string $transactionId, int $statusId): void
    {
        if ($this->orderModule === null) {
            throw new RuntimeException('Unable to update the payment status since the order module is not available!');
        }

        $orderId = $this->connection->createQueryBuilder()
            ->select('id')
            ->from('s_order')
            ->where('transactionID = :transactionId')
            ->setParameter('transactionId', $transactionId)
            ->execute()
            ->fetchColumn();

        $this->orderModule->setPaymentStatus($orderId, $statusId, $this->configReaderService->get('automatic_payment_notification'), 'UnzerPayment - Webhook');
    }

    public function updatePaymentStatusByPayment(Payment $payment): void
    {
        $transactionId = $payment->getOrderId();

        if (empty($transactionId)) {
            return;
        }

        try {
            $paymentStatusMapper = $this->paymentStatusFactory->getStatusMapper($payment->getPaymentType());

            $paymentStatusId = $paymentStatusMapper->getTargetPaymentStatus($payment);
        } catch (NoStatusMapperFoundException | StatusMapperException $ex) {
            $this->logger->error($ex->getMessage(), $ex->getTrace());

            return;
        }

        $this->updatePaymentStatusByTransactionId($transactionId, $paymentStatusId);
    }
}
