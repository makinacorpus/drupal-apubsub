<?php

namespace MakinaCorpus\Drupal\APubSub\Notification\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

// Later use lazy loading, see symfony event-dispatcher for a working example
class RegisterFormatterCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('apb.notification.formatter_registry')) {
            return;
        }

        $definition = $container->getDefinition('apb.notification.formatter_registry');

        $taggedServices = $container->findTaggedServiceIds('apb.notification.formatter');

        foreach ($taggedServices as $id => $attributes) {
            $definition->addMethodCall(
                'registerInstance',
                [new Reference($id)]
            );
        }
    }
}
