<?php

namespace HeidelPayment\Components\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ViewBehaviorCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('heidel_payment.view_behaviors.factory')) {
            return;
        }

        $definition     = $container->getDefinition('heidel_payment.view_behaviors.factory');
        $taggedServices = $container->findTaggedServiceIds('heidelpay.view_behavior');

        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                $definition->addMethodCall('addBehaviorHandler', [
                    new Reference($id),
                    $attributes['payment'],
                ]);
            }
        }
    }
}
