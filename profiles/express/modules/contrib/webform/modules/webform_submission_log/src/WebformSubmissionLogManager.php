<?php

namespace Drupal\webform_submission_log;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;

/**
 * Webform submission log manager.
 */
class WebformSubmissionLogManager implements WebformSubmissionLogManagerInterface {

  use DependencySerializationTrait;

  /**
   * Name of the table where log entries are stored.
   */
  const TABLE = 'webform_submission_log';

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * WebformSubmissionLogManager constructor.
   *
   * @param \Drupal\Core\Database\Connection $datababse
   *   The database service.
   */
  public function __construct(Connection $datababse) {
    $this->database = $datababse;
  }

  /**
   * {@inheritdoc}
   */
  public function insert(array $fields) {
    $fields += [
      'webform_id' => '',
      'sid' => '',
      'handler_id' => '',
      'operation' => '',
      'uid' => '',
      'message' => '',
      'variables' => serialize([]),
      'data' => serialize([]),
      'timestamp' => '',
    ];
    $this->database->insert(self::TABLE)
      ->fields($fields)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getQuery(EntityInterface $webform_entity = NULL, EntityInterface $source_entity = NULL, AccountInterface $account = NULL, array $options = []) {
    // Default options.
    $options += [
      'header' => NULL,
      'limit' => NULL,
    ];

    $query = $this->database->select(static::TABLE, 'log');

    // Log fields.
    $query->fields('log', [
      'lid',
      'uid',
      'webform_id',
      'sid',
      'handler_id',
      'operation',
      'message',
      'variables',
      'timestamp',
      'data',
    ]);

    // User fields.
    $query->leftJoin('users_field_data', 'user', 'log.uid = user.uid');

    // Submission fields.
    $query->leftJoin('webform_submission', 'submission', 'log.sid = submission.sid');
    $query->fields('submission', [
      'entity_type',
      'entity_id',
    ]);

    // Webform condition.
    if ($webform_entity instanceof WebformInterface) {
      $query->condition('log.webform_id', $webform_entity->id());
    }
    // Webform submission conditions.
    elseif ($webform_entity instanceof WebformSubmissionInterface) {
      $query->condition('log.webform_id', $webform_entity->getWebform()->id());
      $query->condition('log.sid', $webform_entity->id());
    }

    // Source entity conditions.
    if ($source_entity) {
      $query->condition('submission.entity_type', $source_entity->getEntityTypeId());
      $query->condition('submission.entity_id', $source_entity->id());
    }

    // User account condition.
    if ($account) {
      $query->condition('log.uid', $account->id());
    }

    // Set header sorting.
    if ($options['header']) {
      $query = $query->extend('\Drupal\Core\Database\Query\TableSortExtender')
        ->orderByHeader($options['header']);
    }

    // Set limit pager.
    if ($options['limit']) {
      $query = $query->extend('\Drupal\Core\Database\Query\PagerSelectExtender')
        ->limit($options['limit']);
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function loadByEntities(EntityInterface $webform_entity = NULL, EntityInterface $source_entity = NULL, AccountInterface $account = NULL, array $options = []) {
    $result = $this->getQuery($webform_entity, $source_entity, $account, $options)
      ->execute();
    $records = [];
    while ($record = $result->fetchObject()) {
      $record->variables = unserialize($record->variables);
      $record->data = unserialize($record->data);
      $records[] = $record;
    }
    return $records;
  }

}
