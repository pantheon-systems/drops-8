<?php

namespace Drupal\action\Plugin\migrate\source;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;
use Drupal\migrate\Row;

/**
 * Drupal 6/7 action source from database.
 *
 * For available configuration keys, refer to the parent classes.
 *
 * @see \Drupal\migrate\Plugin\migrate\source\SqlBase
 * @see \Drupal\migrate\Plugin\migrate\source\SourcePluginBase
 *
 * @MigrateSource(
 *   id = "action",
 *   source_module = "system"
 * )
 */
class Action extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('actions', 'a')
      ->fields('a');
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'aid' => $this->t('Action ID'),
      'type' => $this->t('Module'),
      'callback' => $this->t('Callback function'),
      'parameters' => $this->t('Action configuration'),
    ];
    if ($this->getModuleSchemaVersion('system') >= 7000) {
      $fields['label'] = $this->t('Label of the action');
    }
    else {
      $fields['description'] = $this->t('Action description');
    }
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['aid']['type'] = 'string';
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $aid = $row->getSourceProperty('aid');
    if (is_numeric($aid)) {
      if ($this->getModuleSchemaVersion('system') >= 7000) {
        $label = $row->getSourceProperty('label');
      }
      else {
        $label = $row->getSourceProperty('description');
      }
      $row->setSourceProperty('aid', $label);
    }
    return parent::prepareRow($row);
  }

}
