<?php

declare(strict_types=1);

namespace UnzerPayment;

use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\ActivateContext;
use Shopware\Components\Plugin\Context\DeactivateContext;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;
use Shopware\Components\Plugin\Context\UpdateContext;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use UnzerPayment\Components\DependencyInjection\CompilerPass\PaymentStatusMapperCompilerPass;
use UnzerPayment\Components\DependencyInjection\CompilerPass\ViewBehaviorCompilerPass;
use UnzerPayment\Components\DependencyInjection\CompilerPass\WebhookCompilerPass;
use UnzerPayment\Components\UnzerPaymentClassLoader;
use UnzerPayment\Installers\Attributes;
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
            '1.0.0' => function () {
                $modelManager = $this->container->get('models');
                $connection = $this->container->get('dbal_connection');
                $crudService = $this->container->get('shopware_attribute.crud_service');
                $translation = $this->container->get('translation');
                $dataPersister = $this->container->get('shopware_attribute.data_persister');

                (new Document($connection, $translation))->install();
                (new Database($connection))->install();
                (new Attributes($crudService, $modelManager))->install();
                (new PaymentMethods($modelManager, $dataPersister))->install();

                return true;
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
        ];

        foreach ($versionClosures as $version => $versionClosure) {
            if ($oldVersion === null || (version_compare($oldVersion, $version, '<') && version_compare($version, $newVersion, '<='))) {
                if (!$versionClosure($this)) {
                    return false;
                }
            }
        }

        return true;
    }
}
