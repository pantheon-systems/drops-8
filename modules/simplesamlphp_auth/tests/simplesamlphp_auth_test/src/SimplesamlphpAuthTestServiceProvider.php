<?php

/**
 * @file
 * Contains \Drupal\simplesamlphp_auth_test\SimplesamlphpAuthTestServiceProvider.
 */

namespace Drupal\simplesamlphp_auth_test;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Overrides the simplesamlphp_auth.manager service.
 *
 * Points to a test manager class instead.
 */
class SimplesamlphpAuthTestServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('simplesamlphp_auth.manager');
    $definition->setClass('Drupal\simplesamlphp_auth_test\SimplesamlphpAuthTestManager');
  }

}
