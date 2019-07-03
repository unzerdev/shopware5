<?php

namespace HeidelPayment\Services;

use Doctrine\DBAL\Connection;
use heidelpayPHP\Resources\Payment;

class OrderStatusService implements OrderStatusServiceInterface
{
    /** @var Connection */
    private $connection;

    /** @var PaymentStatusFactoryInterface */
    private $paymentStatusFactory;

    public function __construct(Connection $connection, PaymentStatusFactoryInterface $paymentStatusFactory)
    {
        $this->connection           = $connection;
        $this->paymentStatusFactory = $paymentStatusFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function updatePaymentStatusById(string $transactionId, int $statusId): void
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $queryBuilder->update('s_order')
            ->set('cleared', ':statusId')
            ->where('transactionID = :transactionId')
            ->setParameters([
                'statusId'      => $statusId,
                'transactionId' => $transactionId,
            ])->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function updatePaymentStatusByPayment(Payment $payment): void
    {
        $transactionId   = $payment->getOrderId();
        $paymentStatusId = $this->paymentStatusFactory->getPaymentStatusId($payment);

        $this->updatePaymentStatusById($transactionId, $paymentStatusId);
    }
}
