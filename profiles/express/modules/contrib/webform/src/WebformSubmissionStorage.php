<?php

namespace Drupal\webform;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\AlterableInterface;
use Drupal\Core\Database\ReplicaKillSwitch;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Drupal\webform\Plugin\WebformElement\WebformManagedFileBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the webform submission storage.
 */
class WebformSubmissionStorage extends SqlContentEntityStorage implements WebformSubmissionStorageInterface {

  /**
   * Array used to element data schema.
   *
   * @var array
   */
  protected $elementDataSchema = [];

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The replica kill switch.
   *
   * @var \Drupal\Core\Database\ReplicaKillSwitch
   */
  protected $replicaKillSwitch;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Webform access rules manager service.
   *
   * @var \Drupal\webform\WebformAccessRulesManagerInterface
   */
  protected $accessRulesManager;

  /**
   * WebformSubmissionStorage constructor.
   *
   * @todo Webform 8.x-6.x: Move $time before $access_rules_manager.
   * @todo Webform 8.x-6.x: Move $memory_cache right after $language_manager.
   */
  public function __construct(EntityTypeInterface $entity_type, Connection $database, EntityManagerInterface $entity_manager, CacheBackendInterface $cache, LanguageManagerInterface $language_manager, AccountProxyInterface $current_user, WebformAccessRulesManagerInterface $access_rules_manager, TimeInterface $time = NULL, MemoryCacheInterface $memory_cache = NULL, FileSystemInterface $file_system = NULL, ReplicaKillSwitch $replica_kill_switch = NULL) {
    parent::__construct($entity_type, $database, $entity_manager, $cache, $language_manager, $memory_cache);

    $this->currentUser = $current_user;
    $this->accessRulesManager = $access_rules_manager;
    $this->time = $time ?: \Drupal::time();
    $this->fileSystem = $file_system ?: \Drupal::service('file_system');
    $this->replicaKillSwitch = $replica_kill_switch ?: \Drupal::service('database.replica_kill_switch');
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('database'),
      $container->get('entity.manager'),
      $container->get('cache.entity'),
      $container->get('language_manager'),
      $container->get('current_user'),
      $container->get('webform.access_rules_manager'),
      $container->get('datetime.time'),
      $container->get('entity.memory_cache'),
      $container->get('file_system'),
      $container->get('database.replica_kill_switch')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldDefinitions() {
    /** @var \Drupal\Core\Field\BaseFieldDefinition[] $definitions */
    $field_definitions = $this->entityManager->getBaseFieldDefinitions('webform_submission');

    // For now never let any see or export the serialize YAML data field.
    unset($field_definitions['data']);

    $definitions = [];
    foreach ($field_definitions as $field_name => $field_definition) {
      // Exclude the 'map' field type which is used by the metatag.module.
      if ($field_definition->getType() === 'map') {
        continue;
      }

      $definitions[$field_name] = [
        'title' => $field_definition->getLabel(),
        'name' => $field_name,
        'type' => $field_definition->getType(),
        'target_type' => $field_definition->getSetting('target_type'),
      ];
    }

    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function checkFieldDefinitionAccess(WebformInterface $webform, array $definitions) {
    if (!$webform->access('submission_update_any')) {
      unset($definitions['token']);
    }
    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  protected function doCreate(array $values) {
    /** @var \Drupal\webform\WebformSubmissionInterface $entity */
    $entity = parent::doCreate($values);
    if (!empty($values['data'])) {
      $data = (is_array($values['data'])) ? $values['data'] : Yaml::decode($values['data']);
      $entity->setData($data);
    }
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  protected function doLoadMultiple(array $ids = NULL) {
    /** @var \Drupal\webform\WebformSubmissionInterface[] $webform_submissions */
    $webform_submissions = parent::doLoadMultiple($ids);
    $this->loadData($webform_submissions);
    return $webform_submissions;
  }

  /**
   * {@inheritdoc}
   */
  public function loadByEntities(WebformInterface $webform = NULL, EntityInterface $source_entity = NULL, AccountInterface $account = NULL) {
    $properties = [];
    if ($webform) {
      $properties['webform_id'] = $webform->id();
    }
    if ($source_entity) {
      $properties['entity_type'] = $source_entity->getEntityTypeId();
      $properties['entity_id'] = $source_entity->id();
    }
    if ($account) {
      $properties['uid'] = $account->id();
    }
    return $this->loadByProperties($properties);
  }

  /**
   * {@inheritdoc}
   */
  public function loadFromToken($token, WebformInterface $webform, EntityInterface $source_entity = NULL, AccountInterface $account = NULL) {
    // Check token.
    if (!$token) {
      return NULL;
    }

    // Check that (secure) tokens are enabled for the webform.
    if (!$account && !$webform->getSetting('token_update')) {
      return NULL;
    }

    // Attempt to load the submission using the token.
    $properties = ['token' => $token];
    // Add optional source entity to properties.
    if ($source_entity) {
      $properties['entity_type'] = $source_entity->getEntityTypeId();
      $properties['entity_id'] = $source_entity->id();
    }
    // Add optional user account to properties.
    if ($account) {
      $properties['uid'] = $account->id();
    }

    $entities = $this->loadByProperties($properties);
    if (empty($entities)) {
      return NULL;
    }

    /** @var \Drupal\webform\WebformSubmissionInterface $entity */
    $entity = reset($entities);

    // Make sure the submission is associated with the webform.
    if ($entity->getWebform()->id() != $webform->id()) {
      return NULL;
    }

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildPropertyQuery(QueryInterface $entity_query, array $values) {
    // Add account query when ever filtered by uid.
    if (isset($values['uid'])) {
      $uids = (array) $values['uid'];
      $accounts = User::loadMultiple($uids);
      $or_condition_group = $entity_query->orConditionGroup();
      foreach ($accounts as $account) {
        $this->_addQueryConditions($or_condition_group, NULL, NULL, $account);
      }
      $entity_query->condition($or_condition_group);
      unset($values['uid']);
    }

    parent::buildPropertyQuery($entity_query, $values);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll(WebformInterface $webform = NULL, EntityInterface $source_entity = NULL, $limit = NULL, $max_sid = NULL) {
    $query = $this->getQuery();
    $query->accessCheck(FALSE);
    $this->addQueryConditions($query, $webform, $source_entity, NULL);
    if ($max_sid) {
      $query->condition('sid', $max_sid, '<=');
    }
    $query->sort('sid');
    if ($limit) {
      $query->range(0, $limit);
    }

    $entity_ids = $query->execute();
    $entities = $this->loadMultiple($entity_ids);
    $this->delete($entities);
    return count($entities);
  }

  /**
   * {@inheritdoc}
   */
  public function getTotal(WebformInterface $webform = NULL, EntityInterface $source_entity = NULL, AccountInterface $account = NULL, array $options = []) {
    // Default total to only look at completed submissions.
    $options += [
      'in_draft' => FALSE,
    ];

    $query = $this->getQuery();
    $query->accessCheck(FALSE);
    $this->addQueryConditions($query, $webform, $source_entity, $account, $options);

    // Issue: Query count method is not working for SQL Lite.
    // return $query->count()->execute();
    // Work-around: Manually count the number of entity ids.
    return count($query->execute());
  }

  /**
   * {@inheritdoc}
   */
  public function getMaxSubmissionId(WebformInterface $webform = NULL, EntityInterface $source_entity = NULL, AccountInterface $account = NULL) {
    $query = $this->getQuery();
    $query->accessCheck(FALSE);
    $this->addQueryConditions($query, $webform, $source_entity, $account);
    $query->sort('sid', 'DESC');
    $query->range(0, 1);

    $result = $query->execute();
    return reset($result);
  }

  /**
   * {@inheritdoc}
   */
  public function hasSubmissionValue(WebformInterface $webform, $element_key) {
    /** @var \Drupal\Core\Database\StatementInterface $result */
    $result = $this->database->select('webform_submission_data', 'sd')
      ->fields('sd', ['sid'])
      ->condition('sd.webform_id', $webform->id())
      ->condition('sd.name', $element_key)
      ->execute();
    return $result->fetchAssoc() ? TRUE : FALSE;
  }

  /****************************************************************************/
  // Source entity methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function getSourceEntitiesTotal(WebformInterface $webform) {
    $query = $this->database->select('webform_submission', 's')
      ->fields('s', ['entity_type', 'entity_id'])
      ->condition('webform_id', $webform->id())
      ->condition('entity_type', '', '<>')
      ->isNotNull('entity_type')
      ->condition('entity_id', '', '<>')
      ->isNotNull('entity_id')
      ->distinct();
    return (int) $query->countQuery()->execute()->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceEntities(WebformInterface $webform) {
    /** @var \Drupal\Core\Database\StatementInterface $result */
    $result = $this->database->select('webform_submission', 's')
      ->fields('s', ['entity_type', 'entity_id'])
      ->condition('webform_id', $webform->id())
      ->condition('entity_type', '', '<>')
      ->isNotNull('entity_type')
      ->condition('entity_id', '', '<>')
      ->isNotNull('entity_id')
      ->distinct()
      ->execute();
    $source_entities = [];
    while ($record = $result->fetchAssoc()) {
      $source_entities[$record['entity_type']][$record['entity_id']] = $record['entity_id'];
    }
    return $source_entities;
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceEntitiesAsOptions(WebformInterface $webform) {
    $options = [];
    $source_entities = $this->getSourceEntities($webform);
    foreach ($source_entities as $entity_type => $entity_ids) {
      $optgroup = (string) $this->entityManager->getDefinition($entity_type)->getCollectionLabel();
      $entities = $this->entityManager->getStorage($entity_type)->loadMultiple($entity_ids);
      foreach ($entities as $entity_id => $entity) {
        if ($entity instanceof TranslatableInterface && $entity->hasTranslation($this->languageManager->getCurrentLanguage()->getId())) {
          $entity = $entity->getTranslation($this->languageManager->getCurrentLanguage()->getId());
        }

        $option_value = "$entity_type:$entity_id";
        $option_text = $entity->label();
        $options[$optgroup][$option_value] = $option_text;
      }
      if (isset($options[$optgroup])) {
        asort($options[$optgroup]);
      }
    }
    return (count($options) === 1) ? reset($options) : $options;
  }

  /****************************************************************************/
  // Query methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function addQueryConditions(AlterableInterface $query, WebformInterface $webform = NULL, EntityInterface $source_entity = NULL, AccountInterface $account = NULL, array $options = []) {
    $this->_addQueryConditions($query, $webform, $source_entity, $account, $options);
  }

  /**
   * Add condition to submission query.
   *
   * @param \Drupal\Core\Database\Query\AlterableInterface|\Drupal\Core\Entity\Query\ConditionInterface $query
   *   A SQL query or entity conditions.
   * @param \Drupal\webform\WebformInterface $webform
   *   (optional) A webform.
   * @param \Drupal\Core\Entity\EntityInterface|null $source_entity
   *   (optional) A webform submission source entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   (optional) The current user account.
   * @param array $options
   *   (optional) Additional options and query conditions.
   *   Options/conditions include:
   *   - in_draft (boolean): NULL will return all saved submissions and drafts.
   *     Defaults to NULL
   *   - check_source_entity (boolean): Check that a source entity is defined.
   *   - interval (int): Limit total within an seconds interval.
   *
   * @todo Webform 8.x-6.x: Remove and move code to ::addQueryConditions.
   */
  private function _addQueryConditions($query, WebformInterface $webform = NULL, EntityInterface $source_entity = NULL, AccountInterface $account = NULL, array $options = []) {
    // Set default options/conditions.
    $options += [
      'check_source_entity' => FALSE,
      'in_draft' => NULL,
      'interval' => NULL,
      'access_check' => TRUE,
    ];

    if ($webform) {
      $query->condition('webform_id', $webform->id());
    }

    if ($source_entity) {
      $query->condition('entity_type', $source_entity->getEntityTypeId());
      $query->condition('entity_id', $source_entity->id());
    }
    elseif ($options['check_source_entity']) {
      $query->notExists('entity_type');
      $query->notExists('entity_id');
    }

    if ($account) {
      $query->condition('uid', $account->id());
      // Add anonymous submission ids stored in $_SESSION.
      if ($account->isAnonymous() && $account->id() == $this->currentUser->id()) {
        $sids = $this->getAnonymousSubmissionIds($account);
        if (empty($sids)) {
          // Look for NULL sid to force returning no results.
          $query->condition('sid', NULL);
        }
        else {
          $query->condition('sid', $sids, 'IN');
        }
      }
    }

    if ($options['in_draft'] !== NULL) {
      // Cast boolean to integer to support SQLite.
      $query->condition('in_draft', (int) $options['in_draft']);
    }

    if ($options['interval']) {
      $query->condition('completed', \Drupal::time()->getRequestTime() - $options['interval'], '>');
    }
    if ($options['access_check'] === FALSE) {
      $query->accessCheck(FALSE);
    }
  }

  /****************************************************************************/
  // Paging methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function getFirstSubmission(WebformInterface $webform, EntityInterface $source_entity = NULL, AccountInterface $account = NULL, array $options = []) {
    return $this->getTerminusSubmission($webform, $source_entity, $account, $options, 'first');
  }

  /**
   * {@inheritdoc}
   */
  public function getLastSubmission(WebformInterface $webform, EntityInterface $source_entity = NULL, AccountInterface $account = NULL, array $options = []) {
    return $this->getTerminusSubmission($webform, $source_entity, $account, $options, 'last');
  }

  /**
   * {@inheritdoc}
   */
  public function getPreviousSubmission(WebformSubmissionInterface $webform_submission, EntityInterface $source_entity = NULL, AccountInterface $account = NULL, array $options = []) {
    return $this->getSiblingSubmission($webform_submission, $source_entity, $account, $options, 'previous');
  }

  /**
   * {@inheritdoc}
   */
  public function getNextSubmission(WebformSubmissionInterface $webform_submission, EntityInterface $source_entity = NULL, AccountInterface $account = NULL, array $options = []) {
    return $this->getSiblingSubmission($webform_submission, $source_entity, $account, $options, 'next');
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceEntityTypes(WebformInterface $webform) {
    $entity_types = Database::getConnection()->select('webform_submission', 's')
      ->distinct()
      ->fields('s', ['entity_type'])
      ->condition('s.webform_id', $webform->id())
      ->condition('s.entity_type', 'webform', '<>')
      ->orderBy('s.entity_type', 'ASC')
      ->execute()
      ->fetchCol();

    $entity_type_labels = \Drupal::service('entity_type.repository')->getEntityTypeLabels();
    ksort($entity_type_labels);

    return array_intersect_key($entity_type_labels, array_flip($entity_types));
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceEntityAsOptions(WebformInterface $webform, $entity_type) {
    $entity_ids = Database::getConnection()->select('webform_submission', 's')
      ->fields('s', ['entity_id'])
      ->condition('s.webform_id', $webform->id())
      ->condition('s.entity_type', $entity_type)
      ->execute()
      ->fetchCol();
    // Limit the number of source entities loaded to 100.
    if (empty($entity_ids) || count($entity_ids) > 100) {
      return [];
    }
    $entities = $this->entityTypeManager->getStorage($entity_type)->loadMultiple($entity_ids);
    $options = [];
    foreach ($entities as $entity_id => $entity) {
      $options[$entity_id] = $entity->label();
    }
    asort($options);
    return $options;
  }

  /**
   * Get a webform submission's terminus (aka first or last).
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   * @param \Drupal\Core\Entity\EntityInterface|null $source_entity
   *   (optional) A webform submission source entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   * @param array $options
   *   (optional) Additional options and query conditions.
   * @param string $terminus
   *   Submission terminus, first or last.
   *
   * @return \Drupal\webform\WebformSubmissionInterface|null
   *   The webform submission's terminus (aka first or last).
   */
  protected function getTerminusSubmission(WebformInterface $webform, EntityInterface $source_entity = NULL, AccountInterface $account = NULL, array $options = [], $terminus = 'first') {
    $options += ['in_draft' => FALSE];
    $query = $this->getQuery();
    $this->addQueryConditions($query, $webform, $source_entity, $account, $options);
    $query->sort('sid', ($terminus == 'first') ? 'ASC' : 'DESC');
    $query->range(0, 1);
    return ($entity_ids = $query->execute()) ? $this->load(reset($entity_ids)) : NULL;
  }

  /**
   * Get a webform submission's sibling.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param \Drupal\Core\Entity\EntityInterface|null $source_entity
   *   (optional) A webform submission source entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   * @param array $options
   *   (optional) Additional options and query conditions.
   * @param string $direction
   *   Direction of the sibliing.
   *
   * @return \Drupal\webform\WebformSubmissionInterface|null
   *   The webform submission's sibling.
   */
  protected function getSiblingSubmission(WebformSubmissionInterface $webform_submission, EntityInterface $source_entity = NULL, AccountInterface $account = NULL, array $options = [], $direction = 'previous') {
    $webform = $webform_submission->getWebform();

    $query = $this->getQuery();
    $this->addQueryConditions($query, $webform, $source_entity, $account, $options);

    if ($direction == 'previous') {
      $query->condition('sid', $webform_submission->id(), '<');
      $query->sort('sid', 'DESC');
    }
    else {
      $query->condition('sid', $webform_submission->id(), '>');
      $query->sort('sid', 'ASC');
    }

    $query->range(0, 1);

    $submission = ($entity_ids = $query->execute()) ? $this->load(reset($entity_ids)) : NULL;

    // If account is specified, we need make sure the user can view the submission.
    if ($submission && $account && !$submission->access('view', $account)) {
      return NULL;
    }

    return $submission;
  }

  /****************************************************************************/
  // WebformSubmissionEntityList methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function getCustomColumns(WebformInterface $webform = NULL, EntityInterface $source_entity = NULL, AccountInterface $account = NULL, $include_elements = TRUE) {
    $column_names = $this->getCustomSetting('columns', [], $webform, $source_entity)
      ?: $this->getDefaultColumnNames($webform, $source_entity, $account, $include_elements);
    $columns = $this->getColumns($webform, $source_entity, $account, $include_elements);
    return $this->filterColumns($column_names, $columns);
  }

  /**
   * {@inheritdoc}
   */
  public function getUserColumns(WebformInterface $webform = NULL, EntityInterface $source_entity = NULL, AccountInterface $account = NULL, $include_elements = TRUE) {
    $column_names = ($webform) ? $webform->getSetting('submission_user_columns', []) : [];
    $column_names = $column_names ?: $this->getUserDefaultColumnNames($webform, $source_entity, $account, $include_elements);
    $columns = $this->getColumns($webform, $source_entity, $account, $include_elements);
    unset($columns['sid']);
    return $this->filterColumns($column_names, $columns);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultColumns(WebformInterface $webform = NULL, EntityInterface $source_entity = NULL, AccountInterface $account = NULL, $include_elements = TRUE) {
    $columns = $this->getColumns($webform, $source_entity, $account, $include_elements);

    // Unset columns.
    unset(
      // Admin columns.
      $columns['sid'],
      $columns['label'],
      $columns['uuid'],
      $columns['in_draft'],
      $columns['completed'],
      $columns['changed']
    );

    // Hide certain unnecessary columns, that have default set to FALSE.
    foreach ($columns as $column_name => $column) {
      if (isset($column['default']) && $column['default'] === FALSE) {
        unset($columns[$column_name]);
      }
    }

    return $columns;
  }

  /**
   * {@inheritdoc}
   */
  public function getSubmissionsColumns() {
    $columns = $this->getColumns(NULL, NULL, NULL, FALSE);

    // Unset columns.
    // Note: 'serial' is displayed instead of 'sid'.
    unset(
      // Admin columns.
      $columns['serial'],
      $columns['label'],
      $columns['uuid'],
      $columns['in_draft'],
      $columns['completed'],
      $columns['changed'],
      // User columns.
      $columns['sticky'],
      $columns['locked'],
      $columns['notes'],
      $columns['uid']
    );
    return $columns;
  }

  /**
   * {@inheritdoc}
   */
  public function getUsersSubmissionsColumns() {
    $columns = $this->getColumns(NULL, NULL, NULL, FALSE);
    // Unset columns.
    // Note: Displaying 'label' instead of 'serial' or 'sid'.
    unset(
      // Admin columns.
      $columns['sid'],
      $columns['serial'],
      $columns['uuid'],
      $columns['in_draft'],
      $columns['completed'],
      $columns['changed'],
      // User columns.
      $columns['sticky'],
      $columns['locked'],
      $columns['notes'],
      $columns['uid'],
      // References columns.
      $columns['webform_id'],
      $columns['entity'],
      // Operations.
      $columns['operations']
    );
    return $columns;
  }

  /**
   * {@inheritdoc}
   */
  public function getColumns(WebformInterface $webform = NULL, EntityInterface $source_entity = NULL, AccountInterface $account = NULL, $include_elements = TRUE) {
    $view_any = ($webform && $webform->access('submission_view_any')) ? TRUE : FALSE;

    $columns = [];

    // Serial number.
    $columns['serial'] = [
      'title' => $this->t('#'),
    ];

    // Submission ID.
    $columns['sid'] = [
      'title' => $this->t('SID'),
    ];

    // Submission label.
    $columns['label'] = [
      'title' => $this->t('Submission title'),
      'sort' => FALSE,
    ];

    // UUID.
    $columns['uuid'] = [
      'title' => $this->t('UUID'),
    ];

    // Draft.
    $columns['in_draft'] = [
      'title' => $this->t('In draft'),
    ];

    if (empty($account)) {
      // Sticky (Starred/Unstarred).
      $columns['sticky'] = [
        'title' => $this->t('Starred'),
      ];

      // Locked.
      $columns['locked'] = [
        'title' => $this->t('Locked'),
      ];

      // Notes.
      $columns['notes'] = [
        'title' => $this->t('Notes'),
      ];
    }

    // Created.
    $columns['created'] = [
      'title' => $this->t('Created'),
    ];

    // Completed.
    $columns['completed'] = [
      'title' => $this->t('Completed'),
    ];

    // Changed.
    $columns['changed'] = [
      'title' => $this->t('Changed'),
    ];

    // Source entity.
    if ($view_any && empty($source_entity)) {
      $columns['entity'] = [
        'title' => $this->t('Submitted to'),
        'sort' => FALSE,
      ];
    }

    // Submitted by.
    if (empty($account)) {
      $columns['uid'] = [
        'title' => $this->t('User'),
      ];
    }

    // Submission language.
    if ($view_any && \Drupal::moduleHandler()->moduleExists('language')) {
      $columns['langcode'] = [
        'title' => $this->t('Language'),
      ];
    }

    // Remote address.
    $columns['remote_addr'] = [
      'title' => $this->t('IP address'),
    ];

    // Webform and source entity for entity.webform_submission.collection.
    // @see /admin/structure/webform/submissions/manage
    if (empty($webform) && empty($source_entity)) {
      $columns['webform_id'] = [
        'title' => $this->t('Webform'),
      ];
      $columns['entity'] = [
        'title' => $this->t('Submitted to'),
        'sort' => FALSE,
      ];
    }

    // Webform elements.
    if ($webform && $include_elements) {
      /** @var \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager */
      $element_manager = \Drupal::service('plugin.manager.webform.element');
      $elements = $webform->getElementsInitializedFlattenedAndHasValue('view');
      foreach ($elements as $element) {
        /** @var \Drupal\webform\Plugin\WebformElementInterface $element_plugin */
        $element_plugin = $element_manager->createInstance($element['#type']);
        // Replace tokens which can be used in an element's #title.
        $element_plugin->replaceTokens($element, $webform);
        $columns += $element_plugin->getTableColumn($element);
      }
    }

    // Operations.
    $columns['operations'] = [
      'title' => $this->t('Operations'),
      'sort' => FALSE,
    ];

    // Add name and format to all columns.
    foreach ($columns as $name => &$column) {
      $column['name'] = $name;
      $column['format'] = 'value';
    }

    return $columns;
  }

  /**
   * {@inheritdoc}
   */
  public function getUserDefaultColumnNames(WebformInterface $webform = NULL, EntityInterface $source_entity = NULL, AccountInterface $account = NULL, $include_elements = TRUE) {
    return ['serial', 'created', 'remote_addr'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultColumnNames(WebformInterface $webform = NULL, EntityInterface $source_entity = NULL, AccountInterface $account = NULL, $include_elements = TRUE) {
    $columns = $this->getDefaultColumns($webform, $source_entity, $account, $include_elements);
    return array_keys($columns);
  }

  /**
   * Get specified columns in specified order.
   *
   * @param array $column_names
   *   An associative array of column names.
   * @param array $columns
   *   An associative array containing all available columns.
   *
   * @return array
   *   An associative array containing all specified columns.
   */
  protected function filterColumns(array $column_names, array $columns) {
    $filtered_columns = [];
    foreach ($column_names as $column_name) {
      if (isset($columns[$column_name])) {
        $filtered_columns[$column_name] = $columns[$column_name];
      }
    }
    return $filtered_columns;
  }

  /****************************************************************************/
  // Custom settings methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function getCustomSetting($name, $default, WebformInterface $webform = NULL, EntityInterface $source_entity = NULL) {
    // Return the default value is webform and source entity is not defined.
    if (!$webform && !$source_entity) {
      return $default;
    }

    $results_customize = $webform->getSetting('results_customize', TRUE);

    $key = "results.custom.$name";
    if (!$source_entity) {
      if ($results_customize && $webform->hasUserData($key)) {
        return $webform->getUserData($key);
      }
      elseif ($webform->hasState($key)) {
        return $webform->getState($key);
      }
      else {
        return $default;
      }
    }

    $source_entity_key = $key .'.' . $source_entity->getEntityTypeId() . '.' . $source_entity->id();
    if ($results_customize && $webform->hasUserData($source_entity_key)) {
      return $webform->getUserData($source_entity_key);
    }
    elseif ($webform->hasState($source_entity_key)) {
      return $webform->getState($source_entity_key);
    }
    elseif ($webform->getState('results.custom.default', FALSE)) {
      return $webform->getState($key, $default);
    }
    else {
      return $default;
    }
  }

  /****************************************************************************/
  // Invoke WebformElement and WebformHandler plugin methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function create(array $values = []) {
    /** @var \Drupal\webform\WebformSubmissionInterface $entity */
    // Pre create is called via the WebformSubmission entity.
    // @see: \Drupal\webform\Entity\WebformSubmission::preCreate
    $entity = parent::create($values);

    $this->invokeWebformElements('postCreate', $entity);
    $this->invokeWebformHandlers('postCreate', $entity);

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  protected function postLoad(array &$entities) {
    /** @var \Drupal\webform\WebformSubmissionInterface $entity */
    $return = parent::postLoad($entities);
    foreach ($entities as $entity) {
      $this->invokeWebformElements('postLoad', $entity);
      $this->invokeWebformHandlers('postLoad', $entity);

      // If this is an anonymous draft.
      // We must add $SESSION to the submission's cache context.
      // @see \Drupal\webform\WebformSubmissionStorage::loadDraft
      // @todo Add support for 'view own submission' permission.
      if ($entity->isDraft() && $entity->getOwner()->isAnonymous()) {
        $entity->addCacheContexts(['session']);
      }
    }
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  protected function doPreSave(EntityInterface $entity) {
    /** @var \Drupal\webform\WebformSubmissionInterface $entity */
    $id = parent::doPreSave($entity);
    $this->invokeWebformElements('preSave', $entity);
    $this->invokeWebformHandlers('preSave', $entity);
    return $id;
  }

  /**
   * {@inheritdoc}
   */
  protected function doSave($id, EntityInterface $entity) {
    /** @var \Drupal\webform\WebformSubmissionInterface $entity */
    if ($entity->getWebform()->getSetting('results_disabled')) {
      return WebformSubmissionStorageInterface::SAVED_DISABLED;
    }

    $is_new = $entity->isNew();

    if (!$entity->serial()) {
      $next_serial = $this->entityManager->getStorage('webform')->getSerial($entity->getWebform());
      $entity->set('serial', $next_serial);
    }

    $result = parent::doSave($id, $entity);

    // Save data.
    $this->saveData($entity, !$is_new);

    // Set anonymous draft token.
    // This only needs to be called for new anonymous submissions.
    if ($is_new) {
      $this->setAnonymousSubmission($entity);
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  protected function doPostSave(EntityInterface $entity, $update) {
    /** @var \Drupal\webform\WebformSubmissionInterface $entity */
    parent::doPostSave($entity, $update);

    $webform = $entity->getWebform();

    if ($webform->hasSubmissionLog()) {
      // Log webform submission events to the 'webform_submission' log.
      $context = [
        '@title' => $entity->label(),
        'link' => ($entity->id()) ? $entity->toLink($this->t('Edit'), 'edit-form')->toString() : NULL,
        'webform_submission' => $entity,
      ];
      switch ($entity->getState()) {
        case WebformSubmissionInterface::STATE_DRAFT_UPDATED:
        case WebformSubmissionInterface::STATE_DRAFT_CREATED:
          if ($update) {
            $message = '@title draft updated.';
            $context['operation'] = 'draft updated';
          }
          else {
            $message = '@title draft created.';
            $context['operation'] = 'draft created';
          }
          break;

        case WebformSubmissionInterface::STATE_COMPLETED:
          if ($update) {
            $message = '@title completed using saved draft.';
            $context['operation'] = 'submission completed';
          }
          else {
            $message = '@title created.';
            $context['operation'] = 'submission created';
          }
          break;

        case WebformSubmissionInterface::STATE_CONVERTED:
          $message = '@title converted from anonymous to @user.';
          $context['operation'] = 'submission converted';
          $context['@user'] = $entity->getOwner()->label();
          break;

        case WebformSubmissionInterface::STATE_UPDATED:
          $message = '@title updated.';
          $context['operation'] = 'submission updated';
          break;

        case WebformSubmissionInterface::STATE_UNSAVED:
          $message = '@title submitted.';
          $context['operation'] = 'submission submitted';
          break;

        case WebformSubmissionInterface::STATE_LOCKED:
          $message = '@title locked.';
          $context['operation'] = 'submission locked';
          break;

        default:
          throw new \Exception('Unexpected webform submission state');
      }
      \Drupal::logger('webform_submission')->notice($message, $context);
    }
    elseif (!$webform->getSetting('results_disabled')) {
      // Log general events to the 'webform'.
      switch ($entity->getState()) {
        case WebformSubmissionInterface::STATE_DRAFT_CREATED:
          $message = '@title draft created.';
          break;

        case WebformSubmissionInterface::STATE_DRAFT_UPDATED:
          $message = '@title draft updated.';
          break;

        case WebformSubmissionInterface::STATE_UPDATED:
          $message = '@title updated.';
          break;

        case WebformSubmissionInterface::STATE_COMPLETED:
          $message = ($update) ? '@title completed.' : '@title created.';
          break;

        default:
          $message = NULL;
          break;
      }
      if ($message) {
        $context = [
          '@id' => $entity->id(),
          '@title' => $entity->label(),
          'link' => ($entity->id()) ? $entity->toLink($this->t('Edit'), 'edit-form')->toString() : NULL,
        ];
        \Drupal::logger('webform')->notice($message, $context);
      }
    }

    $this->invokeWebformElements('postSave', $entity, $update);
    $this->invokeWebformHandlers('postSave', $entity, $update);
  }

  /**
   * {@inheritdoc}
   */
  public function resave(EntityInterface $entity) {
    /** @var \Drupal\webform\WebformSubmissionInterface $entity */

    $transaction = $this->database->startTransaction();
    try {
      $return = $this->doSave($entity->id(), $entity);

      // Ignore replica server temporarily.
      $this->replicaKillSwitch->trigger();
      return $return;
    }
    catch (\Exception $e) {
      $transaction->rollBack();
      watchdog_exception($this->entityTypeId, $e);
      throw new EntityStorageException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $entities) {
    /** @var \Drupal\webform\WebformSubmissionInterface $entity */
    if (!$entities) {
      // If no entities were passed, do nothing.
      return;
    }

    foreach ($entities as $entity) {
      $this->invokeWebformElements('preDelete', $entity);
      $this->invokeWebformHandlers('preDelete', $entity);
    }

    $return = parent::delete($entities);
    $this->deleteData($entities);

    foreach ($entities as $entity) {
      $this->invokeWebformElements('postDelete', $entity);
      $this->invokeWebformHandlers('postDelete', $entity);

      WebformManagedFileBase::deleteFiles($entity);
    }

    // Remove empty webform submission specific file directory
    // for all stream wrappers.
    // @see \Drupal\webform\Plugin\WebformElement\WebformManagedFileBase
    // @see \Drupal\webform\Plugin\WebformElement\WebformSignature
    foreach ($entities as $entity) {
      $webform = $entity->getWebform();
      if ($webform) {
        $stream_wrappers = array_keys(\Drupal::service('stream_wrapper_manager')
          ->getNames(StreamWrapperInterface::WRITE_VISIBLE));
        foreach ($stream_wrappers as $stream_wrapper) {
          $file_directory = $stream_wrapper . '://webform/' . $webform->id() . '/' . $entity->id();
          // Clear empty webform submission directory.
          if (file_exists($file_directory)
            && empty(file_scan_directory($file_directory, '/.*/'))) {
            $this->fileSystem->deleteRecursive($file_directory);
          }
        }
      }
    }

    // Log deleted.
    foreach ($entities as $entity) {
      $webform = $entity->getWebform();
      \Drupal::logger('webform')
        ->notice('Deleted @form: Submission #@id.', [
          '@id' => $entity->id(),
          '@form' => ($webform) ? $webform->label() : '[' . t('Webform') . ']',
        ]);
    }

    return $return;
  }

  /****************************************************************************/
  // Invoke methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function invokeWebformHandlers($method, WebformSubmissionInterface $webform_submission, &$context1 = NULL, &$context2 = NULL) {
    $webform = $webform_submission->getWebform();
    if ($webform) {
      return $webform->invokeHandlers($method, $webform_submission, $context1, $context2);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function invokeWebformElements($method, WebformSubmissionInterface $webform_submission, &$context1 = NULL, &$context2 = NULL) {
    $webform = $webform_submission->getWebform();
    if ($webform) {
      $webform->invokeElements($method, $webform_submission, $context1, $context2);
    }
  }

  /****************************************************************************/
  // Purge methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function purge($count) {
    $days_to_seconds = 60 * 60 * 24;

    $query = $this->entityManager->getStorage('webform')->getQuery();
    $query->accessCheck(FALSE);
    $query->condition('settings.purge', [self::PURGE_DRAFT, self::PURGE_COMPLETED, self::PURGE_ALL], 'IN');
    $query->condition('settings.purge_days', 0, '>');
    $webforms_to_purge = array_values($query->execute());

    $webform_submissions_to_purge = [];

    if (!empty($webforms_to_purge)) {
      $webforms_to_purge = $this->entityManager->getStorage('webform')->loadMultiple($webforms_to_purge);
      foreach ($webforms_to_purge as $webform) {
        $query = $this->getQuery();
        // Since results of this query are never displayed to the user and we
        // actually need to query the entire dataset of webform submissions, we
        // are disabling access check.
        $query->accessCheck(FALSE);
        $query->condition('created', $this->time->getRequestTime() - ($webform->getSetting('purge_days') * $days_to_seconds), '<');
        $query->condition('webform_id', $webform->id());
        switch ($webform->getSetting('purge')) {
          case self::PURGE_DRAFT:
            $query->condition('in_draft', 1);
            break;

          case self::PURGE_COMPLETED:
            $query->condition('in_draft', 0);
            break;
        }
        $query->range(0, $count - count($webform_submissions_to_purge));
        $result = array_values($query->execute());
        if (!empty($result)) {
          $webform_submissions_to_purge = array_merge($webform_submissions_to_purge, $result);
        }
        if (count($webform_submissions_to_purge) == $count) {
          // We've collected enough webform submissions for purging in this run.
          break;
        }
      }
    }

    if (!empty($webform_submissions_to_purge)) {
      $webform_submissions_to_purge = $this->loadMultiple($webform_submissions_to_purge);
      $this->delete($webform_submissions_to_purge);
    }
  }

  /****************************************************************************/
  // Data handlers.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function saveData(WebformSubmissionInterface $webform_submission, $delete_first = TRUE) {
    // Get submission data rows.
    $data = $webform_submission->getData();
    $webform_id = $webform_submission->getWebform()->id();
    $sid = $webform_submission->id();

    $elements = $webform_submission->getWebform()->getElementsInitializedFlattenedAndHasValue();
    $computed_elements = $webform_submission->getWebform()->getElementsComputed();

    $rows = [];
    foreach ($data as $name => $item) {
      $element = (isset($elements[$name])) ? $elements[$name] : ['#webform_multiple' => FALSE, '#webform_composite' => FALSE];

      // Check if this is a computed element which is not
      // stored in the database.
      $is_computed_element = (isset($computed_elements[$name])) ? TRUE : FALSE;
      if ($is_computed_element && empty($element['#store'])) {
        continue;
      }

      if ($element['#webform_composite']) {
        if (is_array($item)) {
          $composite_items = (empty($element['#webform_multiple'])) ? [$item] : $item;
          foreach ($composite_items as $delta => $composite_item) {
            foreach ($composite_item as $property => $value) {
              $rows[] = [
                'webform_id' => $webform_id,
                'sid' => $sid,
                'name' => $name,
                'property' => $property,
                'delta' => $delta,
                'value' => (string) $value,
              ];
            }
          }
        }
      }
      elseif ($element['#webform_multiple']) {
        if (is_array($item)) {
          foreach ($item as $delta => $value) {
            $rows[] = [
              'webform_id' => $webform_id,
              'sid' => $sid,
              'name' => $name,
              'property' => '',
              'delta' => $delta,
              'value' => (string) $value,
            ];
          }
        }
      }
      else {
        $rows[] = [
          'webform_id' => $webform_id,
          'sid' => $sid,
          'name' => $name,
          'property' => '',
          'delta' => 0,
          'value' => (string) $item,
        ];
      }
    }

    if ($delete_first) {
      // Delete existing submission data rows.
      $this->database->delete('webform_submission_data')
        ->condition('sid', $sid)
        ->execute();
    }

    // Insert new submission data rows.
    $query = $this->database
      ->insert('webform_submission_data')
      ->fields(['webform_id', 'sid', 'name', 'property', 'delta', 'value']);
    foreach ($rows as $row) {
      $query->values($row);
    }
    $query->execute();
  }

  /**
   * Save webform submission data from the 'webform_submission_data' table.
   *
   * @param array $webform_submissions
   *   An array of webform submissions.
   */
  protected function loadData(array &$webform_submissions) {
    // Load webform submission data.
    if ($sids = array_keys($webform_submissions)) {
      /** @var \Drupal\Core\Database\StatementInterface $result */
      $result = $this->database->select('webform_submission_data', 'sd')
        ->fields('sd', ['webform_id', 'sid', 'name', 'property', 'delta', 'value'])
        ->condition('sd.sid', $sids, 'IN')
        ->orderBy('sd.sid', 'ASC')
        ->orderBy('sd.name', 'ASC')
        ->orderBy('sd.property', 'ASC')
        ->orderBy('sd.delta', 'ASC')
        ->execute();
      $submissions_data = [];
      while ($record = $result->fetchAssoc()) {
        $sid = $record['sid'];
        $name = $record['name'];

        /** @var \Drupal\webform\WebformInterface $webform */
        $webform = $webform_submissions[$sid]->getWebform();
        $elements = ($webform) ? $webform->getElementsInitializedFlattenedAndHasValue() : [];
        $element = (isset($elements[$name])) ? $elements[$name] : ['#webform_multiple' => FALSE, '#webform_composite' => FALSE];

        if ($element['#webform_composite']) {
          if ($element['#webform_multiple']) {
            $submissions_data[$sid][$name][$record['delta']][$record['property']] = $record['value'];
          }
          else {
            $submissions_data[$sid][$name][$record['property']] = $record['value'];
          }
        }
        elseif ($element['#webform_multiple']) {
          $submissions_data[$sid][$name][$record['delta']] = $record['value'];
        }
        else {
          $submissions_data[$sid][$name] = $record['value'];
        }
      }

      // Set webform submission data via setData().
      foreach ($submissions_data as $sid => $submission_data) {
        $webform_submission = $webform_submissions[$sid];
        $webform_submission->setData($submission_data);
        $webform_submission->setOriginalData($webform_submission->getData());
      }
    }
  }

  /**
   * Delete webform submission data from the 'webform_submission_data' table.
   *
   * @param array $webform_submissions
   *   An array of webform submissions.
   */
  protected function deleteData(array $webform_submissions) {
    $sids = [];
    foreach ($webform_submissions as $webform_submission) {
      $sids[$webform_submission->id()] = $webform_submission->id();
    }
    $this->database->delete('webform_submission_data')
      ->condition('sid', $sids, 'IN')
      ->execute();
  }

  /****************************************************************************/
  // Log methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function log(WebformSubmissionInterface $webform_submission, array $context = []) {
    // Submission ID is required for logging.
    if (empty($webform_submission->id())) {
      return;
    }

    $message = $context['message'];
    unset($context['message']);

    $context += [
      'uid' => $this->currentUser->id(),
      'webform_submission' => $webform_submission,
      'handler_id' => NULL,
      'data' => [],
      'link' => $webform_submission->toLink($this->t('Edit'), 'edit-form')->toString(),
    ];

    \Drupal::logger('webform_submission')->notice($message, $context);
  }

  /****************************************************************************/
  // Draft methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function loadDraft(WebformInterface $webform, EntityInterface $source_entity = NULL, AccountInterface $account = NULL) {
    $options = [
      'check_source_entity' => TRUE,
      'in_draft' => TRUE,
    ];

    $query = $this->getQuery();
    // Because draft is somewhat different from a complete webform submission,
    // we allow to bypass access check. Moreover, draft here is enforced to be
    // authored by the $account user. Thus we hardly open any security breach
    // here.
    $query->accessCheck(FALSE);
    $this->addQueryConditions($query, $webform, $source_entity, $account, $options);

    // Only load the most recent draft.
    $query->sort('sid', 'DESC');

    return ($sids = $query->execute()) ? $this->load(reset($sids)) : NULL;
  }

  /****************************************************************************/
  // Anonymous submission methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function userLogin(UserInterface $account) {
    if (empty($_SESSION['webform_submissions'])) {
      return;
    }

    // Move all anonymous submissions to UID of this account.
    $query = $this->getQuery();
    $query->accessCheck(FALSE);
    $query->condition('uid', 0);
    $query->condition('sid', $_SESSION['webform_submissions'], 'IN');
    $query->sort('sid');
    if ($sids = $query->execute()) {
      $webform_submissions = $this->loadMultiple($sids);
      foreach ($webform_submissions as $sid => $webform_submission) {
        $webform = $webform_submission->getWebform();
        // Do not convert confidential submissions and check for convert
        // anonymous setting.
        if ($webform->isConfidential() || empty($webform->getSetting('form_convert_anonymous'))) {
          continue;
        }

        unset($_SESSION['webform_submissions'][$sid]);
        $webform_submission->convert($account);
      }
    }

    // Now that the user has logged in because when the log out $_SESSION is
    // completely reset.
    unset($_SESSION['webform_submissions']);
  }

  /**
   * {@inheritdoc}
   */
  public function getAnonymousSubmissionIds(AccountInterface $account) {
    // Make sure the account and current user are identical.
    if ($account->id() != $this->currentUser->id()) {
      return NULL;
    }

    if (empty($_SESSION['webform_submissions'])) {
      return NULL;
    }

    // Cleanup sids because drafts could have been purged or the webform
    // submission could have been deleted.
    $_SESSION['webform_submissions'] = $this->getQuery()
      // Disable access check because user having 'sid' in his $_SESSION already
      // implies he has access to it.
      ->accessCheck(FALSE)
      ->condition('sid', $_SESSION['webform_submissions'], 'IN')
      ->sort('sid')
      ->execute();
    if (empty($_SESSION['webform_submissions'])) {
      unset($_SESSION['webform_submissions']);
      return NULL;
    }

    return $_SESSION['webform_submissions'];
  }

  /**
   * Track anonymous submissions.
   *
   * Anonymous submission are tracked so that they can be assigned to the user
   * if they login.
   *
   * We use session for storing draft tokens. So we can only do it for the
   * current user.
   *
   * We do not use PrivateTempStore because it utilizes session ID as the key in
   * key-value hash map where it stores its data. During user login the session
   * ID is regenerated (see user_login_finalize()) so it is not suitable for us
   * since we need to "carry" the draft tokens from anonymous session to the
   * logged in one.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   *
   * @see WebformSubmissionStorage::loadDraft
   * @see WebformSubmissionStorage::userLogin
   */
  protected function setAnonymousSubmission(WebformSubmissionInterface $webform_submission) {
    // Make sure the account and current user are identical.
    if ($webform_submission->getOwnerId() != $this->currentUser->id()) {
      return;
    }

    // Make sure the submission is anonymous.
    if (!$webform_submission->getOwner()->isAnonymous()) {
      return;
    }

    // Check if anonymous users are allowed to save submission using $_SESSION.
    if ($this->hasAnonymousSubmissionTracking($webform_submission)) {
      $_SESSION['webform_submissions'][$webform_submission->id()] = $webform_submission->id();
    }
  }

  /**
   * Check if anonymous users submission are tracked using $_SESSION.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   *
   * @return bool
   *   TRUE if anonymous users submission are tracked using $_SESSION.
   */
  protected function hasAnonymousSubmissionTracking(WebformSubmissionInterface $webform_submission) {
    $webform = $webform_submission->getWebform();
    if ($this->currentUser->hasPermission('view own webform submission')) {
      return TRUE;
    }
    elseif ($this->accessRulesManager->checkWebformSubmissionAccess('view_own', $this->currentUser, $webform_submission)->isAllowed()) {
      return TRUE;
    }
    elseif ($webform->getSetting('limit_user') || ($webform->getSetting('entity_limit_user') && $webform_submission->getSourceEntity())) {
      return TRUE;
    }
    elseif ($webform->getSetting('form_convert_anonymous')) {
      return TRUE;
    }
    elseif ($webform->getSetting('draft') === WebformInterface::DRAFT_ALL) {
      return TRUE;
    }
    elseif ($webform->hasAnonymousSubmissionTrackingHandler()) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

}
