<?php

namespace Drupal\video_embed_field\Plugin\migrate\cckfield;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\cckfield\CckFieldPluginBase;

/**
 * Plugin to migrate from the Drupal 7 video_embed_field module.
 *
 * @MigrateCckField(
 *   id = "video_embed_field",
 *   core = {7}
 * )
 */
class VideoEmbedField extends CckFieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFieldType(Row $row) {
    return 'video_embed_field';
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldFormatterMap() {
    return [
      'default' => 'video_embed_field_video',
      'video_embed_field' => 'video_embed_field_video',
      'video_embed_field_thumbnail' => 'video_embed_field_thumbnail',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldWidgetMap() {
    return [
      'video_embed_field_video' => 'video_embed_field_textfield',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function processCckFieldValues(MigrationInterface $migration, $field_name, $data) {
    $process = [
      'plugin' => 'iterator',
      'source' => $field_name,
      'process' => [
        'value' => 'video_url',
      ],
    ];
    $migration->mergeProcessOfProperty($field_name, $process);
  }

}
