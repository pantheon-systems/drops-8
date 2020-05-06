<?php

namespace Drupal\webform\Plugin;

/**
 * An interface implemented by plugin managers with excluded plugin definitions.
 */
interface WebformPluginManagerExcludedInterface {

  /**
   * Remove excluded plugin definitions.
   *
   * @param array $definitions
   *   The plugin definitions to filter.
   *
   * @return array
   *   An array of plugin definitions with excluded plugins removed.
   */
  public function removeExcludeDefinitions(array $definitions);

}
