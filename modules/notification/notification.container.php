<?php

namespace Drupal\Module\notification;

use Drupal\Core\DependencyInjection\ServiceProviderInterface;

use MakinaCorpus\APubSub\Notification\DependencyInjection\Compiler\RegisterFormattersPass;

use Symfony\Component\DependencyInjection\ContainerBuilder;

class ServiceProvider implements ServiceProviderInterface
{
   /**
    * {@inheritdoc}
    */
   public function register(ContainerBuilder $container)
   {
       $container->addCompilerPass(new RegisterFormattersPass());
   }
}
