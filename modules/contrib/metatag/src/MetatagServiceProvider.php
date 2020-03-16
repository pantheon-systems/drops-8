<?php

namespace Drupal\metatag;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\metatag\Normalizer\MetatagHalNormalizer;
use Drupal\metatag\Normalizer\MetatagNormalizer;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Service Provider for Metatag.
 */
class MetatagServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $modules = $container->getParameter('container.modules');
    if (isset($modules['serialization'])) {
      // Serialization module is enabled, add our metatag normalizers.
      // Priority of the metatag normalizer must be higher than other
      // general-purpose typed data and field item normalizers.
      $metatag = new Definition(MetatagNormalizer::class);
      $metatag->addTag('normalizer', ['priority' => 30]);
      $container->setDefinition('metatag.normalizer.metatag', $metatag);

      $metatag_hal = new Definition(MetatagHalNormalizer::class);
      $metatag_hal->addTag('normalizer', ['priority' => 31]);
      $container->setDefinition('metatag.normalizer.metatag.hal', $metatag_hal);
    }
  }

}
