<?php

declare(strict_types=1);

namespace UnzerPayment\Components\ApplePay;

use Doctrine\DBAL\Connection;
use Shopware\Components\Plugin\Configuration\ReaderInterface;
use Shopware\Components\Plugin\Configuration\WriterInterface;

class CertificateManager
{
    public const TABLE_NAME                                                  = 's_plugin_unzer_apple_pay_configuration';
    public const CONFIG_KEY_APPLE_PAY_PAYMENT_PROCESSING_CERTIFICATE_ID      = 'applePayPaymentProcessingCertificateId';
    public const CONFIG_KEY_APPLE_PAY_MERCHANT_IDENTIFICATION_CERTIFICATE_ID = 'applePayMerchantIdentificationCertificateId';
    public const PATH_FORMAT                                                 = '%s/shop-%s/%s';

    private const APPLE_PAY_CERTIFICATE_PATH                   = 'unzer_payment6_apple_pay_certificates';
    private const MERCHANT_IDENTIFICATION_CERTIFICATE_FILENAME = 'merchant-identification-certificate.pem';
    private const MERCHANT_IDENTIFICATION_KEY_FILENAME         = 'merchant-identification-privatekey.key';

    private ReaderInterface $configReader;
    private WriterInterface $configWriter;
    private string $pluginName;
    private Connection $connection;

    public function __construct(
        WriterInterface $configWriter,
        ReaderInterface $configReader,
        Connection $connection,
        string $pluginName
    ) {
        $this->configReader = $configReader;
        $this->configWriter = $configWriter;
        $this->connection   = $connection;
        $this->pluginName   = $pluginName;
    }

    public function setPaymentProcessingCertificateId(?string $certificateId, ?int $shopId): void
    {
        $this->upsertConfigTable($shopId, $certificateId, 'payment_certificate_id');
    }

    public function setMerchantCertificateId(?string $certificateId, ?int $shopId): void
    {
        $this->upsertConfigTable($shopId, $certificateId, 'merchant_certificate_id');
    }

    public function getMerchantIdentificationCertificatePath(?int $shopId): string
    {
        return sprintf(self::PATH_FORMAT, self::APPLE_PAY_CERTIFICATE_PATH, $shopId, self::MERCHANT_IDENTIFICATION_CERTIFICATE_FILENAME);
    }

    public function getMerchantIdentificationCertificatePathForUpdate(?int $shopId): string
    {
        return sprintf(self::PATH_FORMAT, self::APPLE_PAY_CERTIFICATE_PATH, $shopId, self::MERCHANT_IDENTIFICATION_CERTIFICATE_FILENAME);
    }

    public function getMerchantIdentificationKeyPath(?int $shopId): string
    {
        return sprintf(self::PATH_FORMAT, self::APPLE_PAY_CERTIFICATE_PATH, $shopId, self::MERCHANT_IDENTIFICATION_KEY_FILENAME);
    }

    public function getMerchantIdentificationKeyPathForUpdate(?int $shopId): string
    {
        return sprintf(self::PATH_FORMAT, self::APPLE_PAY_CERTIFICATE_PATH, $shopId, self::MERCHANT_IDENTIFICATION_KEY_FILENAME);
    }

    public function getConfig(string $configKey, ?int $shopId): ?string
    {
        return $this->configReader->getByPluginName($this->pluginName, $shopId)[$configKey] ?? null;
    }

    public function upsertConfigTable(?int $shopId, ?string $certificateId, string $columnName): void
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        $configResult = $this->connection->fetchOne(
            sprintf('SELECT `shop_id` FROM `%s` WHERE `shop_id` = :shopId', self::TABLE_NAME),
            [
                'shopId' => $shopId,
            ]
        );

        if ($configResult === false) {
            $queryBuilder->insert(self::TABLE_NAME)
                ->values([
                    'shop_id'   => ':shopId',
                    $columnName => ':certificateId',
                ])->setParameters([
                    'shopId'        => $shopId,
                    'certificateId' => $certificateId ?: 'NULL',
                ])->execute();
        } else {
            $queryBuilder->update(self::TABLE_NAME, 'config')
                ->set($columnName, ':certificateId')
                ->where('config.shop_id = :shopId')
                ->setParameters([
                    'shopId'        => $shopId,
                    'certificateId' => $certificateId ?: 'NULL',
                ])
                ->execute();
        }
    }
}
