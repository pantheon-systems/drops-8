<?php

namespace Drupal\video_filter;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Core class for Video Fitler module.
 *
 * Contains helper methods.
 *
 * @package Drupal\video_filter
 */
class VideoFilterCore {

  use StringTranslationTrait;

  /**
   * Load Video Filter plugins.
   */
  public function loadPlugins($url = '') {
    $result = [];
    $manager = \Drupal::service('plugin.manager.video_filter');
    $plugins = [];
    foreach ($manager->getDefinitions() as $plugin_info) {
      $plugin = $manager->createInstance($plugin_info['id']);
      // Plugin options.
      $options = $plugin->options();
      // Check if URL is supported.
      $regexp = $plugin->getRegexp();
      $_regexp = !is_array($regexp) ? [$regexp] : $regexp;
      foreach ($_regexp as $regexp) {
        if (preg_match($regexp, $url, $matches) && !empty($options)) {
          $result['id'] = $plugin_info['id'];
          $result['options'] = $options;
          $result['plugin'] = $plugin;
        }
      }
      $plugins[$plugin_info['id']] = [
        'options' => $options,
        'regexp' => $_regexp,
      ];
    }
    $result['plugins'] = $plugins;
    return $result;
  }

}
