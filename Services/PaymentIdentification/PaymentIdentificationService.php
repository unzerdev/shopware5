<?php

declare(strict_types=1);

namespace UnzerPayment\Services\PaymentIdentification;

use Doctrine\DBAL\Connection;
use UnzerPayment\Components\BookingMode;
use UnzerPayment\Installers\Attributes;
use UnzerPayment\Installers\PaymentMethods;
use UnzerPayment\Services\ConfigReader\ConfigReaderServiceInterface;

class PaymentIdentificationService implements PaymentIdentificationServiceInterface
{
    /** @var ConfigReaderServiceInterface */
    private $configReader;

    /** @var Connection */
    private $connection;

    public function __construct(
        ConfigReaderServiceInterface $configReader,
        Connection $connection
    ) {
        $this->configReader = $configReader;
        $this->connection   = $connection;
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
            !empty($payment['attributes']['core']->get(Attributes::UNZER_PAYMENT_ATTRIBUTE_PAYMENT_FRAME) &&
            $this->shouldDisplayFrame($payment['name']));
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
            ->andWhere('sOrder.language = :shopId')
            ->setParameter('paymentId', $paymentId)
            ->setParameter('shopId', $shopId)
            ->execute()
            ->fetchOne();

        return $paymentName === PaymentMethods::PAYMENT_NAME_PAYLATER_INVOICE;
    }

    private function shouldDisplayFrame(string $paymentName): bool
    {
        if ($paymentName === PaymentMethods::PAYMENT_NAME_PAYPAL &&
            !in_array($this->configReader->get('paypal_bookingmode'), [BookingMode::AUTHORIZE_REGISTER, BookingMode::CHARGE_REGISTER])) {
            return false;
        }

        return true;
    }
}
