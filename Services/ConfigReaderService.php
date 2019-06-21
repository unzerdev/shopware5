<?php

namespace HeidelPayment\Services;

use Exception;
use Shopware\Bundle\StoreFrontBundle\Service\ContextServiceInterface;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\Plugin\CachedConfigReader;
use Shopware\Models\Shop\Shop;

class ConfigReaderService implements ConfigReaderServiceInterface
{
    /** @var CachedConfigReader */
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
        CachedConfigReader $configReader,
        ContextServiceInterface $contextService,
        ModelManager $modelManager,
        string $pluginName,
        ?Shop $shop = null
    ) {
        $this->configReader   = $configReader;
        $this->contextService = $contextService;
        $this->pluginName     = $pluginName;
        $this->modelManager   = $modelManager;
        $this->shop           = $shop;
    }

    public function get(?string $key = null)
    {
        if ($this->shop === null) {
            try {
                $this->shop = $this->modelManager->find(
                    Shop::class,
                    $this->contextService->getShopContext()->getShop()->getId()
                );
            } catch (Exception $ex) {
                if ($this->shop === null) {
                    $this->shop = $this->modelManager->getRepository(Shop::class)->getActiveDefault();
                }
            }
        }

        if ($key === null) {
            return $this->configReader->getByPluginName($this->pluginName, $this->shop);
        }

        return $this->configReader->getByPluginName($this->pluginName, $this->shop)[$key];
    }
}
