<?php

/**
 * @file
 * Provide views data for entity_browser.module.
 */

/**
 * Implements hook_views_data_alter().
 */
function entity_browser_views_data_alter(&$data) {
  foreach (\Drupal::entityTypeManager()->getDefinitions() as $entity_type_name => $entity_type) {
    if ($base_table = $entity_type->getBaseTable()) {
      $entity_keys = $entity_type->get('entity_keys');
      $data[$base_table]['entity_browser_select'] = [
        'title' => t('Entity browser bulk select form'),
        'help' => t('Add a form element that lets you use a view as a base to select entities in entity browser.'),
        'field' => [
          'id' => 'entity_browser_select',
          'real field' => $entity_keys['id'],
        ],
      ];
    }
  }
}
