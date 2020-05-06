<?php

namespace Drupal\webform\Plugin;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Interface of webform source entity plugin manager.
 */
interface WebformSourceEntityManagerInterface extends PluginManagerInterface {

  /**
   * Detect and return a source entity from current context.
   *
   * @param string|string[] $ignored_types
   *   Entity types that may not be used as a source entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   Source entity or NULL when no source entity is found.
   */
  public function getSourceEntity($ignored_types = []);

}
