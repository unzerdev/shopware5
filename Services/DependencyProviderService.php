<?php

namespace HeidelPayment\Services;

use Shopware\Components\DependencyInjection\Container;
use Shopware\Components\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DependencyProviderService implements DependencyProviderServiceInterface, ContainerAwareInterface
{
    /** @var ContainerInterface */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->setContainer($container);
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(Container $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getModule(string $name)
    {
        if (!$this->container->has('modules')) {
            return null;
        }

        return $this->container->get('modules')->getModule($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getSession()
    {
        if (!$this->container->initialized('session')) {
            return null;
        }

        return $this->container->get('session');
    }

    public function get(string $name)
    {
        if ($this->container->has($name)) {
            return $this->container->get($name);
        }

        return null;
    }
}
