<?php

namespace HeidelPayment;

use HeidelPayment\Installers\PaymentMethods;
use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;
use Shopware\Components\Plugin\Context\UpdateContext;

//Load the heidelpay-php SDK
require __DIR__ . '/vendor/autoload.php';

class HeidelPayment extends Plugin
{
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
        parent::uninstall($context);
    }

    /**
     * {@inheritdoc}
     */
    public function update(UpdateContext $updateContext)
    {
        $this->applyUpdates($updateContext->getCurrentVersion(), $updateContext->getUpdateVersion());

        parent::update($updateContext);
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
     *
     * @return bool
     */
    private function applyUpdates($oldVersion = null, $newVersion = null)
    {
        $versionClosures = [
            '1.0.0' => function () {
                (new PaymentMethods($this->container->get('models')))->install();

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
