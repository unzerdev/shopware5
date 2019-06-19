<?php

namespace HeidelPayment;

use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;
use Shopware\Components\Plugin\Context\UpdateContext;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class HeidelPayment extends Plugin
{
    /**
     * {@inheritDoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->setParameter('heidel_payment.plugin_dir', $this->getPath());

        parent::build($container);
    }

    /**
     * {@inheritDoc}
     */
    public function install(InstallContext $context)
    {
        $this->applyUpdates(null, $context->getCurrentVersion());

        parent::install($context);
    }

    /**
     * {@inheritDoc}
     */
    public function uninstall(UninstallContext $context)
    {
        parent::uninstall($context);
    }

    /**
     * {@inheritDoc}
     */
    public function update(UpdateContext $updateContext)
    {
        $this->applyUpdates($updateContext->getCurrentVersion(), $updateContext->getUpdateVersion());

        parent::update($updateContext);
    }

    /**
     * @param string|null $oldVersion
     * @param string|null $newVersion
     *
     * @return bool
     */
    private function applyUpdates($oldVersion = null, $newVersion = null)
    {
        $versionClosures = [

            '1.0.0' => function () {
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
