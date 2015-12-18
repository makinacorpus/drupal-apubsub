<?php

namespace MakinaCorpus\Drupal\APubSub\Notification\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class RegisterChanTypeCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('apb.notification.chan_type_registry')) {
            return;
        }

        $definition = $container->getDefinition('apb.notification.chan_type_registry');

        $taggedServices = $container->findTaggedServiceIds('apb.notification.chan_type');

        foreach ($taggedServices as $id => $attributes) {
            $definition->addMethodCall(
                'registerInstance',
                [new Reference($id)]
            );
        }
    }
}
