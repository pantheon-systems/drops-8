<?php

namespace Drupal\libraries;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Modifies Libraries API services based on configuration.
 */
class LibrariesServiceProvider implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    if ($container->has('config.factory')) {
      // The configuration factory depends on the cache factory, but that
      // depends on the 'cache_default_bin_backends' parameter that has not yet
      // been set by \Drupal\Core\Cache\ListCacheBinsPass::process() at this
      // point.
      $parameter_name = 'cache_default_bin_backends';
      if (!$container->hasParameter($parameter_name)) {
        $container->setParameter($parameter_name, []);
      }

      /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
      $config_factory = $container->get('config.factory');

      $config = $config_factory->get('libraries.settings');
      if (!$config->isNew()) {
        // Set the local definition path.
        $container
          ->getDefinition('libraries.definition.discovery.local')
          ->replaceArgument(1, $config->get('definition.local.path'));

        // Set the remote definition URL. Note that this is set even if
        // the remote discovery is not enabled below in case the
        // 'libraries.definition.discovery.remote' service is used explicitly.
        $container
          ->getDefinition('libraries.definition.discovery.remote')
          ->replaceArgument(2, $config->get('definition.remote.url'));

        // Because it is less convenient to remove a method call than to add
        // one, the remote discovery is not registered in libraries.services.yml
        // and instead added here, even though the 'definition.remote.enable'
        // configuration value is TRUE by default.
        if ($config->get('definition.remote.enable')) {
          // Add the remote discovery to the list of chained discoveries.
          $container
            ->getDefinition('libraries.definition.discovery')
            ->addMethodCall('addDiscovery', [new Reference('libraries.definition.discovery.remote')]);
        }
      }

      // At this point the event dispatcher has not yet been populated with
      // event subscribers by RegisterEventSubscribersPass::process() but has
      // already bin injected in the configuration factory. Reset those services
      // accordingly.
      $container->set('event_dispatcher', NULL);
      $container->set('config.factory', NULL);
    }
  }

}
