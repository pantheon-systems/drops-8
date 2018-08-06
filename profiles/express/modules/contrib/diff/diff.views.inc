<?php

/**
 * @file
 * Provide views data for diff.module.
 */

/**
 * Implements hook_views_data().
 */
function diff_views_data() {
  $data = [];

  /** @var \Drupal\Core\Entity\EntityTypeInterface $entity_type */
  foreach (\Drupal::entityTypeManager()->getDefinitions() as $entity_type) {
    // Add the diff_from and diff_to fields to every revisionable entity type.
    if ($entity_type->isRevisionable()) {
      $revision_base_table = $entity_type->getRevisionDataTable() ?: $entity_type->getRevisionTable();

      $data[$revision_base_table]['diff_from'] = [
        'title' => t('Diff from'),
        'help' => 'Diff "from" radio button to compare revisions. Also adds the "Compare" button.',
        'field' => [
          'id' => 'diff__from',
        ],
      ];
      $data[$revision_base_table]['diff_to'] = [
        'title' => t('Diff to'),
        'help' => 'Diff "to" radio button to compare revisions.',
        'field' => [
          'id' => 'diff__to',
        ],
      ];
    }
  }

  return $data;
}
