<?php

declare(strict_types=1);

namespace UnzerPayment\Components\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class WebhookCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('unzer_payment.factory.webhook')) {
            return;
        }

        $definition     = $container->getDefinition('unzer_payment.factory.webhook');
        $taggedServices = $container->findTaggedServiceIds('unzer_payment.webhook_handler');

        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                $definition->addMethodCall('addWebhookHandler', [
                    new Reference($id),
                    $attributes['hook'],
                ]);
            }
        }
    }
}
