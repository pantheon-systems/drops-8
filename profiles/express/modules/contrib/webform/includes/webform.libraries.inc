<?php

/**
 * @file
 * Webform libraries.
 */

/**
 * Implements hook_library_info_alter().
 */
function webform_library_info_alter(&$libraries, $extension) {
  // Only alter webform libraries.
  if ($extension != 'webform') {
    return;
  }

  /** @var \Drupal\webform\WebformLibrariesManagerInterface $libraries_manager */
  $libraries_manager = \Drupal::service('webform.libraries_manager');

  // Add off-canvas system tray to dialog dependencies.
  if (isset($libraries['webform.admin.dialog']) && (floatval(\Drupal::VERSION) >= 8.3) && \Drupal::moduleHandler()->moduleExists('outside_in')) {
    $libraries['webform.admin.dialog']['dependencies'][] = 'outside_in/drupal.off_canvas';
    $libraries['webform.admin.dialog']['dependencies'][] = 'outside_in/drupal.outside_in';
  }

  // Map /library/* paths to CDN.
  // @see webform.libraries.yml.
  foreach ($libraries as $library_name => &$library) {
    // Remove excluded libraries.
    if ($libraries_manager->isExcluded($library_name)) {
      unset($libraries[$library_name]);
      continue;
    }

    if (!empty($library['dependencies'])) {
      // Remove excluded libraries from dependencies.
      foreach ($library['dependencies'] as $dependency_index => $dependency_name) {
        if ($libraries_manager->isExcluded($dependency_name)) {
          $library['dependencies'][$dependency_index] = NULL;
          $library['dependencies'] = array_filter($library['dependencies']);
        }
      }
    }

    // Check CDN setting exists.
    if (!isset($library['cdn'])) {
      continue;
    }

    // Check if the CDN's source /library/* path exists.
    reset($library['cdn']);
    if (file_exists(DRUPAL_ROOT . key($library['cdn']))) {
      continue;
    }

    _webform_library_info_alter_recursive($library, $library['cdn']);
  }
}

/**
 * Recursive through a webform library.
 *
 * @param array $library
 *   A webform library defined in webform.libraries.yml.
 * @param array $cdn
 *   A associative array of library paths mapped to CDN URL.
 */
function _webform_library_info_alter_recursive(array &$library, array $cdn) {
  foreach ($library as $key => &$value) {
    // CSS and JS files and listed in associative arrays keyed via string.
    if (!is_string($key) || !is_array($value)) {
      continue;
    }

    // Ignore the CDN's associative array.
    if ($key == 'cdn') {
      continue;
    }

    // Replace the CDN sources (i.e. /library/*) with the CDN URL destination
    // (https://cdnjs.cloudflare.com/ajax/libs/*).
    foreach ($cdn as $source => $destination) {
      if (strpos($key, $source) === 0) {
        $uri = str_replace($source, $destination, $key);
        $library[$uri] = $value;
        $library[$uri]['type'] = 'external';
        unset($library[$key]);
        break;
      }
    }

    // Recurse downward to find nested libraries.
    _webform_library_info_alter_recursive($value, $cdn);
  }
}
