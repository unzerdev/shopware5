<?php

declare(strict_types=1);

namespace UnzerPayment\Services\PaymentIdentification;

use Doctrine\DBAL\Connection;
use UnzerPayment\Installers\Attributes;
use UnzerPayment\Installers\PaymentMethods;

class PaymentIdentificationService implements PaymentIdentificationServiceInterface
{
    public const CANCELLATION_OBJECT_NEEDED_PAYMENT_METHODS = [
        PaymentMethods::PAYMENT_NAME_PAYLATER_INVOICE,
        PaymentMethods::PAYMENT_NAME_PAYLATER_INSTALLMENT,
        PaymentMethods::PAYMENT_NAME_PAYLATER_DIRECT_DEBIT_SECURED,
    ];

    private Connection $connection;

    public function __construct(
        Connection $connection
    ) {
        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function isUnzerPayment(array $payment): bool
    {
        return strpos($payment['name'], 'unzer') === 0;
    }

    /**
     * {@inheritdoc}
     */
    public function isUnzerPaymentWithFrame(array $payment): bool
    {
        return strpos($payment['name'], 'unzer') !== false &&
            !empty($payment['attributes']) &&
            !empty($payment['attributes']['core']) &&
            !empty($payment['attributes']['core']->get(Attributes::UNZER_PAYMENT_ATTRIBUTE_PAYMENT_FRAME));
    }

    public function isUnzerPaymentWithFraudPrevention(array $payment): bool
    {
        return strpos($payment['name'], 'unzer') !== false &&
            !empty($payment['attribute']) &&
            !empty($payment['attribute']->get(Attributes::UNZER_PAYMENT_ATTRIBUTE_FRAUD_PREVENTION_USAGE)) &&
            1 === (int) $payment['attribute']->get(Attributes::UNZER_PAYMENT_ATTRIBUTE_FRAUD_PREVENTION_USAGE);
    }

    public function chargeCancellationNeedsCancellationObject(string $paymentId, int $shopId): bool
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $paymentName  = $queryBuilder->select('sPayment.name')
            ->from('s_order', 'sOrder')
            ->leftJoin('sOrder', 's_core_paymentmeans', 'sPayment', 'sOrder.paymentID = sPayment.id')
            ->where('sOrder.temporaryID = :paymentId')
            ->orWhere('sOrder.transactionID = :paymentId')
            ->andWhere('sOrder.language = :shopId')
            ->setParameter('paymentId', $paymentId)
            ->setParameter('shopId', $shopId)
            ->execute()
            ->fetchColumn();

        return in_array($paymentName, self::CANCELLATION_OBJECT_NEEDED_PAYMENT_METHODS, true);
    }
}
