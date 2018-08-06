<?php

namespace Drupal\webform\Entity;

use Drupal\Core\Serialization\Yaml;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Defines the WebformSubmission entity.
 *
 * @ingroup webform
 *
 * @ContentEntityType(
 *   id = "webform_submission",
 *   label = @Translation("Webform submission"),
 *   bundle_label = @Translation("Webform"),
 *   handlers = {
 *     "storage" = "Drupal\webform\WebformSubmissionStorage",
 *     "storage_schema" = "Drupal\webform\WebformSubmissionStorageSchema",
 *     "views_data" = "Drupal\webform\WebformSubmissionViewsData",
 *     "view_builder" = "Drupal\webform\WebformSubmissionViewBuilder",
 *     "list_builder" = "Drupal\webform\WebformSubmissionListBuilder",
 *     "access" = "Drupal\webform\WebformSubmissionAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\webform\WebformSubmissionForm",
 *       "edit" = "Drupal\webform\WebformSubmissionForm",
 *       "edit_all" = "Drupal\webform\WebformSubmissionForm",
 *       "api" = "Drupal\webform\WebformSubmissionForm",
 *       "test" = "Drupal\webform\WebformSubmissionForm",
 *       "notes" = "Drupal\webform\WebformSubmissionNotesForm",
 *       "duplicate" = "Drupal\webform\WebformSubmissionDuplicateForm",
 *       "delete" = "Drupal\webform\Form\WebformSubmissionDeleteForm",
 *     },
 *   },
 *   bundle_entity_type = "webform",
 *   list_cache_contexts = { "user" },
 *   base_table = "webform_submission",
 *   admin_permission = "administer webform",
 *   entity_keys = {
 *     "id" = "sid",
 *     "bundle" = "webform_id",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/webform/manage/{webform}/submission/{webform_submission}",
 *     "table" = "/admin/structure/webform/manage/{webform}/submission/{webform_submission}/table",
 *     "text" = "/admin/structure/webform/manage/{webform}/submission/{webform_submission}/text",
 *     "yaml" = "/admin/structure/webform/manage/{webform}/submission/{webform_submission}/yaml",
 *     "yaml" = "/admin/structure/webform/manage/{webform}/submission/{webform_submission}/yaml",
 *     "edit-form" = "/admin/structure/webform/manage/{webform}/submission/{webform_submission}/edit",
 *     "notes-form" = "/admin/structure/webform/manage/{webform}/submission/{webform_submission}/notes",
 *     "resend-form" = "/admin/structure/webform/manage/{webform}/submission/{webform_submission}/resend",
 *     "duplicate-form" = "/admin/structure/webform/manage/{webform}/submission/{webform_submission}/duplicate",
 *     "delete-form" = "/admin/structure/webform/manage/{webform}/submission/{webform_submission}/delete",
 *     "collection" = "/admin/structure/webform/submissions/manage/list"
 *   },
 *   permission_granularity = "bundle"
 * )
 */
class WebformSubmission extends ContentEntityBase implements WebformSubmissionInterface {

  use EntityChangedTrait;
  use StringTranslationTrait;

  /**
   * Store a reference to the current temporary webform.
   *
   * @var \Drupal\webform\WebformInterface
   *
   * @see \Drupal\webform\WebformEntityElementsValidator::validateRendering()
   */
  protected static $webform;

  /**
   * The data.
   *
   * @var array
   */
  protected $data = [];

  /**
   * Reference to original data loaded before any updates.
   *
   * @var array
   */
  protected $originalData = [];

