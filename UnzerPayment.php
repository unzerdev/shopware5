<?php

declare(strict_types=1);

namespace UnzerPayment;

use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\ActivateContext;
use Shopware\Components\Plugin\Context\DeactivateContext;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;
use Shopware\Components\Plugin\Context\UpdateContext;
use Shopware\Models\Plugin\Plugin as PluginModel;
use Shopware\Models\Shop\Shop;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use UnzerPayment\Components\DependencyInjection\CompilerPass\PaymentStatusMapperCompilerPass;
use UnzerPayment\Components\DependencyInjection\CompilerPass\ViewBehaviorCompilerPass;
use UnzerPayment\Components\DependencyInjection\CompilerPass\WebhookCompilerPass;
use UnzerPayment\Components\UnzerPaymentClassLoader;
use UnzerPayment\Installers\Attributes;
use UnzerPayment\Installers\Certificates;
use UnzerPayment\Installers\Database;
use UnzerPayment\Installers\Document;
use UnzerPayment\Installers\PaymentMethods;

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    (new UnzerPaymentClassLoader())->register();
}

class UnzerPayment extends Plugin
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new WebhookCompilerPass());
        $container->addCompilerPass(new ViewBehaviorCompilerPass());
        $container->addCompilerPass(new PaymentStatusMapperCompilerPass());

        parent::build($container);
    }

    public function install(InstallContext $context): void
    {
        $this->applyUpdates(null, $context->getCurrentVersion());
    }

    public function activate(ActivateContext $context): void
    {
        $snippetNamespace = $this->container->get('snippets')->getNamespace('backend/unzer_payment/pluginmanager');

        $context->scheduleClearCache(ActivateContext::CACHE_LIST_ALL);
        $context->scheduleMessage($snippetNamespace->get('activate/message'));

        parent::activate($context);
    }

    public function update(UpdateContext $context): void
    {
        $snippetNamespace = $this->container->get('snippets')->getNamespace('backend/unzer_payment/pluginmanager');

        $this->applyUpdates($context->getCurrentVersion(), $context->getUpdateVersion());

        $context->scheduleClearCache(UpdateContext::CACHE_LIST_ALL);
        $context->scheduleMessage($snippetNamespace->get('update/message'));

        parent::update($context);
    }

    public function uninstall(UninstallContext $context): void
    {
        $snippetNamespace = $this->container->get('snippets')->getNamespace('backend/unzer_payment/pluginmanager');

        (new PaymentMethods($this->container->get('models'), $this->container->get('shopware_attribute.data_persister')))->uninstall();

        if (!$context->keepUserData()) {
            (new Certificates($this->container->get('dbal_connection'), $this->container->get('shopware.filesystem.private')))->uninstall();
            (new Database($this->container->get('dbal_connection')))->uninstall();
            (new Document($this->container->get('dbal_connection'), $this->container->get('translation')))->uninstall();
            (new Attributes($this->container->get('shopware_attribute.crud_service'), $this->container->get('models')))->uninstall();
        }

        $context->scheduleMessage($snippetNamespace->get('uninstall/message'));
    }

    public function deactivate(DeactivateContext $context): void
    {
        $context->scheduleClearCache(DeactivateContext::CACHE_LIST_ALL);

        parent::deactivate($context);
    }

    public function getVersion(): string
    {
        return $this->container->get('dbal_connection')->createQueryBuilder()
            ->select('version')
            ->from('s_core_plugins')
            ->where('name = :name')
            ->setParameter('name', $this->getName())
            ->execute()->fetchColumn();
    }

    private function applyUpdates(?string $oldVersion = null, ?string $newVersion = null): bool
    {
        $versionClosures = [
            '1.0.0' => function (): void {
                $modelManager = $this->container->get('models');
                $connection = $this->container->get('dbal_connection');
                $crudService = $this->container->get('shopware_attribute.crud_service');
                $translation = $this->container->get('translation');
                $dataPersister = $this->container->get('shopware_attribute.data_persister');

                (new Document($connection, $translation))->install();
                (new Database($connection))->install();
                (new Attributes($crudService, $modelManager))->install();
                (new PaymentMethods($modelManager, $dataPersister))->install();
            },
            '1.1.0' => function () use ($oldVersion, $newVersion): void {
                $modelManager = $this->container->get('models');
                $dataPersister = $this->container->get('shopware_attribute.data_persister');

                (new PaymentMethods($modelManager, $dataPersister))->update($oldVersion ?? '', $newVersion ?? '');
            },
            '1.2.0' => function () use ($oldVersion, $newVersion): void {
                $connection = $this->container->get('dbal_connection');

                (new Database($connection))->update($oldVersion ?? '', $newVersion ?? '');
            },
            '1.2.1' => function (): void {
                $connection = $this->container->get('dbal_connection');
                $subshopIdColumnExists = $connection->fetchOne('SHOW COLUMNS FROM `s_plugin_unzer_order_ext_backup` LIKE \'subshop_id\';');

                if (!$subshopIdColumnExists) {
                    $connection->executeStatement('ALTER TABLE s_plugin_unzer_order_ext_backup ADD COLUMN subshop_id INT NOT NULL AFTER dispatch_id;');
                }
            },
            '1.3.1' => function () use ($oldVersion, $newVersion): void {
                $connection = $this->container->get('dbal_connection');

                (new Database($connection))->update($oldVersion ?? '', $newVersion ?? '');
            },
            '1.4.0' => function () use ($oldVersion, $newVersion): void {
                $modelManager = $this->container->get('models');
                $dataPersister = $this->container->get('shopware_attribute.data_persister');
                $crudService = $this->container->get('shopware_attribute.crud_service');

                (new Attributes($crudService, $modelManager))->update($oldVersion ?? '', $newVersion ?? '');
                (new PaymentMethods($modelManager, $dataPersister))->update($oldVersion ?? '', $newVersion ?? '');
            },
            '1.5.0' => function () use ($oldVersion, $newVersion): void {
                $modelManager = $this->container->get('models');
                $dataPersister = $this->container->get('shopware_attribute.data_persister');

                (new PaymentMethods($modelManager, $dataPersister))->update($oldVersion ?? '', $newVersion ?? '');
            },
            '1.6.0' => function (): void {
                $configReader = $this->container->get('shopware.plugin.config_reader');
                $configWriter = $this->container->get('shopware.plugin.config_writer');
                $modelManager = $this->container->get('models');
                $pluginName = 'UnzerPayment';
                $plugin = $modelManager->getRepository(PluginModel::class)->findOneBy(['name' => $pluginName]);

                /** @var Shop $shop */
                foreach ($modelManager->getRepository(Shop::class)->findAll() as $shop) {
                    $config = $configReader->getByPluginName($pluginName, $shop);

                    $newConfig = [
                        'credit_card_bookingmode' => strtolower(str_replace('register', '', $config['credit_card_bookingmode'])),
                        'paypal_bookingmode' => strtolower(str_replace('register', '', $config['paypal_bookingmode'])),
                    ];

                    $configWriter->savePluginConfig($plugin, $newConfig, $shop);
                }
            },
            '1.8.0' => function () use ($oldVersion, $newVersion): void {
                $modelManager = $this->container->get('models');
                $dataPersister = $this->container->get('shopware_attribute.data_persister');

                (new PaymentMethods($modelManager, $dataPersister))->update($oldVersion ?? '', $newVersion ?? '');
            },
            '1.9.0' => function () use ($oldVersion, $newVersion): void {
                $modelManager = $this->container->get('models');
                $dataPersister = $this->container->get('shopware_attribute.data_persister');

                (new PaymentMethods($modelManager, $dataPersister))->update($oldVersion ?? '', $newVersion ?? '');
            },
            '1.9.1' => function () use ($oldVersion, $newVersion): void {
                $modelManager = $this->container->get('models');
                $dataPersister = $this->container->get('shopware_attribute.data_persister');
                (new PaymentMethods($modelManager, $dataPersister))->deprecateGiropay();
            },
            '1.10.0' => function () use ($oldVersion, $newVersion): void {
                $modelManager = $this->container->get('models');
                $dataPersister = $this->container->get('shopware_attribute.data_persister');
                (new PaymentMethods($modelManager, $dataPersister))->update($oldVersion ?? '', $newVersion ?? '');
            },
            '1.11.0' => function () use ($oldVersion, $newVersion): void {
                $modelManager = $this->container->get('models');
                $dataPersister = $this->container->get('shopware_attribute.data_persister');
                (new PaymentMethods($modelManager, $dataPersister))->update($oldVersion ?? '', $newVersion ?? '');
            },
            '1.12.0' => function () use ($oldVersion, $newVersion): void {
                $modelManager = $this->container->get('models');
                $dataPersister = $this->container->get('shopware_attribute.data_persister');
                (new PaymentMethods($modelManager, $dataPersister))->update($oldVersion ?? '', $newVersion ?? '');
            },
        ];

        foreach ($versionClosures as $version => $versionClosure) {
            if ($oldVersion === null
                || (
                    version_compare($oldVersion, $version, '<') // if closure is greater than oldVersion
                    && version_compare($version, $newVersion, '<=') // if closure is lower/equal than updateVersion
                )) {
                $versionClosure($this);
            }
        }

        return true;
    }
}
