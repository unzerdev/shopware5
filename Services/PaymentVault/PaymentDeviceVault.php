<?php

namespace HeidelPayment\Services\PaymentVault;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Enlight_Components_Session_Namespace as Session;
use HeidelPayment\Services\PaymentVault\Struct\VaultedDeviceStruct;
use heidelpayPHP\Resources\PaymentTypes\BasePaymentType;

class PaymentDeviceVault implements PaymentVaultServiceInterface
{
    /** @var Session */
    private $session;

    /** @var Connection */
    private $connection;

    /** @var PaymentDeviceFactoryInterface */
    private $paymentDeviceFactory;

    public function __construct(Session $session, Connection $connection, PaymentDeviceFactoryInterface $paymentDeviceFactory)
    {
        $this->session              = $session;
        $this->connection           = $connection;
        $this->paymentDeviceFactory = $paymentDeviceFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getVaultedDevicesForCurrentUser(): array
    {
        $userId       = $this->session->offsetGet('sUserId');
        $queryBuilder = $this->connection->createQueryBuilder();
        $result       = [];

        $deviceData = $queryBuilder->select('*')->from('s_plugin_heidel_payment_vault')
            ->where('user_id = :userId')
            ->setParameter('userId', $userId)
            ->execute()->fetchAll();

        foreach ($deviceData as $device) {
            $result[$device['device_type']][] = $this->paymentDeviceFactory->getPaymentDevice($device);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @see VaultedDeviceStruct::DEVICE_TYPE_CARD
     */
    public function saveDeviceToVault(BasePaymentType $paymentType, int $deviceTypeId): void
    {
        $this->connection->createQueryBuilder()
            ->insert('s_plugin_heidel_payment_vault')
            ->values([
                'user_id'     => ':userId',
                'device_type' => ':deviceTypeId',
                'type_id'     => ':typeId',
                'data'        => ':data',
                'date'        => ':date',
            ])->setParameters([
                'userId'       => $this->session->offsetGet('sUserId'),
                'deviceTypeId' => $deviceTypeId,
                'typeId'       => $paymentType->getId(),
                'data'         => json_encode($paymentType->expose()),
                'date'         => new DateTimeImmutable(),
            ])->execute();
    }
}
