<?php

declare(strict_types=1);

namespace UnzerPayment\Services\UnzerPaymentClient;

use Doctrine\DBAL\Connection;
use PDO;
use RuntimeException;
use Shopware\Bundle\StoreFrontBundle\Service\ContextServiceInterface;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Shop\Shop;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use UnzerPayment\Components\UnzerDebugHandler;
use UnzerPayment\Installers\PaymentMethods;
use UnzerPayment\Services\ConfigReader\ConfigReaderServiceInterface;
use UnzerPayment\Services\UnzerPaymentApiLogger\UnzerPaymentApiLoggerServiceInterface;
use UnzerSDK\Unzer;

class UnzerPaymentClientService implements UnzerPaymentClientServiceInterface
{
    // These constants are used to identify the client type in the config
    // They are the prefix of the config keys for the private and public key
    public const KEYPAIR_TYPE_GENERAL                               = 'general';
    public const KEYPAIR_TYPE_PAYLATER_INVOICE_B2B_EUR              = 'paylater_invoice_b2b_eur';
    public const KEYPAIR_TYPE_PAYLATER_INVOICE_B2B_CHF              = 'paylater_invoice_b2b_chf';
    public const KEYPAIR_TYPE_PAYLATER_INVOICE_B2C_EUR              = 'paylater_invoice_b2c_eur';
    public const KEYPAIR_TYPE_PAYLATER_INVOICE_B2C_CHF              = 'paylater_invoice_b2c_chf';
    public const KEYPAIR_TYPE_PAYLATER_INSTALLMENT_B2C_EUR          = 'paylater_installment_b2c_eur';
    public const KEYPAIR_TYPE_PAYLATER_INSTALLMENT_B2C_CHF          = 'paylater_installment_b2c_chf';
    public const KEYPAIR_TYPE_PAYLATER_DIRECT_DEBIT_SECURED_B2C_EUR = 'paylater_direct_debit_secured_b2c_eur';

    public const PRIVATE_CONFIG_KEYS = [
        self::KEYPAIR_TYPE_GENERAL                               => 'private_key',
        self::KEYPAIR_TYPE_PAYLATER_INVOICE_B2B_EUR              => self::KEYPAIR_TYPE_PAYLATER_INVOICE_B2B_EUR . '_private_key',
        self::KEYPAIR_TYPE_PAYLATER_INVOICE_B2B_CHF              => self::KEYPAIR_TYPE_PAYLATER_INVOICE_B2B_CHF . '_private_key',
        self::KEYPAIR_TYPE_PAYLATER_INVOICE_B2C_EUR              => self::KEYPAIR_TYPE_PAYLATER_INVOICE_B2C_EUR . '_private_key',
        self::KEYPAIR_TYPE_PAYLATER_INVOICE_B2C_CHF              => self::KEYPAIR_TYPE_PAYLATER_INVOICE_B2C_CHF . '_private_key',
        self::KEYPAIR_TYPE_PAYLATER_INSTALLMENT_B2C_EUR          => self::KEYPAIR_TYPE_PAYLATER_INSTALLMENT_B2C_EUR . '_private_key',
        self::KEYPAIR_TYPE_PAYLATER_INSTALLMENT_B2C_CHF          => self::KEYPAIR_TYPE_PAYLATER_INSTALLMENT_B2C_CHF . '_private_key',
        self::KEYPAIR_TYPE_PAYLATER_DIRECT_DEBIT_SECURED_B2C_EUR => self::KEYPAIR_TYPE_PAYLATER_DIRECT_DEBIT_SECURED_B2C_EUR . '_private_key',
    ];

    public const PUBLIC_CONFIG_KEYS = [
        self::KEYPAIR_TYPE_GENERAL                               => 'public_key',
        self::KEYPAIR_TYPE_PAYLATER_INVOICE_B2B_EUR              => self::KEYPAIR_TYPE_PAYLATER_INVOICE_B2B_EUR . '_public_key',
        self::KEYPAIR_TYPE_PAYLATER_INVOICE_B2B_CHF              => self::KEYPAIR_TYPE_PAYLATER_INVOICE_B2B_CHF . '_public_key',
        self::KEYPAIR_TYPE_PAYLATER_INVOICE_B2C_EUR              => self::KEYPAIR_TYPE_PAYLATER_INVOICE_B2C_EUR . '_public_key',
        self::KEYPAIR_TYPE_PAYLATER_INVOICE_B2C_CHF              => self::KEYPAIR_TYPE_PAYLATER_INVOICE_B2C_CHF . '_public_key',
        self::KEYPAIR_TYPE_PAYLATER_INSTALLMENT_B2C_EUR          => self::KEYPAIR_TYPE_PAYLATER_INSTALLMENT_B2C_EUR . '_public_key',
        self::KEYPAIR_TYPE_PAYLATER_INSTALLMENT_B2C_CHF          => self::KEYPAIR_TYPE_PAYLATER_INSTALLMENT_B2C_CHF . '_public_key',
        self::KEYPAIR_TYPE_PAYLATER_DIRECT_DEBIT_SECURED_B2C_EUR => self::KEYPAIR_TYPE_PAYLATER_DIRECT_DEBIT_SECURED_B2C_EUR . '_public_key',
    ];

