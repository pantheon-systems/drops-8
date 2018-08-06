<?php

/**
 * @file
 * Contains \Drupal\field_group_migrate\Plugin\migrate\source\d7\FieldGroup.
 */

namespace Drupal\field_group_migrate\Plugin\migrate\source\d7;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Drupal 7 field_group source.
 *
 * @MigrateSource(
 *   id = "d7_field_group"
 * )
 */
class FieldGroup extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('field_group', 'f')->fields('f');
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $data = unserialize($row->getSourceProperty('data'));
    $format_settings = $data['format_settings'] + $data['format_settings']['instance_settings'];
    unset($format_settings['instance_settings']);
    $settings = array(
      'children' => $data['children'],
      'parent_name' => $row->getSourceProperty('parent_name'),
      'weight' => $data['weight'],
      'label' => $data['label'],
      'format_settings' => $format_settings,
      'format_type' => $data['format_type'],
    );
    switch ($data['format_type']) {
      case 'div':
        $settings['format_type'] = 'html_element';
        $settings['format_settings']['element'] = 'div';
        break;

      case 'tabs':
        $settings['format_type'] = 'tabs';
        $settings['format_settings']['direction'] = 'vertical';
        break;

      case 'htabs':
        $settings['format_type'] = 'tabs';
        $settings['format_settings']['direction'] = 'horizontal';
        break;

      case 'htab':
        $settings['format_type'] = 'tab';
        break;

      case 'multipage-group':
        // @todo Check if there is a better way to deal with this format type.
        $settings['format_type'] = 'tabs';
        break;

      case 'multipage':
      // @todo Check if there is a better way to deal with this format type.
        $settings['format_type'] = 'tab';
        break;

    }
    $row->setSourceProperty('settings', $settings);
    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['id']['type'] = 'integer';
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = array(
      'id' => $this->t('ID'),
      'identifier' => $this->t('Identifier'),
      'group_name' => $this->t('Group name'),
      'entity_type' => $this->t('Entity type'),
      'bundle' => $this->t('Bundle'),
      'mode' => $this->t('View mode'),
      'parent_name' => $this->t('Parent name'),
      'data' => $this->t('Data'),
    );
    return $fields;
  }

}
