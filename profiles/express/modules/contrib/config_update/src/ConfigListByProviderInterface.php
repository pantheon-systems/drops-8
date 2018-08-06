<?php

namespace Drupal\config_update;

/**
 * Defines an extended interface for listing configuration.
 */
interface ConfigListByProviderInterface extends ConfigListInterface {

  /**
   * Returns the provider of a given config object.
   *
   * @param string $name
   *   Name of the config object.
   *
   * @return string[]
   *   Array containing the type of extension providing this config object as
   *   its first element (module, theme, profile), and the name of the provider
   *   as its second element. NULL if unknown.
   */
  public function getConfigProvider($name);

  /**
   * Lists the providers of a given type that actually have configuration.
   *
   * @param string $type
   *   Type of extension (module, theme, profile).
   * @param string $name
   *   Machine name of extension.
   *
   * @return bool
   *   TRUE if the extension has either install or optional config, and FALSE
   *   if it does not.
   */
  public function providerHasConfig($type, $name);

}
