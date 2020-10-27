<?php

declare(strict_types=1);

namespace UnzerPayment\Components\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class PaymentStatusMapperCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('unzer_payment.factory.status_mapper')) {
            return;
        }

        $definition     = $container->getDefinition('unzer_payment.factory.status_mapper');
        $taggedServices = $container->findTaggedServiceIds('unzer_payment.payment.status_mapper');

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('addStatusMapper', [
                new Reference($id),
            ]);
        }
    }
}
