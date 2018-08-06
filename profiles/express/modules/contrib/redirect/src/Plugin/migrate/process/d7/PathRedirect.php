<?php

/**
 * @file
 * Contains \Drupal\redirect\Plugin\migrate\process\d7\PathRedirect.
 */

namespace Drupal\redirect\Plugin\migrate\process\d7;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * @MigrateProcessPlugin(
 *   id = "d7_path_redirect"
 * )
 */
class PathRedirect extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   *
   * Transform the field as required for an iFrame field.
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    // Check if the url begins with http.
    if (preg_match('#^http#', $value[0])) {
      // Use it as is.
      $uri = $value[0];
    }
    else {
      // Make the link internal.
      $uri = 'internal:/' . $value[0];
    }

    // Check if there are options.
    if (!empty($value[1])) {
      // Check if there is a query.
      $options = unserialize($value[1]);
      if (!empty($options['query'])) {
        // Add it to the end of the url.
        $uri .= '?' . http_build_query($options['query']);
      }
    }

    return $uri;
  }

}