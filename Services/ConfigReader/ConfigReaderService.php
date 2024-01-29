<?php

declare(strict_types=1);

namespace UnzerPayment\Services\ConfigReader;

use Shopware\Bundle\StoreFrontBundle\Service\ContextServiceInterface;
use Shopware\Components\Model\ModelManager;
use Shopware\Components\Plugin\ConfigReader;
use Shopware\Models\Shop\Shop;
use Throwable;

class ConfigReaderService implements ConfigReaderServiceInterface
{
    private ConfigReader $configReader;

    private ContextServiceInterface $contextService;

    private string $pluginName;

    private ModelManager $modelManager;

    /** @var Shop[] */
    private array $shops = [];

    private ?Shop $activeDefaultShop = null;

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

    public function get(string $key = null, ?int $shopId = null)
    {
        if ($this->activeDefaultShop === null) {
            try {
                $this->activeDefaultShop = $this->modelManager->find(
                    Shop::class,
                    $this->contextService->getShopContext()->getShop()->getId()
                );
            } catch (Throwable $ex) {
                $this->activeDefaultShop = $this->modelManager->getRepository(Shop::class)->getActiveDefault();
            } finally {
                $this->shops[$this->activeDefaultShop->getId()] = $this->activeDefaultShop;
            }
        }

        if ($shopId !== null && !array_key_exists($shopId, $this->shops)) {
            $shop = $this->modelManager->find(
                Shop::class,
                $shopId
            );

            //no shop with id found use activeDefault as fallback
            $this->shops[$shopId] = $shop ?? $this->activeDefaultShop;
        }

        $shopIdForConfig = $shopId ?? $this->activeDefaultShop->getId();

        if ($key === null) {
            return $this->configReader->getByPluginName($this->pluginName, $this->shops[$shopIdForConfig]);
        }

        return $this->configReader->getByPluginName($this->pluginName, $this->shops[$shopIdForConfig])[$key];
    }
}
