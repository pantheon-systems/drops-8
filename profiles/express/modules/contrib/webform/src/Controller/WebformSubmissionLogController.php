<?php

namespace Drupal\webform\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformRequestInterface;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for webform submission log routes.
 *
 * Copied from: \Drupal\dblog\Controller\DbLogController.
 */
class WebformSubmissionLogController extends ControllerBase {

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * The webform storage.
   *
   * @var \Drupal\webform\WebformStorageInterface
   */
  protected $webformStorage;

  /**
   * The webform submission storage.
   *
   * @var \Drupal\webform\WebformSubmissionStorageInterface
   */
  protected $webformSubmissionStorage;

  /**
   * Webform request handler.
   *
   * @var \Drupal\webform\WebformRequestInterface
   */
  protected $requestHandler;

  /**
   * Constructs a WebformSubmissionLogController object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   A database connection.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\webform\WebformRequestInterface $request_handler
   *   The webform request handler.
   */
  public function __construct(Connection $database, DateFormatterInterface $date_formatter, WebformRequestInterface $request_handler) {
    $this->database = $database;
    $this->dateFormatter = $date_formatter;
    $this->webformStorage = $this->entityTypeManager()->getStorage('webform');
    $this->webformSubmissionStorage = $this->entityTypeManager()->getStorage('webform_submission');
    $this->userStorage = $this->entityTypeManager()->getStorage('user');
    $this->requestHandler = $request_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('date.formatter'),
      $container->get('webform.request')
    );
  }

  /**
   * Displays a listing of webform submission log messages.
   *
   * @param \Drupal\webform\WebformInterface|null $webform
   *   A webform.
   * @param \Drupal\webform\WebformInterface|null $webform_submission
   *   A webform submission.
   * @param \Drupal\Core\Entity\EntityInterface|null $source_entity
   *   A source entity.
   *
   * @return array
   *   A render array as expected by drupal_render().
   */
  public function overview(WebformInterface $webform = NULL, WebformSubmissionInterface $webform_submission = NULL, EntityInterface $source_entity = NULL) {
    if (empty($webform) && !empty($webform_submission)) {
      $webform = $webform_submission->getWebform();
    }
    if (empty($source_entity) && !empty($webform_submission)) {
      $source_entity = $webform_submission->getSourceEntity();
    }

    // Header.
    $header = [];
    $header['lid'] = ['data' => $this->t('#'), 'field' => 'l.lid', 'sort' => 'desc'];
    if (empty($webform)) {
      $header['webform_id'] = ['data' => $this->t('Webform'), 'field' => 'l.webform_id', 'class' => [RESPONSIVE_PRIORITY_MEDIUM]];
    }
    if (empty($source_entity) && empty($webform_submission)) {
      $header['entity'] = ['data' => $this->t('Submitted to'), 'class' => [RESPONSIVE_PRIORITY_LOW]];
    }
    if (empty($webform_submission)) {
      $header['sid'] = ['data' => $this->t('Submission'), 'field' => 'l.sid'];
    }
    $header['handler_id'] = ['data' => $this->t('Handler'), 'field' => 'l.handler_id'];
    $header['operation'] = ['data' => $this->t('Operation'), 'field' => 'l.operation', 'class' => [RESPONSIVE_PRIORITY_MEDIUM]];
    $header['message'] = ['data' => $this->t('Message'), 'field' => 'l.message', 'class' => [RESPONSIVE_PRIORITY_LOW]];
    $header['uid'] = ['data' => $this->t('User'), 'field' => 'ufd.name', 'class' => [RESPONSIVE_PRIORITY_LOW]];
    $header['timestamp'] = ['data' => $this->t('Date'), 'field' => 'l.timestamp', 'sort' => 'desc', 'class' => [RESPONSIVE_PRIORITY_LOW]];

    // Query.
    $query = $this->database->select('webform_submission_log', 'l')
      ->extend('\Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('\Drupal\Core\Database\Query\TableSortExtender');
    $query->leftJoin('users_field_data', 'ufd', 'l.uid = ufd.uid');
    $query->leftJoin('webform_submission', 'ws', 'l.sid = ws.sid');
    $query->fields('l', [
      'lid',
      'uid',
      'webform_id',
      'sid',
      'handler_id',
      'operation',
      'message',
      'timestamp',
    ]);
    $query->fields('ws', [
      'entity_type',
      'entity_id',
    ]);
    if ($webform) {
      $query->condition('l.webform_id', $webform->id());
    }
    if ($webform_submission) {
      $query->condition('l.sid', $webform_submission->id());
    }
    if ($source_entity) {
      $query->condition('ws.entity_type', $source_entity->getEntityTypeId());
      $query->condition('ws.entity_id', $source_entity->id());
    }
    $result = $query
      ->limit(50)
      ->orderByHeader($header)
      ->execute();

    // Rows.
    $rows = [];
    foreach ($result as $log) {
      $row = [];
      $row['lid'] = $log->lid;
      if (empty($webform)) {
        $log_webform = $this->webformStorage->load($log->webform_id);
        $row['webform_id'] = $log_webform->toLink($log_webform->label(), 'results-log');
      }
      if (empty($source_entity) && empty($webform_submission)) {
        $entity = NULL;
        if ($log->entity_type && $log->entity_id) {
          $entity_type = $log->entity_type;
          $entity_id = $log->entity_id;
          if ($entity = $this->entityTypeManager()->getStorage($entity_type)->load($entity_id)) {
            $row['entity'] = ($entity->hasLinkTemplate('canonical')) ? $entity->toLink() : "$entity_type:$entity_id";
          }
          else {
            $row['entity'] = "$entity_type:$entity_id";
          }
        }
        else {
          $row['entity'] = '';
        }
      }
      if (empty($webform_submission)) {
        if ($log->sid) {
          $log_webform_submission = $this->webformSubmissionStorage->load($log->sid);
          $row['sid'] = [
            'data' => [
              '#type' => 'link',
              '#title' => $log->sid,
              '#url' => $this->requestHandler->getUrl($log_webform_submission, $source_entity, 'webform_submission.log'),
            ],
          ];
        }
        else {
          $row['sid'] = '';
        }
      }
      $row['handler_id'] = $log->handler_id;
      $row['operation'] = $log->operation;
      $row['message'] = $log->message;
      $row['uid'] = [
        'data' => [
          '#theme' => 'username',
          '#account' => $this->userStorage->load($log->uid),
        ],
      ];
      $row['timestamp'] = $this->dateFormatter->format($log->timestamp, 'short');

      $rows[] = $row;
    }

    $build['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No log messages available.'),
    ];
    $build['pager'] = ['#type' => 'pager'];
    return $build;
  }

}
