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
        if (!$context->keepUserData()) {
            (new Database($this->container->get('dbal_connection')))->uninstall();
            (new Attributes($this->container->get('shopware_attribute.crud_service'), $this->container->get('models')))->uninstall();
        }

        (new PaymentMethods($this->container->get('models')))->uninstall();

        $context->scheduleClearCache(InstallContext::CACHE_LIST_ALL);
        $context->scheduleMessage($snippet->get('uninstall/message'));
    }

    /**
     * {@inheritdoc}
     */
    public function update(UpdateContext $context)
    {
        $this->applyUpdates($context->getCurrentVersion(), $context->getUpdateVersion());

        $context->scheduleMessage($snippet->get('update/message'));

        parent::update($context);
    }

    public function activate(ActivateContext $context)
    {
        $snippet = $this->container->get('snippets')->getNamespace('backend/heidel_payment/pluginmanager');

        $context->scheduleClearCache(InstallContext::CACHE_LIST_ALL);
        $context->scheduleMessage($snippet->get('activate/message'));
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

    /**
     * @param null|string $oldVersion
     * @param null|string $newVersion
     */
    private function applyUpdates($oldVersion = null, $newVersion = null): bool
    {
        $versionClosures = [
            '1.0.0' => function () {
                (new PaymentMethods($this->container->get('models')))->install();
                (new Database($this->container->get('dbal_connection')))->install();
                (new Attributes($this->container->get('shopware_attribute.crud_service'), $this->container->get('models')))->install();

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
