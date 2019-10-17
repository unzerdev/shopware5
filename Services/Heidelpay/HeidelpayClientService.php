<?php

namespace HeidelPayment\Services\Heidelpay;

use HeidelPayment\Services\ConfigReaderServiceInterface;
use heidelpayPHP\Heidelpay;
use heidelpayPHP\Interfaces\DebugHandlerInterface;
use Shopware\Bundle\StoreFrontBundle\Service\ContextServiceInterface;

class HeidelpayClientService implements HeidelpayClientServiceInterface
{
    /** @var ConfigReaderServiceInterface */
    private $configReaderService;

    /** @var null|ContextServiceInterface */
    private $contextService;

    /** @var DebugHandlerInterface */
    private $debugHandler;

    public function __construct(ConfigReaderServiceInterface $configReaderService, ContextServiceInterface $contextService, DebugHandlerInterface $debugHandler)
    {
        $this->configReaderService = $configReaderService;
        $this->contextService      = $contextService;
        $this->debugHandler        = $debugHandler;
    }

    /**
     * {@inheritdoc}
     */
    public function getHeidelpayClient(): Heidelpay
    {
        $locale = 'en_GB';

        if ($this->contextService !== null) {
            $locale = $this->contextService->getShopContext()->getShop()->getLocale()->getLocale();
        }

        $transactionMode = $this->configReaderService->get('transaction_mode');

        $client = new Heidelpay($this->getPrivateKey(), $locale);
        $client->setDebugMode($transactionMode === 'test');
        $client->setDebugHandler($this->debugHandler);

        return $client;
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

    private function getApiKey(string $key)
    {
        if ($key === '') {
            return $key;
        }

        $transMode   = $this->configReaderService->get('transaction_mode');
        $explodedKey = explode('-', $key);

        $explodedKey[0] = $transMode === 'live' ? 'p' : 's';

        return implode('-', $explodedKey);
    }
}
