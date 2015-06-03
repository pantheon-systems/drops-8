<?php

/**
 * @file
 * Definition of Drupal\Core\Updater\Theme.
 */

namespace Drupal\Core\Updater;

use Drupal\Core\Url;

/**
 * Defines a class for updating themes using
 * Drupal\Core\FileTransfer\FileTransfer classes via authorize.php.
 */
class Theme extends Updater implements UpdaterInterface {

  /**
   * Returns the directory where a theme should be installed.
   *
   * If the theme is already installed, drupal_get_path() will return
   * a valid path and we should install it there (although we need to use an
   * absolute path, so we prepend DRUPAL_ROOT). If we're installing a new
   * theme, we always want it to go into /themes, since that's
   * where all the documentation recommends users install their themes, and
   * there's no way that can conflict on a multi-site installation, since
   * the Update manager won't let you install a new theme if it's already
   * found on your system, and if there was a copy in the top-level we'd see it.
   *
   * @return string
   *   A directory path.
   */
  public function getInstallDirectory() {
    if ($relative_path = drupal_get_path('theme', $this->name)) {
      $relative_path = dirname($relative_path);
    }
    else {
      $relative_path = 'themes';
    }
    return DRUPAL_ROOT . '/' . $relative_path;
  }

  /**
   * Implements Drupal\Core\Updater\UpdaterInterface::isInstalled().
   */
  public function isInstalled() {
    return (bool) drupal_get_path('theme', $this->name);
  }

  /**
   * Implements Drupal\Core\Updater\UpdaterInterface::canUpdateDirectory().
   */
  static function canUpdateDirectory($directory) {
    $info = static::getExtensionInfo($directory);

    return (isset($info['type']) && $info['type'] == 'theme');
  }

  /**
   * Determines whether this class can update the specified project.
   *
   * @param string $project_name
   *   The project to check.
   *
   * @return bool
   */
  public static function canUpdate($project_name) {
    return (bool) drupal_get_path('theme', $project_name);
  }

  /**
   * Overrides Drupal\Core\Updater\Updater::postInstall().
   */
  public function postInstall() {
    // Update the theme info.
    clearstatcache();
    \Drupal::service('theme_handler')->rebuildThemeData();
  }

  /**
   * Overrides Drupal\Core\Updater\Updater::postInstallTasks().
   */
  public function postInstallTasks() {
    return array(
      \Drupal::l(t('Install newly added themes'), new Url('system.themes_page')),
      \Drupal::l(t('Administration pages'), new Url('system.admin')),
    );
  }
}
