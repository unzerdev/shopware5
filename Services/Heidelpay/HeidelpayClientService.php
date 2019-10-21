<?php

namespace HeidelPayment\Services\Heidelpay;

use HeidelPayment\Services\ConfigReaderServiceInterface;
use HeidelPayment\Services\HeidelpayApiLoggerServiceInterface;
use heidelpayPHP\Heidelpay;
use RuntimeException;
use Shopware\Bundle\StoreFrontBundle\Service\ContextServiceInterface;

class HeidelpayClientService implements HeidelpayClientServiceInterface
{
    /** @var ConfigReaderServiceInterface */
    private $configReaderService;

    /** @var null|ContextServiceInterface */
    private $contextService;

    /** @var HeidelpayApiLoggerServiceInterface */
    private $apiLoggerService;

    public function __construct(ConfigReaderServiceInterface $configReaderService, ContextServiceInterface $contextService, HeidelpayApiLoggerServiceInterface $apiLoggerService)
    {
        $this->configReaderService = $configReaderService;
        $this->contextService      = $contextService;
        $this->apiLoggerService    = $apiLoggerService;
    }

    /**
     * {@inheritdoc}
     */
    public function getHeidelpayClient(): ?Heidelpay
    {
        $locale = 'en_GB';

        if ($this->contextService !== null) {
            $locale = $this->contextService->getShopContext()->getShop()->getLocale()->getLocale();
        }

        try {
            return new Heidelpay($this->getPrivateKey(), $locale);
        } catch (RuntimeException $ex) {
            $this->apiLoggerService->getPluginLogger()->error(sprintf('Could not initialize Heidelpay client due to the following error: %s', $ex->getMessage()));
        }

        return null;
    }

    public function getPrivateKey(): string
    {
        $privateKey = $this->configReaderService->get('private_key');

        return $privateKey ?? '';
    }

    public function getPublicKey(): string
    {
        $publicKey = $this->configReaderService->get('public_key');

        return $publicKey ?? '';
    }
}
