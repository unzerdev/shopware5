<?php

declare(strict_types=1);

namespace UnzerPayment\Services\UnzerPaymentClient;

use RuntimeException;
use Shopware\Bundle\StoreFrontBundle\Service\ContextServiceInterface;
use UnzerPayment\Components\UnzerDebugHandler;
use UnzerPayment\Services\ConfigReader\ConfigReaderServiceInterface;
use UnzerPayment\Services\UnzerPaymentApiLogger\UnzerPaymentApiLoggerServiceInterface;
use UnzerSDK\Unzer;

class UnzerPaymentClientService implements UnzerPaymentClientServiceInterface
{
    /** @var ConfigReaderServiceInterface */
    private $configReaderService;

    /** @var null|ContextServiceInterface */
    private $contextService;

    /** @var UnzerPaymentApiLoggerServiceInterface */
    private $apiLoggerService;

    public function __construct(
        ConfigReaderServiceInterface $configReaderService,
        ContextServiceInterface $contextService,
        UnzerPaymentApiLoggerServiceInterface $apiLoggerService
    ) {
        $this->configReaderService = $configReaderService;
        $this->contextService      = $contextService;
        $this->apiLoggerService    = $apiLoggerService;
    }

    public function getUnzerPaymentClient(?string $locale = null): ?Unzer
    {
        if (empty($locale)) {
            $locale = 'en-GB';

            if ($this->contextService !== null) {
                try {
                    $shopContext = $this->contextService->getShopContext();
                    $shop        = $shopContext->getShop();
                    $locale      = $shop->getLocale()->getLocale();

                    if ($locale) {
                        $locale = str_replace('_', '-', $locale);
                    }
                } catch (\ServiceNotFoundException $e) {
                    return null;
                }
            }
        }

        try {
            $unzer = new Unzer($this->getPrivateKey(), $locale);

            $unzer->setDebugMode((bool) $this->configReaderService->get('extended_logging'));
            $unzer->setDebugHandler((new UnzerDebugHandler($this->apiLoggerService->getPluginLogger())));

            return $unzer;
        } catch (RuntimeException $ex) {
            $this->apiLoggerService->getPluginLogger()->error(
                sprintf('Could not initialize Unzer Payment client due to the following error: %s', $ex->getMessage())
            );
        }

        return null;
    }

    public function getPrivateKey(): string
    {
        $privateKey = $this->configReaderService->get('private_key');

        return $this->getApiKey($privateKey);
    }

    public function getPublicKey(): string
    {
        $publicKey = $this->configReaderService->get('public_key');

        return $this->getApiKey($publicKey);
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
}