    private ConfigReaderServiceInterface $configReaderService;

    private ?ContextServiceInterface $contextService;

    private UnzerPaymentApiLoggerServiceInterface $apiLoggerService;

    private ModelManager $modelManager;

    private Connection $connection;

    public function __construct(
        ConfigReaderServiceInterface $configReaderService,
        ContextServiceInterface $contextService,
        UnzerPaymentApiLoggerServiceInterface $apiLoggerService,
        ModelManager $modelManager,
        Connection $connection
    ) {
        $this->configReaderService = $configReaderService;
        $this->contextService      = $contextService;
        $this->apiLoggerService    = $apiLoggerService;
        $this->modelManager        = $modelManager;
        $this->connection          = $connection;
    }

    public function getGeneralUnzerPaymentClient(?string $locale = null, ?int $shopId = null): ?Unzer
    {
        if (empty($locale)) {
            $locale = $this->getLocale();

            if ($locale === null) {
                return null;
            }
        }

        try {
            $unzer = new Unzer($this->getPrivateKey($shopId, self::PRIVATE_CONFIG_KEYS[self::KEYPAIR_TYPE_GENERAL]), $locale);

            $unzer->setDebugMode((bool) $this->configReaderService->get('extended_logging', $shopId));
            $unzer->setDebugHandler((new UnzerDebugHandler($this->apiLoggerService->getPluginLogger())));

            return $unzer;
        } catch (RuntimeException $e) {
            $this->apiLoggerService->getPluginLogger()->error(
                sprintf('Could not initialize general Unzer Payment client due to the following error: %s', $e->getMessage())
            );
        }

        return null;
    }

    public function getUnzerPaymentClientByType(string $keypairType, ?string $locale = null, ?int $shopId = null): ?Unzer
    {
        if (empty($locale)) {
            $locale = $this->getLocale();

            if ($locale === null) {
                return null;
            }
        }

        try {
            $unzer = new Unzer($this->getPrivateKeyByType($keypairType, $shopId), $locale);

            $unzer->setDebugMode((bool) $this->configReaderService->get('extended_logging', $shopId));
            $unzer->setDebugHandler((new UnzerDebugHandler($this->apiLoggerService->getPluginLogger())));

            return $unzer;
        } catch (RuntimeException $ex) {
            $this->apiLoggerService->getPluginLogger()->error(
                sprintf('Could not initialize Unzer Payment client due to the following error: %s', $ex->getMessage())
            );
        }

        return null;
    }

    public function getUnzerPaymentClientByPublicKey(string $publicKey): ?Unzer
    {
        foreach ($this->modelManager->getRepository(Shop::class)->findAll() as $shop) {
            foreach (self::PUBLIC_CONFIG_KEYS as $keypairType => $publicConfigKey) {
                $config = $this->configReaderService->get($publicConfigKey, $shop->getId());

                if ($config === $publicKey) {
                    return $this->getUnzerPaymentClientByType($keypairType, null, $shop->getId());
                }
            }
        }

        return null;
    }

    public function getUnzerPaymentClientByPaymentId(string $paymentId): ?Unzer
    {
        try {
            $order = $this->connection->createQueryBuilder()
                // we need 'language' to get the real subshop ID
                ->select('o.currency AS currency', 'o.language AS languageShopId', 'ba.company AS company', 'c.countryiso AS countryIso', 'pm.name AS paymentName')
                ->from('s_order', 'o')
                    ->leftJoin('o', 's_order_billingaddress', 'ba', 'o.id = ba.orderID')
                    ->leftJoin('ba', 's_core_countries', 'c', 'ba.countryID = c.id')
                    ->leftJoin('o', 's_core_paymentmeans', 'pm', 'o.paymentID = pm.id')
                ->where('transactionID = :paymentId')
                ->orWhere('temporaryID = :paymentId')
                ->setParameter('paymentId', $paymentId)
                ->execute()->fetchAll(PDO::FETCH_ASSOC);
            $order = $order[0] ?? null;
        } catch (\Throwable $e) {
            $this->apiLoggerService->getPluginLogger()->error(
                sprintf('Could not initialize Unzer Payment client due to the following error: %s', $e->getMessage())
            );

            return null;
        }

        $keypairType = $this->getKeypairType($order['paymentName'], $order['currency'], !empty($order['company']));

        return $this->getUnzerPaymentClientByType($keypairType, $order['countryIso'], (int) $order['languageShopId']);
    }

