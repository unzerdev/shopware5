<?php

declare(strict_types=1);

namespace HeidelPayment\Components\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class PaymentStatusMapperCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('heidel_payment.status_mapper.factory')) {
            return;
        }

        $definition     = $container->getDefinition('heidel_payment.status_mapper.factory');
        $taggedServices = $container->findTaggedServiceIds('heidelpay.payment.status_mapper');

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('addStatusMapper', [
                new Reference($id),
            ]);
        }
    }
}
