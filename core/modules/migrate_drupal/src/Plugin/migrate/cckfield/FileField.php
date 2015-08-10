<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\Plugin\migrate\cckfield\FileField.
 */

namespace Drupal\migrate_drupal\Plugin\migrate\cckfield;

use Drupal\migrate\Entity\MigrationInterface;

/**
 * @PluginID("filefield")
 */
class FileField extends CckFieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFieldWidgetMap() {
    return [
      'filefield_widget' => 'file_generic',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldFormatterMap() {
    return [
      'default' => 'file_default',
      'url_plain' => 'file_url_plain',
      'path_plain' => 'file_url_plain',
      'image_plain' => 'image',
      'image_nodelink' => 'image',
      'image_imagelink' => 'image',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function processCckFieldValues(MigrationInterface $migration, $field_name, $data) {
    $process = [
      'plugin' => 'd6_cck_file',
      'source' => [
        $field_name,
        $field_name . '_list',
        $field_name . '_data',
      ],
    ];
    $migration->mergeProcessOfProperty($field_name, $process);
  }

}
