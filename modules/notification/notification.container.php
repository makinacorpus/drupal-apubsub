<?php

namespace Drupal\Module\notification;

use MakinaCorpus\Drupal\APubSub\Notification\DependencyInjection\Compiler\RegisterChanTypeCompilerPass;
use MakinaCorpus\Drupal\APubSub\Notification\DependencyInjection\Compiler\RegisterFormatterCompilerPass;

use Drupal\Core\DependencyInjection\ServiceProviderInterface;

use Symfony\Component\DependencyInjection\ContainerBuilder;

class ServiceProvider implements ServiceProviderInterface
{
   /**
    * {@inheritdoc}
    */
   public function register(ContainerBuilder $container)
   {
       $container->addCompilerPass(new RegisterFormatterCompilerPass());
       $container->addCompilerPass(new RegisterChanTypeCompilerPass());
   }
}
