<?php

namespace Drupal\metatag\Plugin\migrate\source\d7;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 7 Metatag field.
 *
 * @MigrateSource(
 *   id = "d7_metatag_field",
 *   source_module = "metatag"
 * )
 */
class MetatagField extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('metatag', 'm')
      ->fields('m', ['entity_type'])
      ->groupBy('entity_type');
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'entity_type' => $this->t('Entity type'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['entity_type']['type'] = 'string';
    return $ids;
  }

}
