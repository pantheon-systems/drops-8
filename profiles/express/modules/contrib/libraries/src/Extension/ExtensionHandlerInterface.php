<?php

namespace Drupal\libraries\Extension;

/**
 * Provides an interface for extension handlers.
 */
interface ExtensionHandlerInterface {

  /**
   * Returns all extensions installed on the system.
   *
   * @return \Drupal\libraries\Extension\ExtensionInterface[]|\Generator
   *   An array of extension objects keyed by the name of the extension.
   */
  public function getExtensions();

}
