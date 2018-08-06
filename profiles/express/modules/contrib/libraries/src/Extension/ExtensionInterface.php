<?php

namespace Drupal\libraries\Extension;

use Drupal\libraries\Extension\Core\ExtensionInterface as CoreExtensionInterface;

/**
 * Provides an interface for extensions.
 *
 * @todo Consider the extends or alternatively consider slimming
 *   CoreExtensionInterface.
 */
interface ExtensionInterface extends CoreExtensionInterface {

  /**
   * Gets the library dependencies of the extension, if any.
   *
   * @return array
   *   The IDs of the libraries the extension depends on.
   */
  public function getLibraryDependencies();

}
