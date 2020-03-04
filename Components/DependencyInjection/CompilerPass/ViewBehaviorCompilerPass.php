<?php

declare(strict_types=1);

namespace HeidelPayment\Components\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ViewBehaviorCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('heidel_payment.factory.view_behavior')) {
            return;
        }

        $definition     = $container->getDefinition('heidel_payment.factory.view_behavior');
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
