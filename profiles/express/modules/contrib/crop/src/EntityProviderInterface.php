<?php

namespace Drupal\crop;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines the interface for entity provider integration plugin.
 */
interface EntityProviderInterface extends PluginInspectionInterface {

  /**
   * Returns the selection display label.
   *
   * @return string
   *   The selection display label.
   */
  public function label();

  /**
   * Gets URI of the image file.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity being cropping.
   *
   * @return string|false
   *   URI as string or FALSE
   */
  public function uri(EntityInterface $entity);

}