  /**
   * Flag to indicated is submission is being converted from anonymous to authenticated.
   *
   * @var bool
   */
  protected $converting = FALSE;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['serial'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Serial number'))
      ->setDescription(t('The serial number of the webform submission entity.'))
      ->setReadOnly(TRUE);

    $fields['sid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Submission ID'))
      ->setDescription(t('The ID of the webform submission entity.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('Submission UUID'))
      ->setDescription(t('The UUID of the webform submission entity.'))
      ->setReadOnly(TRUE);

    $fields['token'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Token'))
      ->setDescription(t('A secure token used to look up a submission.'))
      ->setSetting('max_length', 255)
      ->setReadOnly(TRUE);

    $fields['uri'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Submission URI'))
      ->setDescription(t('The URI the user submitted the webform.'))
      ->setSetting('max_length', 2000)
      ->setReadOnly(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the webform submission was first saved as draft or submitted.'));

    $fields['completed'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Completed'))
      ->setDescription(t('The time that the webform submission was submitted as complete (not draft).'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the webform submission was last saved (complete or draft).'));

    $fields['in_draft'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Is draft'))
      ->setDescription(t('Is this a draft of the submission?'))
      ->setDefaultValue(FALSE);

    $fields['current_page'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Current page'))
      ->setDescription(t('The current wizard page.'))
      ->setSetting('max_length', 128);

    $fields['remote_addr'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Remote IP address'))
      ->setDescription(t('The IP address of the user that submitted the webform.'))
      ->setSetting('max_length', 128);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Submitted by'))
      ->setDescription(t('The username of the user that submitted the webform.'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback('Drupal\webform\Entity\WebformSubmission::getCurrentUserId');

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language'))
      ->setDescription(t('The submission language code.'));

    $fields['webform_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Webform'))
      ->setDescription(t('The associated webform.'))
      ->setSetting('target_type', 'webform');

    $fields['entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Submitted to: Entity type'))
      ->setDescription(t('The entity type to which this submission was submitted from.'))
      ->setSetting('is_ascii', TRUE)
      ->setSetting('max_length', EntityTypeInterface::ID_MAX_LENGTH);

    // Can't use entity reference without a target type because it defaults to
    // an integer which limits reference to only content entities (and not
    // config entities like Views, Panels, etc...).
    // @see \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem::propertyDefinitions()
    $fields['entity_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Submitted to: Entity ID'))
      ->setDescription(t('The ID of the entity of which this webform submission was submitted from.'))
      ->setSetting('max_length', 255);

    $fields['sticky'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Sticky'))
      ->setDescription(t('A flag that indicate the status of the webform submission.'))
      ->setDefaultValue(FALSE);

    $fields['notes'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Notes'))
      ->setDescription(t('Administrative notes about the webform submission.'))
      ->setDefaultValue('');

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function serial() {
    return $this->get('serial')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    $submission_label = $this->getWebform()->getSetting('submission_label')
      ?: \Drupal::config('webform.settings')->get('settings.default_submission_label');
    return \Drupal::service('webform.token_manager')->replace($submission_label, $this);
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    if (isset($this->get('created')->value)) {
      return $this->get('created')->value;
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($created) {
    $this->set('created', $created);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getChangedTime() {
    return $this->get('changed')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setChangedTime($timestamp) {
    $this->set('changed', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCompletedTime() {
    return $this->get('completed')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCompletedTime($timestamp) {
    $this->set('completed', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getNotes() {
    return $this->get('notes')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setNotes($notes) {
    $this->set('notes', $notes);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSticky() {
    return $this->get('sticky')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSticky($sticky) {
    $this->set('sticky', $sticky);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRemoteAddr() {
    return $this->get('remote_addr')->value ?: $this->t('(unknown)');
  }

  /**
   * {@inheritdoc}
   */
  public function setRemoteAddr($ip_address) {
    $this->set('remote_addr', $ip_address);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentPage() {
    return $this->get('current_page')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCurrentPage($current_page) {
    $this->set('current_page', $current_page);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentPageTitle() {
    $current_page = $this->getCurrentPage();
    $page = $this->getWebform()->getPage('default', $current_page);
    return ($page && isset($page['#title'])) ? $page['#title'] : $current_page;
  }

  /**
   * {@inheritdoc}
   */
  public function getElementData($key) {
    return (isset($this->data[$key])) ? $this->data[$key] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setElementData($key, $value) {
    // Make sure the element exists before setting its value.
    if ($this->getWebform()->getElement($key)) {
      $this->data[$key] = $value;
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getData() {
    return $this->data;
  }

  /**
   * {@inheritdoc}
   */
  public function setData(array $data) {
    $this->data = $data;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOriginalData() {
    return $this->originalData;
  }

  /**
   * {@inheritdoc}
   */
  public function setOriginalData(array $data) {
    $this->originalData = $data;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getToken() {
    return $this->token->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getWebform() {
    if (isset($this->webform_id->entity)) {
      return $this->webform_id->entity;
    }
    else {
      return static::$webform;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceEntity() {
    if ($this->entity_type->value && $this->entity_id->value) {
      $entity_type = $this->entity_type->value;
      $entity_id = $this->entity_id->value;
      return $this->entityTypeManager()->getStorage($entity_type)->load($entity_id);
    }
    else {
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceUrl() {
    $uri = $this->uri->value;
    if ($uri !== NULL && ($url = \Drupal::pathValidator()->getUrlIfValid($uri))) {
      return $url->setOption('absolute', TRUE);
    }
    elseif (($entity = $this->getSourceEntity()) && $entity->hasLinkTemplate('canonical')) {
      return $entity->toUrl()->setOption('absolute', TRUE);
    }
    else {
      return $this->getWebform()->toUrl()->setOption('absolute', TRUE);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTokenUrl() {
    return $this->getSourceUrl()
      ->setOption('query', ['token' => $this->token->value]);
  }

  /**
   * {@inheritdoc}
   */
  public function invokeWebformHandlers($method, &$context1 = NULL, &$context2 = NULL) {
    if ($webform = $this->getWebform()) {
      $webform->invokeHandlers($method, $this, $context1, $context2);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function invokeWebformElements($method, &$context1 = NULL, &$context2 = NULL) {
    if ($webform = $this->getWebform()) {
      $webform->invokeElements($method, $this, $context1, $context2);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    $user = $this->get('uid')->entity;
    if (!$user || $user->isAnonymous()) {
      $user = User::getAnonymousUser();
    }
    return $user;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isDraft() {
    return $this->get('in_draft')->value ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isConverting() {
    return $this->converting;
  }

  /**
   * {@inheritdoc}
   */
  public function isCompleted() {
    return $this->get('completed')->value ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isSticky() {
    return (bool) $this->get('sticky')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function hasNotes() {
    return $this->notes ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getState() {
    if (!$this->id()) {
      return self::STATE_UNSAVED;
    }
    elseif ($this->isConverting()) {
      return self::STATE_CONVERTED;
    }
    elseif ($this->isDraft()) {
      return self::STATE_DRAFT;
    }
    elseif ($this->completed->value == $this->changed->value) {
      return self::STATE_COMPLETED;
    }
    else {
      return self::STATE_UPDATED;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);
    $uri_route_parameters['webform'] = $this->getWebform()->id();
    return $uri_route_parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function createDuplicate() {
    /** @var \Drupal\webform\WebformSubmissionInterface $duplicate */
    $duplicate = parent::createDuplicate();

    $duplicate->set('serial', NULL);
    $duplicate->set('token', Crypt::randomBytesBase64());

    // Clear state.
    $duplicate->set('in_draft', FALSE);
    $duplicate->set('current_page', NULL);

    // Create timestamps.
    $duplicate->set('created', NULL);
    $duplicate->set('changed', NULL);
    $duplicate->set('completed', NULL);

    // Clear admin notes and sticky.
    $duplicate->set('notes', '');
    $duplicate->set('sticky', FALSE);

    return $duplicate;
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage, array &$values) {
    if (empty($values['webform_id']) && empty($values['webform'])) {
      if (empty($values['webform_id'])) {
        throw new \Exception('Webform id (webform_id) is required to create a webform submission.');
      }
      elseif (empty($values['webform'])) {
        throw new \Exception('Webform (webform) is required to create a webform submission.');
      }
    }

    // Get temporary webform entity and store it in the static
    // WebformSubmission::$webform property.
    // This could be reworked to use \Drupal\user\PrivateTempStoreFactory
    // but it might be overkill since we are just using this to validate
    // that a webform's elements can be rendered.
    // @see \Drupal\webform\WebformEntityElementsValidator::validateRendering()
    // @see \Drupal\webform_ui\Form\WebformUiElementTestForm::buildForm()
    if (isset($values['webform']) && ($values['webform'] instanceof WebformInterface)) {
      $webform = $values['webform'];
      static::$webform = $values['webform'];
      $values['webform_id'] = $values['webform']->id();
    }
    else {
      /** @var \Drupal\webform\WebformInterface $webform */
      $webform = Webform::load($values['webform_id']);
      static::$webform = NULL;
    }

    // Get request's source entity parameter.
    /** @var \Drupal\webform\WebformRequestInterface $request_handler */
    $request_handler = \Drupal::service('webform.request');
    $source_entity = $request_handler->getCurrentSourceEntity('webform');
    $values += [
      'entity_type' => ($source_entity) ? $source_entity->getEntityTypeId() : NULL,
      'entity_id' => ($source_entity) ? $source_entity->id() : NULL,
    ];

    // Decode all data in an array.
    if (empty($values['data'])) {
      $values['data'] = [];
    }
    elseif (is_string($values['data'])) {
      $values['data'] = Yaml::decode($values['data']);
    }

    // Get default date from source entity 'webform' field.
    if ($values['entity_type'] && $values['entity_id']) {
      $source_entity = \Drupal::entityTypeManager()
        ->getStorage($values['entity_type'])
        ->load($values['entity_id']);

      /** @var \Drupal\webform\WebformEntityReferenceManagerInterface $entity_reference_manager */
      $entity_reference_manager = \Drupal::service('webform.entity_reference_manager');

      if ($webform_field_name = $entity_reference_manager->getFieldName($source_entity)) {
        if ($source_entity->$webform_field_name->target_id == $webform->id() && $source_entity->$webform_field_name->default_data) {
          $values['data'] += Yaml::decode($source_entity->$webform_field_name->default_data);
        }
      }
    }

    // Set default values.
    $current_request = \Drupal::requestStack()->getCurrentRequest();
    $values += [
      'in_draft' => FALSE,
      'uid' => \Drupal::currentUser()->id(),
      'langcode' => \Drupal::languageManager()->getCurrentLanguage()->getId(),
      'token' => Crypt::randomBytesBase64(),
      'uri' => preg_replace('#^' . base_path() . '#', '/', $current_request->getRequestUri()),
      'remote_addr' => ($webform && $webform->isConfidential()) ? '' : $current_request->getClientIp(),
    ];

    $webform->invokeHandlers(__FUNCTION__, $values);
    $webform->invokeElements(__FUNCTION__, $values);
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    // Set created.
    if (!$this->created->value) {
      $this->created->value = REQUEST_TIME;
    }

    // Set changed.
    $this->changed->value = REQUEST_TIME;

    // Set completed.
    if ($this->isDraft()) {
      $this->completed->value = NULL;
    }
    elseif (!$this->isCompleted()) {
      $this->completed->value = REQUEST_TIME;
    }

    parent::preSave($storage);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    // Clear the remote_addr for confidential submissions.
    if ($this->getWebform()->isConfidential()) {
      $this->get('remote_addr')->value = '';
    }

    return parent::save();
  }

  /**
   * {@inheritdoc}
   */
  public function convert(UserInterface $account) {
    $this->converting = TRUE;
    $this->setOwner($account);
    $this->save();
    $this->converting = FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function toArray($custom = FALSE, $check_access = FALSE) {
    if ($custom === FALSE) {
      return parent::toArray();
    }
    else {
      $values = parent::toArray();
      foreach ($values as $key => $item) {
        // Issue #2567899 It seems it is impossible to save an empty string to an entity.
        // @see https://www.drupal.org/node/2567899
        // Solution: Set empty (aka NULL) items to an empty string.
        if (empty($item)) {
          $values[$key] = '';
        }
        else {
          $value = reset($item);
          $values[$key] = reset($value);
        }
      }

      $values['data'] = $this->getData();

      // Check access.
      if ($check_access) {
        // Check field definition access.
        $submission_storage = \Drupal::entityTypeManager()->getStorage('webform_submission');
        $field_definitions = $submission_storage->getFieldDefinitions();
        $field_definitions = $submission_storage->checkFieldDefinitionAccess($this->getWebform(), $field_definitions + ['data' => TRUE]);
        $values = array_intersect_key($values, $field_definitions);

        // Check element data access.
        $elements = $this->getWebform()->getElementsInitializedFlattenedAndHasValue('view');
        $values['data'] = array_intersect_key($values['data'], $elements);
      }

      return $values;
    }
  }


  /**
   * Default value callback for 'uid' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getCurrentUserId() {
    return [\Drupal::currentUser()->id()];
  }

}
