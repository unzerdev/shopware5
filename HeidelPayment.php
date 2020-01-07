<?php

namespace HeidelPayment;

use HeidelPayment\Components\DependencyInjection\ViewBehaviorCompilerPass;
use HeidelPayment\Components\DependencyInjection\WebhookCompilerPass;
use HeidelPayment\Installers\Attributes;
use HeidelPayment\Installers\Database;
use HeidelPayment\Installers\PaymentMethods;
use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\ActivateContext;
use Shopware\Components\Plugin\Context\DeactivateContext;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;
use Shopware\Components\Plugin\Context\UpdateContext;
use Symfony\Component\DependencyInjection\ContainerBuilder;

//Load the heidelpay-php SDK
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

class HeidelPayment extends Plugin
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new ViewBehaviorCompilerPass());
        $container->addCompilerPass(new WebhookCompilerPass());

        parent::build($container);
    }

    /**
     * {@inheritdoc}
     */
    public function install(InstallContext $context)
    {
        $this->applyUpdates(null, $context->getCurrentVersion());

        parent::install($context);
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall(UninstallContext $context)
    {
        $snippetNamespace = $this->container->get('snippets')->getNamespace('backend/heidel_payment/pluginmanager');

        if (!$context->keepUserData()) {
            (new Database($this->container->get('dbal_connection')))->uninstall();
            (new Attributes($this->container->get('shopware_attribute.crud_service'), $this->container->get('models')))->uninstall();
        }

        (new PaymentMethods($this->container->get('models')))->uninstall();

        $context->scheduleClearCache(InstallContext::CACHE_LIST_ALL);
        $context->scheduleMessage($snippetNamespace->get('uninstall/message'));
    }

    /**
     * {@inheritdoc}
     */
    public function update(UpdateContext $context)
    {
        $snippetNamespace = $this->container->get('snippets')->getNamespace('backend/heidel_payment/pluginmanager');

        $this->applyUpdates($context->getCurrentVersion(), $context->getUpdateVersion());

        $context->scheduleMessage($snippetNamespace->get('update/message'));

        parent::update($context);
    }

    public function activate(ActivateContext $context)
    {
        $snippetNamespace = $this->container->get('snippets')->getNamespace('backend/heidel_payment/pluginmanager');

        $context->scheduleClearCache(InstallContext::CACHE_LIST_ALL);
        $context->scheduleMessage($snippetNamespace->get('activate/message'));
    }

    public function deactivate(DeactivateContext $context)
    {
        $context->scheduleClearCache(InstallContext::CACHE_LIST_ALL);
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

                (new PaymentMethods($modelManager))->install();
                (new Database($this->container->get('dbal_connection')))->install();
                (new Attributes($this->container->get('shopware_attribute.crud_service'), $modelManager))->install();

                return true;
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
