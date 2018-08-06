<?php

namespace Drupal\webform;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\webform\Normalizer\WebformEntityReferenceItemNormalizer;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Service Provider for Webform.
 */
class WebformServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $modules = $container->getParameter('container.modules');
    // Hal module is enabled, add our new normalizer for webform items.
    // Core 8.3 and above use hal module https://www.drupal.org/node/2830467.
    $manager = isset($modules['hal']) ? 'hal.link_manager' : 'rest.link_manager';
    if ($container->has($manager)) {
      $service_definition = new Definition(WebformEntityReferenceItemNormalizer::class, [
        new Reference($manager),
        new Reference('serializer.entity_resolver'),
      ]);
      // The priority must be higher than that of
      // serializer.normalizer.entity_reference_item.hal in
      // hal.services.yml.
      $service_definition->addTag('normalizer', ['priority' => 20]);
      $container->setDefinition('serializer.normalizer.webform_entity_reference_item', $service_definition);
    }
  }

}