    /**
     * @return array<string, Unzer> E. g. ['general' => Unzer, 'paylater_invoice_b2b_eur' => Unzer, ...]
     */
    public function getExistingKeypairClients(?string $locale = null, ?int $shopId = null): ?array
    {
        if (empty($locale)) {
            $locale = $this->getLocale();

            if ($locale === null) {
                return null;
            }
        }

        $clients      = [];
        $debugMode    = (bool) $this->configReaderService->get('extended_logging', $shopId);
        $debugHandler = new UnzerDebugHandler($this->apiLoggerService->getPluginLogger());
        foreach (self::PRIVATE_CONFIG_KEYS as $keypairType => $privateKeyConfig) {
            $privateKey = $this->configReaderService->get($privateKeyConfig, $shopId);

            if (empty($privateKey)) {
                continue;
            }

            $apiKey = $this->getApiKey($privateKey);

            if (empty($apiKey)) {
                continue;
            }

            try {
                $unzer = new Unzer($apiKey, $locale);
                $unzer->setDebugMode($debugMode);
                $unzer->setDebugHandler($debugHandler);

                $clients[$keypairType] = $unzer;
            } catch (RuntimeException $ex) {
                $this->apiLoggerService->getPluginLogger()->error(
                    sprintf('Could not initialize Unzer Payment %s client due to the following error: %s', $keypairType, $ex->getMessage())
                );
            }
        }

        return $clients;
    }

    public function getPrivateKey(?int $shopId = null, string $configKey = 'private_key'): string
    {
        $privateKey = $this->configReaderService->get($configKey, $shopId);

        return $this->getApiKey($privateKey ?? '');
    }

    public function getPrivateKeyByType(string $keypairType, ?int $shopId = null): ?string
    {
        if (!array_key_exists($keypairType, self::PRIVATE_CONFIG_KEYS)) {
            return null;
        }

        return $this->getPrivateKey($shopId, self::PRIVATE_CONFIG_KEYS[$keypairType]);
    }

    public function getPublicKey(?int $shopId = null): string
    {
        $publicKey = $this->configReaderService->get('public_key', $shopId);

        return $this->getApiKey($publicKey);
    }

    public function getKeypairType(string $paymentName, string $currency, bool $isB2b): string
    {
        if ($paymentName === PaymentMethods::PAYMENT_NAME_PAYLATER_INVOICE) {
            if ($isB2b) {
                if ($currency === 'EUR') {
                    return self::KEYPAIR_TYPE_PAYLATER_INVOICE_B2B_EUR;
                }

                if ($currency === 'CHF') {
                    return self::KEYPAIR_TYPE_PAYLATER_INVOICE_B2B_CHF;
                }
            }

            if ($currency === 'EUR') {
                return self::KEYPAIR_TYPE_PAYLATER_INVOICE_B2C_EUR;
            }

            if ($currency === 'CHF') {
                return self::KEYPAIR_TYPE_PAYLATER_INVOICE_B2C_CHF;
            }
        }

        if ($paymentName === PaymentMethods::PAYMENT_NAME_PAYLATER_INSTALLMENT && !$isB2b) {
            if ($currency === 'EUR') {
                return self::KEYPAIR_TYPE_PAYLATER_INSTALLMENT_B2C_EUR;
            }

            if ($currency === 'CHF') {
                return self::KEYPAIR_TYPE_PAYLATER_INSTALLMENT_B2C_CHF;
            }
        }

        if ($paymentName === PaymentMethods::PAYMENT_NAME_PAYLATER_DIRECT_DEBIT_SECURED && !$isB2b) {
            if ($currency === 'EUR') {
                return self::KEYPAIR_TYPE_PAYLATER_DIRECT_DEBIT_SECURED_B2C_EUR;
            }
        }

        return self::KEYPAIR_TYPE_GENERAL;
    }

    public function publicKeyExists(string $publicKey): bool
    {
        foreach ($this->modelManager->getRepository(Shop::class)->findAll() as $shop) {
            foreach (self::PUBLIC_CONFIG_KEYS as $publicConfigKey) {
                $config = $this->configReaderService->get($publicConfigKey, $shop->getId());

                if ($config === $publicKey) {
                    return true;
                }
            }
        }

        return false;
    }

    private function getApiKey(string $key): string
    {
        if ($key === '') {
            return $key;
        }

        $transMode = $this->configReaderService->get('transaction_mode');

        $explodedKey = explode('-', $key);

        $explodedKey[0] = $transMode === 'live' ? 'p' : 's';

        return implode('-', $explodedKey);
    }

    private function getLocale(): ?string
    {
        $locale = 'en-GB';

        if ($this->contextService !== null) {
            try {
                $shopContext = $this->contextService->getShopContext();
                $shop        = $shopContext->getShop();
                $locale      = $shop->getLocale()->getLocale();

                if ($locale) {
                    $locale = str_replace('_', '-', $locale);
                }
            } catch (ServiceNotFoundException $e) {
                return null;
            }
        }

        return $locale;
    }
}
