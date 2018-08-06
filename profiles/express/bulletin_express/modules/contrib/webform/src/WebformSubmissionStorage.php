<?php

namespace Drupal\webform;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\AlterableInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\user\UserInterface;
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
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * WebformSubmissionStorage constructor.
   */
  public function __construct(EntityTypeInterface $entity_type, Connection $database, EntityManagerInterface $entity_manager, CacheBackendInterface $cache, LanguageManagerInterface $language_manager, AccountProxyInterface $current_user) {
    parent::__construct($entity_type, $database, $entity_manager, $cache, $language_manager);

    $this->currentUser = $current_user;
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
      $container->get('current_user')
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
    if (!$webform->access('submission_upates_any')) {
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
  public function loadMultiple(array $ids = NULL) {
    /** @var \Drupal\webform\WebformSubmissionInterface[] $webform_submissions */
    $webform_submissions = parent::loadMultiple($ids);
    $this->loadData($webform_submissions);
    return $webform_submissions;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll(WebformInterface $webform = NULL, EntityInterface $source_entity = NULL, $limit = NULL, $max_sid = NULL) {
    $query = $this->getQuery();
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
  public function getTotal(WebformInterface $webform = NULL, EntityInterface $source_entity = NULL, AccountInterface $account = NULL) {
    $query = $this->getQuery();
    $this->addQueryConditions($query, $webform, $source_entity, $account, ['in_draft' => FALSE]);

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
  // Query methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function addQueryConditions(AlterableInterface $query, WebformInterface $webform = NULL, EntityInterface $source_entity = NULL, AccountInterface $account = NULL, array $options = []) {
    // Set default options/conditions.
    $options += [
      'check_source_entity' => FALSE,
      'in_draft' => NULL,
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
      $query->condition('in_draft', $options['in_draft']);
    }
  }

  /****************************************************************************/
  // Paging methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function getFirstSubmission(WebformInterface $webform, EntityInterface $source_entity = NULL, AccountInterface $account = NULL) {
    return $this->getTerminusSubmission($webform, $source_entity, $account, 'ASC');
  }

  /**
   * {@inheritdoc}
   */
  public function getLastSubmission(WebformInterface $webform, EntityInterface $source_entity = NULL, AccountInterface $account = NULL) {
    return $this->getTerminusSubmission($webform, $source_entity, $account, 'DESC');
  }

  /**
   * {@inheritdoc}
   */
  public function getPreviousSubmission(WebformSubmissionInterface $webform_submission, EntityInterface $source_entity = NULL, AccountInterface $account = NULL) {
    return $this->getSiblingSubmission($webform_submission, $source_entity, $account, 'previous');
  }

  /**
   * {@inheritdoc}
   */
  public function getNextSubmission(WebformSubmissionInterface $webform_submission, EntityInterface $source_entity = NULL, AccountInterface $account = NULL) {
    return $this->getSiblingSubmission($webform_submission, $source_entity, $account, 'next');
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
  protected function getTerminusSubmission(WebformInterface $webform, EntityInterface $source_entity = NULL, AccountInterface $account = NULL, $sort = 'DESC') {
    $query = $this->getQuery();
    $this->addQueryConditions($query, $webform, $source_entity, $account, ['in_draft' => FALSE]);
    $query->sort('sid', $sort);
    $query->range(0, 1);
    return ($entity_ids = $query->execute()) ? $this->load(reset($entity_ids)) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function getSiblingSubmission(WebformSubmissionInterface $webform_submission, EntityInterface $source_entity = NULL, AccountInterface $account = NULL, $direction = 'previous') {
    $webform = $webform_submission->getWebform();

    $query = $this->getQuery();
    $this->addQueryConditions($query, $webform, $source_entity, $account);

    if ($direction == 'previous') {
      $query->condition('sid', $webform_submission->id(), '<');
      $query->sort('sid', 'DESC');
    }
    else {
      $query->condition('sid', $webform_submission->id(), '>');
      $query->sort('sid', 'ASC');
    }

    $query->range(0, 1);

    return ($entity_ids = $query->execute()) ? $this->load(reset($entity_ids)) : NULL;
  }

  /****************************************************************************/
  // WebformSubmissionEntityList methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function getCustomColumns(WebformInterface $webform = NULL, EntityInterface $source_entity = NULL, AccountInterface $account = NULL, $include_elements = TRUE) {
    // Get custom columns from the webform's state.
    if ($source_entity) {
      $source_key = $source_entity->getEntityTypeId() . '.' . $source_entity->id();
      $custom_column_names = $webform->getState("results.custom.columns.$source_key", []);
      // If the source entity does not have custom columns, then see if we
      // can use the main webform as the default custom columns.
      if (empty($custom_column_names) && $webform->getState("results.custom.default", FALSE)) {
        $custom_column_names = $webform->getState('results.custom.columns', []);
      }
    }
    else {
      $custom_column_names = $webform->getState('results.custom.columns', []);
    }

    if (empty($custom_column_names)) {
      return $this->getDefaultColumns($webform, $source_entity, $account, $include_elements);
    }

    // Get custom column with labels.
    $columns = $this->getColumns($webform, $source_entity, $account, $include_elements);
    $custom_columns = [];
    foreach ($custom_column_names as $column_name) {
      if (isset($columns[$column_name])) {
        $custom_columns[$column_name] = $columns[$column_name];
      }
    }
    return $custom_columns;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultColumns(WebformInterface $webform = NULL, EntityInterface $source_entity = NULL, AccountInterface $account = NULL, $include_elements = TRUE) {
    $columns = $this->getColumns($webform, $source_entity, $account, $include_elements);

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
      'default' => FALSE,
    ];

    // UUID.
    $columns['uuid'] = [
      'title' => $this->t('UUID'),
      'default' => FALSE,
    ];

    // Sticky (Starred/Unstarred).
    if (empty($account)) {
      $columns['sticky'] = [
        'title' => $this->t('Starred'),
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
      'default' => FALSE,
    ];

    // Changed.
    $columns['changed'] = [
      'title' => $this->t('Changed'),
      'default' => FALSE,
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

    // Webform.
    if (empty($webform) && empty($source_entity)) {
      $columns['webform_id'] = [
        'title' => $this->t('Webform'),
      ];
    }

    // Webform elements.
    if ($webform && $include_elements) {
      /** @var \Drupal\webform\WebformElementManagerInterface $element_manager */
      $element_manager = \Drupal::service('plugin.manager.webform.element');
      $elements = $webform->getElementsInitializedFlattenedAndHasValue('view');
      foreach ($elements as $element) {
        /** @var \Drupal\webform\WebformElementInterface $element_handler */
        $element_handler = $element_manager->createInstance($element['#type']);
        $columns += $element_handler->getTableColumn($element);
      }
    }

    // Operations.
    if (empty($account)) {
      $columns['operations'] = [
        'title' => $this->t('Operations'),
        'sort' => FALSE,
      ];
    }

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
  public function getCustomSetting($name, $default, WebformInterface $webform = NULL, EntityInterface $source_entity = NULL) {
    // Return the default value is webform and source entity is not defined.
    if (!$webform && !$source_entity) {
      return $default;
    }

    $key = "results.custom.$name";
    if (!$source_entity) {
      return $webform->getState($key, $default);
    }

    $source_key = $source_entity->getEntityTypeId() . '.' . $source_entity->id();
    if ($webform->hasState("$key.$source_key")) {
      return $webform->getState("$key.$source_key", $default);
    }
    if ($webform->getState("results.custom.default", FALSE)) {
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

      // If this is an anonymous draft..
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
      $entity->set('serial', $this->getNextSerial($entity));
    }

    $result = parent::doSave($id, $entity);

    // Save data.
    $this->saveData($entity, !$is_new);

    // Set anonymous draft token.
    // This only needs to be called for new anonymous submissions.
    if ($is_new) {
      $this->setAnonymousSubmission($entity);
    }

    // DEBUG: dsm($entity->getState());
    // Log transaction.
    $webform = $entity->getWebform();
    $context = [
      '@id' => $entity->id(),
      '@form' => $webform->label(),
      'link' => $entity->toLink($this->t('Edit'), 'edit-form')->toString(),
    ];
    switch ($entity->getState()) {
      case WebformSubmissionInterface::STATE_DRAFT:
        \Drupal::logger('webform')->notice('@form: Submission #@id draft saved.', $context);
        break;

      case WebformSubmissionInterface::STATE_UPDATED:
        \Drupal::logger('webform')->notice('@form: Submission #@id updated.', $context);
        break;

      case WebformSubmissionInterface::STATE_COMPLETED:
        if ($result === SAVED_NEW) {
          \Drupal::logger('webform')->notice('@form: Submission #@id created.', $context);
        }
        else {
          \Drupal::logger('webform')->notice('@form: Submission #@id completed.', $context);
        }
        break;
    }

    return $result;
  }

  /**
   * Returns the next serial number.
   *
   * @return int
   *   The next serial number.
   */
  protected function getNextSerial(WebformSubmissionInterface $webform_submission) {
    $webform = $webform_submission->getWebform();

    $next_serial = $webform->getState('next_serial');
    $max_serial = $this->getMaxSerial($webform);
    $serial = max($next_serial, $max_serial);

    $webform->setState('next_serial', $serial + 1);

    return $serial;
  }

  /**
   * {@inheritdoc}
   */
  public function getMaxSerial(WebformInterface $webform) {
    $query = \Drupal::database()->select('webform_submission');
    $query->condition('webform_id', $webform->id());
    $query->addExpression('MAX(serial)');
    return $query->execute()->fetchField() + 1;
  }

  /**
   * {@inheritdoc}
   */
  protected function doPostSave(EntityInterface $entity, $update) {
    /** @var \Drupal\webform\WebformSubmissionInterface $entity */
    parent::doPostSave($entity, $update);

    // Log submission events.
    if ($entity->getWebform()->hasSubmissionLog()) {
      $t_args = ['@title' => $entity->label()];
      switch ($entity->getState()) {
        case WebformSubmissionInterface::STATE_DRAFT:
          if ($update) {
            $operation = 'draft updated';
            $message = $this->t('@title draft updated.', $t_args);
          }
          else {
            $operation = 'draft created';
            $message = $this->t('@title draft created.', $t_args);
          }
          break;

        case WebformSubmissionInterface::STATE_COMPLETED:
          if ($update) {
            $operation = 'submission completed';
            $message = $this->t('@title completed using saved draft.', $t_args);
          }
          else {
            $operation = 'submission created';
            $message = $this->t('@title created.', $t_args);
          }
          break;

        case WebformSubmissionInterface::STATE_CONVERTED:
          $operation = 'submission converted';
          $message = $this->t('@title converted from anonymous to @user.', $t_args + ['@user' => $entity->getOwner()->label()]);
          break;

        case WebformSubmissionInterface::STATE_UPDATED:
          $operation = 'submission updated';
          $message = $this->t('@title updated.', $t_args);
          break;

        case WebformSubmissionInterface::STATE_UNSAVED:
          $operation = 'submission submitted';
          $message = $this->t('@title submitted.', $t_args);
          break;

        default:
          throw new \Exception('Unexpected webform submission state');
          break;
      }

      $this->log($entity, [
        'handler_id' => '',
        'operation' => $operation,
        'message' => $message,
      ]);
    }

    $this->invokeWebformElements('postSave', $entity, $update);
    $this->invokeWebformHandlers('postSave', $entity, $update);
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
    }

    // Delete submission log after all pre and post delete hooks are called.
    $this->deleteLog($entities);

    // Log deleted.
    foreach ($entities as $entity) {
      \Drupal::logger('webform')
        ->notice('Deleted @form: Submission #@id.', [
          '@id' => $entity->id(),
          '@form' => $entity->getWebform()->label(),
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
    $webform->invokeHandlers($method, $webform_submission, $context1, $context2);
  }

  /**
   * {@inheritdoc}
   */
  public function invokeWebformElements($method, WebformSubmissionInterface $webform_submission, &$context1 = NULL, &$context2 = NULL) {
    $webform = $webform_submission->getWebform();
    $webform->invokeElements($method, $webform_submission, $context1, $context2);
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
    $query->condition('settings.purge', [self::PURGE_DRAFT, self::PURGE_COMPLETED, self::PURGE_ALL], 'IN');
    $query->condition('settings.purge_days', 0, '>');
    $webforms_to_purge = array_values($query->execute());

    $webform_submissions_to_purge = [];

    if (!empty($webforms_to_purge)) {
      $webforms_to_purge = $this->entityManager->getStorage('webform')->loadMultiple($webforms_to_purge);
      foreach ($webforms_to_purge as $webform) {
        $query = $this->getQuery();
        $query->condition('created', REQUEST_TIME - ($webform->getSetting('purge_days') * $days_to_seconds), '<');
        $query->condition('webform_id', $webform->id());
        switch ($webform->getSetting('purge')) {
          case self::PURGE_DRAFT:
            $query->condition('in_draft', TRUE);
            break;

          case self::PURGE_COMPLETED:
            $query->condition('in_draft', FALSE);
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

        $elements = $webform_submissions[$sid]->getWebform()->getElementsInitializedFlattenedAndHasValue();
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
        $webform_submissions[$sid]->setData($submission_data);
        $webform_submissions[$sid]->setOriginalData($submission_data);
      }
    }
  }

  /**
   * Save webform submission data to the 'webform_submission_data' table.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param bool $delete_first
   *   TRUE to delete any data first. For new submissions this is not needed.
   */
  protected function saveData(WebformSubmissionInterface $webform_submission, $delete_first = TRUE) {
    // Get submission data rows.
    $data = $webform_submission->getData();
    $webform_id = $webform_submission->getWebform()->id();
    $sid = $webform_submission->id();

    $elements = $webform_submission->getWebform()->getElementsInitializedFlattenedAndHasValue();

    $rows = [];
    foreach ($data as $name => $item) {
      $element = (isset($elements[$name])) ? $elements[$name] : ['#webform_multiple' => FALSE, '#webform_composite' => FALSE];
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
  public function log(WebformSubmissionInterface $webform_submission, array $values = []) {
    // Submission ID is required for logging.
    // @todo Enable logging for submissions not saved to the database.
    if (empty($webform_submission->id())) {
      return;
    }

    $values += [
      'uid' => $this->currentUser->id(),
      'webform_id' => $webform_submission->getWebform()->id(),
      'sid' => $webform_submission->id() ?: NULL,
      'handler_id' => NULL,
      'data' => [],
      'timestamp' => time(),
    ];
    $values['data'] = serialize($values['data']);
    \Drupal::database()
      ->insert('webform_submission_log')
      ->fields($values)
      ->execute();
  }

  /**
   * Delete webform submission events from the 'webform_submission_log' table.
   *
   * @param array $webform_submissions
   *   An array of webform submissions.
   */
  protected function deleteLog(array $webform_submissions) {
    $sids = [];
    foreach ($webform_submissions as $webform_submission) {
      $sids[$webform_submission->id()] = $webform_submission->id();
    }
    $this->database->delete('webform_submission_log')
      ->condition('sid', $sids, 'IN')
      ->execute();
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
    $this->addQueryConditions($query, $webform, $source_entity, $account, $options);

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

    $_SESSION['webform_submissions'][$webform_submission->id()] = $webform_submission->id();
  }

  /**
   * Get anonymous users sumbmission ids.
   *
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   A user account.
   *
   * @return array|
   *   A array of submission ids or NULL if the user us not anonymous or has
   *   not saved submissions.
   */
  protected function getAnonymousSubmissionIds(AccountInterface $account) {
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
      ->condition('sid', $_SESSION['webform_submissions'], 'IN')
      ->sort('sid')
      ->execute();
    if (empty($_SESSION['webform_submissions'])) {
      unset($_SESSION['webform_submissions']);
      return NULL;
    }

    return $_SESSION['webform_submissions'];
  }

}
