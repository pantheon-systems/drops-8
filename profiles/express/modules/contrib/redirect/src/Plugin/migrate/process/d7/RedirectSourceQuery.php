<?php

/**
 * @file
 * Contains \Drupal\redirect\Plugin\migrate\process\d7\RedirectSourceQuery.
 */

namespace Drupal\redirect\Plugin\migrate\process\d7;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * @MigrateProcessPlugin(
 *   id = "d7_redirect_source_query"
 * )
 */
class RedirectSourceQuery extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   *
   * Transform the field as required for an iFrame field.
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    // Check if there are options.
    if (!empty($value)) {
      // Check if there is a query.
      $options = unserialize($value);
      if (!empty($options['query'])) {
        // Add it to the end of the url.
        return serialize($options['query']);
      }
    }

    return NULL;
  }

}
