<?php

namespace Drupal\Tests\webform_submission_log\Traits;

/**
 * Trait for webform submission log tests.
 */
trait WebformSubmissionLogTrait {

  /**
   * Get the last submission id.
   *
   * @return int
   *   The last submission id.
   */
  protected function getLastSubmissionLog() {
    $query = \Drupal::database()->select('webform_submission_log', 'l');
    $query->leftJoin('webform_submission', 'ws', 'l.sid = ws.sid');
    $query->fields('l', [
      'lid',
      'uid',
      'sid',
      'handler_id',
      'operation',
      'message',
      'variables',
      'timestamp',
    ]);
    $query->fields('ws', [
      'webform_id',
      'entity_type',
      'entity_id',
    ]);
    $query->orderBy('l.lid', 'DESC');
    $query->range(0, 1);
    $submission_log = $query->execute()->fetch();
    if ($submission_log) {
      $submission_log->variables = unserialize($submission_log->variables);
    }
    return $submission_log;
  }

  /**
   * Get the entire submission log.
   *
   * @return int
   *   The last submission id.
   */
  protected function getSubmissionLog() {
    $query = \Drupal::database()->select('webform_submission_log', 'l');
    $query->leftJoin('webform_submission', 'ws', 'l.sid = ws.sid');
    $query->fields('l', [
      'lid',
      'uid',
      'sid',
      'handler_id',
      'operation',
      'message',
      'variables',
      'timestamp',
    ]);
    $query->fields('ws', [
      'webform_id',
      'entity_type',
      'entity_id',
    ]);
    $query->orderBy('l.lid', 'DESC');
    $submission_logs = $query->execute()->fetchAll();
    foreach ($submission_logs as &$submission_log) {
      $submission_log->variables = unserialize($submission_log->variables);
    }
    return $submission_logs;
  }

}
