<?php

declare(strict_types=1);

namespace HeidelPayment\Components\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class PaymentValidatorCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('heidel_payment.validator.factory')) {
            return;
        }

        $definition     = $container->getDefinition('heidel_payment.validator.factory');
        $taggedServices = $container->findTaggedServiceIds('heidelpay.payment.validator');

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
