<?php

namespace Drupal\redirect\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * @MigrateProcessPlugin(
 *   id = "d6_path_redirect"
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

    // Check if there is a query.
    if (!empty($value[1])) {
      // Add it to the end of the url.
      $uri .= '?' . $value[1];
    }

    return $uri;
  }

}
