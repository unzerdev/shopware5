<?php

declare(strict_types=1);

namespace UnzerPayment\Services\UnzerPaymentClient;

use HeidelPayment\Services\HeidelpayApiLogger\HeidelpayApiLoggerServiceInterface;
use heidelpayPHP\Heidelpay;
use RuntimeException;
use Shopware\Bundle\StoreFrontBundle\Service\ContextServiceInterface;
use UnzerPayment\Components\UnzerDebugHandler;
use UnzerPayment\Services\ConfigReader\ConfigReaderServiceInterface;
use UnzerPayment\Services\UnzerPaymentApiLogger\UnzerPaymentApiLoggerServiceInterface;

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
    )
    {
        $this->configReaderService = $configReaderService;
        $this->contextService      = $contextService;
        $this->apiLoggerService    = $apiLoggerService;
    }

    /**
     * {@inheritdoc}
     */
    public function getUnzerPaymentClient(): ?Heidelpay
    {
        $locale = 'en-GB';

        if ($this->contextService !== null) {
            $locale = $this->contextService->getShopContext()->getShop()->getLocale()->getLocale();

            if ($locale) {
                $locale = str_replace('_', '-', $locale);
            }
        }

        try {
            $heidelpay = new Heidelpay($this->getPrivateKey(), $locale);

            $heidelpay->setDebugMode((bool) $this->configReaderService->get('extended_logging'));
            $heidelpay->setDebugHandler((new UnzerDebugHandler($this->apiLoggerService->getPluginLogger())));

            return $heidelpay;
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
