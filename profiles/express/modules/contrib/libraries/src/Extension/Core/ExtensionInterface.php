<?php

namespace Drupal\libraries\Extension\Core;

/**
 * Provides an interface for \Drupal\Core\Extension\Extension and its children.
 *
 * @todo Move this upstream.
 * @todo Consider providing an interface (with trait) for \SplFileInfo and
 *   extending it.
 */
interface ExtensionInterface extends \Serializable {

  /**
   * Returns the type of the extension.
   *
   * @return string
   */
  public function getType();

  /**
   * Returns the internal name of the extension.
   *
   * @return string
   */
  public function getName();

  /**
   * Returns the relative path of the extension.
   *
   * @return string
   */
  public function getPath();

  /**
   * Returns the relative path and filename of the extension's info file.
   *
   * @return string
   */
  public function getPathname();

  /**
   * Returns the filename of the extension's info file.
   *
   * @return string
   */
  public function getFilename();

  /**
   * Returns the relative path of the main extension file, if any.
   *
   * @return string|null
   */
  public function getExtensionPathname();

  /**
   * Returns the name of the main extension file, if any.
   *
   * @return string|null
   */
  public function getExtensionFilename();

  /**
   * Loads the main extension file, if any.
   *
   * @return bool
   *   TRUE if this extension has a main extension file, FALSE otherwise.
   */
  public function load();

}
