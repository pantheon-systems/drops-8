<?php

namespace Drupal\webform\Utility;

/**
 * Helper class for reflection methods.
 */
class WebformReflectionHelper {

  /**
   * A list of Webform submodules.
   *
   * @var array
   */
  protected static $modules;

  /**
   * Get a list of Webform submodules.
   *
   * @return array
   *   A list of Webform submodules.
   */
  public static function getSubModules() {
    if (isset(self::$modules)) {
      return self::$modules;
    }

    self::$modules = [];
    $files = file_scan_directory(drupal_get_path('module', 'webform') . '/modules', '/.*\.info\.yml/');
    foreach ($files as $file) {
      $module_name = str_replace('.info.yml', '', $file->filename);
      self::$modules[$module_name] = $module_name;
    }
    return self::$modules;
  }

  /**
   * Get an object's class hierarchy.
   *
   * @param object $object
   *   An object.
   * @param string $base_class_name
   *   (optional) Base class name to use as the root of object's class
   *   hierarchy.
   *
   * @return array
   *   An array containing this elements class hierarchy.
   */
  public static function getParentClasses($object, $base_class_name = '') {
    $class = get_class($object);
    $parent_classes = [];
    while ($class_name = self::getClassName($class)) {
      $parent_classes[] = $class_name;
      $class = get_parent_class($class);
      if ($class_name == $base_class_name || !$class) {
        break;
      }
    }
    return array_reverse($parent_classes);
  }

  /**
   * Get a class's name without its namespace.
   *
   * @param string $class
   *   A class.
   *
   * @return string
   *   The class's name without its namespace.
   */
  protected static function getClassName($class) {
    $parts = preg_split('#\\\\#', $class);
    return end($parts);
  }

}
