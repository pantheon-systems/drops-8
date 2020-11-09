<?php

namespace Drupal\metatag\Plugin\migrate\source\d6;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 6 Nodewords field.
 *
 * @MigrateSource(
 *   id = "d6_nodewords_field",
 *   source_module = "nodewords"
 * )
 */
class NodewordsField extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('nodewords', 'n')
      ->fields('n', ['type'])
      ->groupBy('type');
  }

  /**
   * {@inheritdoc}
   */
  public function initializeIterator() {
    $instances = [];
    foreach (parent::initializeIterator() as $instance) {
      switch ($instance['type']) {
        // define('NODEWORDS_TYPE_NODE', 5);
        case 5:
          $instance['entity_type'] = 'node';
          break;

        // define('NODEWORDS_TYPE_TERM', 6);
        case 6:
          $instance['entity_type'] = 'taxonomy_term';
          break;

        // define('NODEWORDS_TYPE_USER', 8);
        case 8:
          $instance['entity_type'] = 'user';
          break;

        default:
          continue 2;
      }

      $instances[$instance['entity_type']] = $instance;
    }

    return new \ArrayIterator($instances);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'type' => $this->t('Configuration type'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['type']['type'] = 'integer';
    return $ids;
  }

}
