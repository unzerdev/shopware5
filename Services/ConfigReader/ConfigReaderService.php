<?php

declare(strict_types=1);

namespace HeidelPayment\Services\ConfigReader;

use Shopware\Bundle\StoreFrontBundle\Service\ContextServiceInterface;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\Plugin\ConfigReader;
use Shopware\Models\Shop\Shop;
use Throwable;

class ConfigReaderService implements ConfigReaderServiceInterface
{
    /** @var ConfigReader */
    private $configReader;

    /** @var ContextServiceInterface */
    private $contextService;

    /** @var string */
    private $pluginName;

    /** @var ModelManager */
    private $modelManager;

    /** @var null|Shop */
    private $shop;

    public function __construct(
        ConfigReader $configReader,
        ContextServiceInterface $contextService,
        ModelManager $modelManager,
        string $pluginName
    ) {
        $this->configReader   = $configReader;
        $this->contextService = $contextService;
        $this->pluginName     = $pluginName;
        $this->modelManager   = $modelManager;
    }

    public function get(string $key = null, int $shopId = null)
    {
        if ($this->shop === null) {
            try {
                $this->shop = $this->modelManager->find(
                    Shop::class,
                    $shopId ?? $this->contextService->getShopContext()->getShop()->getId()
                );
            } catch (Throwable $ex) {
                $this->shop = $this->modelManager->getRepository(Shop::class)->getActiveDefault();
            }
        }

        if ($key === null) {
            return $this->configReader->getByPluginName($this->pluginName, $this->shop);
        }

        return $this->configReader->getByPluginName($this->pluginName, $this->shop)[$key];
    }
}
