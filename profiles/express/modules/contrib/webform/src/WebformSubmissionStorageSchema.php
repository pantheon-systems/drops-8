<?php

namespace Drupal\webform;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;

/**
 * Defines the webform submission schema handler.
 */
class WebformSubmissionStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getEntitySchema(ContentEntityTypeInterface $entity_type, $reset = FALSE) {
    $schema = parent::getEntitySchema($entity_type, $reset);

    $schema['webform_submission']['indexes'] += [
      'webform_submission_field__token' => ['token'],
    ];

    $schema['webform_submission_data'] = [
      'description' => 'Stores all submitted data for webform submissions.',
      'fields' => [
        'webform_id' => [
          'description' => 'The webform id.',
          'type' => 'varchar',
          'length' => 32,
          'not null' => TRUE,
        ],
        'sid' => [
          'description' => 'The unique identifier for this submission.',
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
        ],
        'name' => [
          'description' => 'The name of the element.',
          'type' => 'varchar',
          'length' => 128,
          'not null' => TRUE,
        ],
        'property' => [
          'description' => "The property of the element's value.",
          'type' => 'varchar',
          'length' => 128,
          'not null' => TRUE,
          'default' => '',
        ],
        'delta' => [
          'description' => "The delta of the element's value.",
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ],
        'value' => [
          'description' => "The element's value.",
          'type' => 'text',
          'size' => 'medium',
          'not null' => TRUE,
        ],
      ],
      'primary key' => ['sid', 'name', 'property', 'delta'],
      'indexes' => [
        'webform_id' => ['webform_id'],
        'sid_webform_id' => ['sid', 'webform_id'],
      ],
    ];

    return $schema;
  }

}
