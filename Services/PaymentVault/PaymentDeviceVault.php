<?php

namespace HeidelPayment\Services\PaymentVault;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Enlight_Components_Session_Namespace as Session;
use HeidelPayment\Services\AddressHashGeneratorInterface;
use HeidelPayment\Services\PaymentVault\Struct\VaultedDeviceStruct;
use heidelpayPHP\Resources\PaymentTypes\BasePaymentType;
use PDO;

class PaymentDeviceVault implements PaymentVaultServiceInterface
{
    /** @var Session */
    private $session;

    /** @var Connection */
    private $connection;

    /** @var PaymentDeviceFactoryInterface */
    private $paymentDeviceFactory;

    /** @var AddressHashGeneratorInterface */
    private $addressHashGenerator;

    public function __construct(Session $session, Connection $connection, PaymentDeviceFactoryInterface $paymentDeviceFactory, AddressHashGeneratorInterface $addressHashGenerator)
    {
        $this->session              = $session;
        $this->connection           = $connection;
        $this->paymentDeviceFactory = $paymentDeviceFactory;
        $this->addressHashGenerator = $addressHashGenerator;
    }

    /**
     * {@inheritdoc}
     */
    public function getVaultedDevicesForCurrentUser(array $billingAddress, array $shippingAddress): array
    {
        $userId      = $this->session->offsetGet('sUserId');
        $addressHash = $this->addressHashGenerator->generateHash($billingAddress, $shippingAddress);

        $queryBuilder = $this->connection->createQueryBuilder();
        $result       = [];

        $deviceData = $queryBuilder->select('*')->from('s_plugin_heidel_payment_vault')
            ->where('user_id = :userId')
            ->andWhere('address_hash = :addressHash')
            ->setParameter('userId', $userId)
            ->setParameter('addressHash', $addressHash)
            ->execute()->fetchAll();

        foreach ($deviceData as $device) {
            $result[$device['device_type']][] = $this->paymentDeviceFactory->getPaymentDevice($device);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteDeviceFromVault(int $userId, int $vaultId)
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $queryBuilder->delete('s_plugin_heidel_payment_vault')
            ->where('user_id = :userId')
            ->andWhere('id = :vaultId')
            ->setParameters([
                'userId'  => $userId,
                'vaultId' => $vaultId,
            ])
            ->execute();
    }

    /**
     * {@inheritdoc}
     *
     * @see VaultedDeviceStruct::DEVICE_TYPE_CARD
     */
    public function saveDeviceToVault(BasePaymentType $paymentType, string $deviceType, array $billingAddress, array $shippingAddress)
    {
        $addressHash = $this->addressHashGenerator->generateHash($billingAddress, $shippingAddress);

        $this->connection->createQueryBuilder()
            ->insert('s_plugin_heidel_payment_vault')
            ->values([
                'user_id'      => ':userId',
                'device_type'  => ':deviceType',
                'type_id'      => ':typeId',
                'data'         => ':data',
                'date'         => ':date',
                'address_hash' => ':addressHash',
            ])->setParameters([
                'userId'      => $this->session->offsetGet('sUserId'),
                'deviceType'  => $deviceType,
                'typeId'      => $paymentType->getId(),
                'data'        => json_encode($paymentType->expose()),
                'date'        => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
                'addressHash' => $addressHash,
            ])->execute();
    }

    /**
     * {@inheritdoc}
     */
    public function hasVaultedSepaMandate(int $userId, string $iban, array $billingAddress, array $shippingAddress): bool
    {
        $addressHash = $this->addressHashGenerator->generateHash($billingAddress, $shippingAddress);

        return $this->checkForVaultedSepaMandate($userId, $iban, $addressHash, VaultedDeviceStruct::DEVICE_TYPE_SEPA_MANDATE);
    }

    /**
     * {@inheritdoc}
     */
    public function hasVaultedSepaGuaranteedMandate(int $userId, string $iban, array $billingAddress, array $shippingAddress): bool
    {
        $addressHash = $this->addressHashGenerator->generateHash($billingAddress, $shippingAddress);

        return $this->checkForVaultedSepaMandate($userId, $iban, $addressHash, VaultedDeviceStruct::DEVICE_TYPE_SEPA_MANDATE_GUARANTEED);
    }

    /**
     * {@inheritdoc}
     */
    private function checkForVaultedSepaMandate(int $userId, string $iban, string $addressHash, string $deviceType): bool
    {
        $iban = str_replace(' ', '', $iban);

        $queryBuilder = $this->connection->createQueryBuilder();
        $vaultedData  = $queryBuilder
            ->select('data')
            ->from('s_plugin_heidel_payment_vault')
            ->where('device_type = :deviceType')
            ->andWhere('user_id = :userId')
            ->andWhere('address_hash = :addressHash')
            ->setParameters([
                'deviceType'  => $deviceType,
                'userId'      => $userId,
                'addressHash' => $addressHash,
            ])
            ->execute()->fetchAll(PDO::FETCH_COLUMN);

        foreach ($vaultedData as $mandate) {
            $vaultedIban = json_decode($mandate, true)['iban'];
            $vaultedIban = str_replace(' ', '', $vaultedIban);

            if ($iban === $vaultedIban) {
                return true;
            }
        }

        return false;
    }
}
