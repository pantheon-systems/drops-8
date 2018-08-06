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
    $schema = parent::getEntitySchema($entity_type, $reset = FALSE);

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

    $schema['webform_submission_log'] = [
      'description' => 'Table that contains logs of all webform submission events.',
      'fields' => [
        'lid' => [
          'type' => 'serial',
          'not null' => TRUE,
          'description' => 'Primary Key: Unique log event ID.',
        ],
        'webform_id' => [
          'description' => 'The webform id.',
          'type' => 'varchar',
          'length' => 32,
          'not null' => TRUE,
        ],
        'sid' => [
          'description' => 'The webform submission id.',
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => FALSE,
        ],
        'handler_id' => [
          'description' => 'The webform handler id.',
          'type' => 'varchar',
          'length' => 64,
          'not null' => FALSE,
        ],
        'uid' => [
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
          'description' => 'The {users}.uid of the user who triggered the event.',
        ],
        'operation' => [
          'type' => 'varchar_ascii',
          'length' => 64,
          'not null' => TRUE,
          'default' => '',
          'description' => 'Type of operation, for example "save", "sent", or "update."',
        ],
        'message' => [
          'type' => 'text',
          'not null' => TRUE,
          'size' => 'big',
          'description' => 'Text of log message.',
        ],
        'data' => [
          'type' => 'blob',
          'not null' => TRUE,
          'size' => 'big',
          'description' => 'Serialized array of data.',
        ],
        'timestamp' => [
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'description' => 'Unix timestamp of when event occurred.',
        ],
      ],
      'primary key' => ['lid'],
      'indexes' => [
        'webform_id' => ['webform_id'],
        'sid' => ['sid'],
        'uid' => ['uid'],
        'handler_id' => ['handler_id'],
        'handler_id_operation' => ['handler_id', 'operation'],
      ],
    ];

    return $schema;
  }

}
