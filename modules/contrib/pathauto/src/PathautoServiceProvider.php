<?php

namespace Drupal\pathauto;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Overrides the pathauto.alias_storage_helper service if needed.
 */
class PathautoServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    if (version_compare(\Drupal::VERSION, '8.8', '<')) {
      $definition = $container->getDefinition('pathauto.alias_storage_helper');
      $definition->setClass(LegacyAliasStorageHelper::class);
      $definition->setArgument(1, new Reference('path.alias_storage'));
    }
  }

}
