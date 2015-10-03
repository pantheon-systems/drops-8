<?php

/**
 * @file
 * Contains \Drupal\node\Plugin\migrate\builder\d6\Node.
 */

namespace Drupal\node\Plugin\migrate\builder\d6;

use Drupal\migrate\Entity\Migration;
use Drupal\migrate_drupal\Plugin\migrate\builder\CckBuilder;

/**
 * @PluginID("d6_node")
 */
class Node extends CckBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildMigrations(array $template) {
    $migrations = [];

    // Read all CCK field instance definitions in the source database.
    $fields = array();
    foreach ($this->getSourcePlugin('d6_field_instance', $template['source']) as $field) {
      $info = $field->getSource();
      $fields[$info['type_name']][$info['field_name']] = $info;
    }

    foreach ($this->getSourcePlugin('d6_node_type', $template['source']) as $row) {
      $node_type = $row->getSourceProperty('type');
      $values = $template;
      $values['id'] = $template['id'] . '__' . $node_type;
      $label = $template['label'];
      $values['label'] = $this->t("@label (@type)", ['@label' => $label, '@type' => $node_type]);
      $values['source']['node_type'] = $node_type;
      $migration = Migration::create($values);

      if (isset($fields[$node_type])) {
        foreach ($fields[$node_type] as $field => $info) {
          if ($this->cckPluginManager->hasDefinition($info['type'])) {
            $this->getCckPlugin($info['type'])
              ->processCckFieldValues($migration, $field, $info);
          }
          else {
            $migration->setProcessOfProperty($field, $field);
          }
        }
      }

      $migrations[] = $migration;
    }

    return $migrations;
  }

}
