<?php

namespace Drupal\webform\Entity;

use Drupal\Component\Render\PlainTextOutput;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Session\AccountInterface;
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
 *   label_collection = @Translation("Submissions"),
 *   label_singular = @Translation("submission"),
 *   label_plural = @Translation("submissions"),
 *   label_count = @PluralTranslation(
 *     singular = "@count submission",
 *     plural = "@count submissions",
 *   ),
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
 *   list_cache_tags = { "config:webform_list", "webform_submission_list" },
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
 *     "access-denied" = "/admin/structure/webform/manage/{webform}/submission/{webform_submission}/access-denied",
 *     "table" = "/admin/structure/webform/manage/{webform}/submission/{webform_submission}/table",
 *     "text" = "/admin/structure/webform/manage/{webform}/submission/{webform_submission}/text",
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
   * The data with computed values.
   *
   * @var array
   */
  protected $computedData = [];

  /**
   * Flag to indicated if submission is being converted from anonymous to authenticated.
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
    // config entities like Views, Panels, etc…).
    // @see \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem::propertyDefinitions()
    $fields['entity_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Submitted to: Entity ID'))
      ->setDescription(t('The ID of the entity of which this webform submission was submitted from.'))
      ->setSetting('max_length', 255);

    $fields['locked'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Locked'))
      ->setDescription(t('A flag that indicates a locked webform submission.'))
      ->setDefaultValue(FALSE);

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

    /** @var \Drupal\webform\WebformTokenManagerInterface $token_manager */
    $token_manager = \Drupal::service('webform.token_manager');
    return PlainTextOutput::renderFromHtml($token_manager->replaceNoRenderContext($submission_label, $this));
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
  public function setLocked($locked) {
    $this->set('locked', $locked);
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
    $data = $this->getData();
    return (isset($data[$key])) ? $data[$key] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setElementData($key, $value) {
    // Make sure the element exists before setting its value.
    if ($this->getWebform()->getElement($key)) {
      $this->data[$key] = $value;
      $this->computedData = NULL;
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRawData() {
    return $this->data;
  }

  /**
   * {@inheritdoc}
   */
  public function getData() {
    if (isset($this->computedData)) {
      return $this->computedData;
    }

    // If there is no active theme and we can't prematurely start computing
    // element values because it will define and lock the active theme.
    /** @var \Drupal\webform\WebformThemeManagerInterface $theme_manager */
    $theme_manager = \Drupal::service('webform.theme_manager');
    if (!$theme_manager->hasActiveTheme()) {
      return $this->data;
    }

    // Set computed element values in to submission data.
    $this->computedData = $this->data;
    $webform = $this->getWebform();
    if ($webform->hasComputed()) {
      /** @var \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager */
      $element_manager = \Drupal::service('plugin.manager.webform.element');
      $computed_elements = $webform->getElementsComputed();
      foreach ($computed_elements as $computed_element_name) {
        $computed_element = $webform->getElement($computed_element_name);
        /** @var \Drupal\webform\Plugin\WebformElementComputedInterface $element_plugin */
        $element_plugin = $element_manager->getElementInstance($computed_element);
        $this->computedData[$computed_element_name] = $element_plugin->computeValue($computed_element, $this);
      }
    }

    return $this->computedData;
  }

  /**
   * {@inheritdoc}
   */
  public function setData(array $data) {
    $this->data = $data;
    $this->computedData = NULL;
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
  public function getElementOriginalData($key) {
    return (isset($this->originalData[$key])) ? $this->originalData[$key] : NULL;
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
  public function getSourceEntity($translate = FALSE) {
    if ($this->entity_type->value && $this->entity_id->value) {
      $entity_type = $this->entity_type->value;
      $entity_id = $this->entity_id->value;
      $source_entity = $this->entityTypeManager()->getStorage($entity_type)->load($entity_id);

      // If translated is set, get the translated source entity.
      if ($translate && $source_entity instanceof ContentEntityInterface) {
        $langcode = $this->language()->getId();
        if ($source_entity->hasTranslation($langcode)) {
          $source_entity = $source_entity->getTranslation($langcode);
        }
      }
      return $source_entity;
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
  public function getTokenUrl($operation = 'update') {
    switch ($operation) {
      case 'view':
        /** @var \Drupal\webform\WebformRequestInterface $request_handler */
        $request_handler = \Drupal::service('webform.request');
        $url = $request_handler->getUrl($this, $this->getSourceEntity(), 'webform.user.submission');
        break;

      case 'update':
        $url = $this->getSourceUrl();
        break;

      default:
        throw new \Exception("Token URL operation $operation is not supported");
    }

    $options = $url->setAbsolute()->getOptions();
    $options['query']['token'] = $this->getToken();
    return $url->setOptions($options);
  }

  /**
   * {@inheritdoc}
   */
  public function invokeWebformHandlers($method, &$context1 = NULL, &$context2 = NULL, &$context3 = NULL) {
    if ($webform = $this->getWebform()) {
      return $webform->invokeHandlers($method, $this, $context1, $context2, $context3);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function invokeWebformElements($method, &$context1 = NULL, &$context2 = NULL, &$context3 = NULL) {
    if ($webform = $this->getWebform()) {
      $webform->invokeElements($method, $this, $context1, $context2, $context3);
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
  public function isLocked() {
    return $this->get('locked')->value ? TRUE : FALSE;
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
  public function isOwner(AccountInterface $account) {
    if ($account->isAnonymous()) {
      return !empty($_SESSION['webform_submissions']) && isset($_SESSION['webform_submissions'][$this->id()]);
    }
    else {
      return $account->id() === $this->getOwnerId();
    }
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
      return ($this->created->value === $this->changed->value) ? self::STATE_DRAFT_CREATED : self::STATE_DRAFT_UPDATED;
    }
    elseif ($this->isLocked()) {
      return self::STATE_LOCKED;
    }
    elseif ($this->completed->value === $this->changed->value) {
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
    $webform = $this->getWebform();
    if ($webform) {
      $uri_route_parameters['webform'] = $webform->id();
    }
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

    // Clear admin notes, sticky, and locked.
    $duplicate->set('notes', '');
    $duplicate->set('sticky', FALSE);
    $duplicate->set('locked', FALSE);

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
    // This could be reworked to use \Drupal\Core\TempStore\PrivateTempStoreFactory
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
      'remote_addr' => ($webform && $webform->hasRemoteAddr()) ? $current_request->getClientIp() : '',
    ];

    $webform->invokeHandlers(__FUNCTION__, $values);
    $webform->invokeElements(__FUNCTION__, $values);
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    // Because the original data is not stored in the persistent entity storage
    // cache we have to reset it for the original webform submission entity.
    // @see \Drupal\Core\Entity\EntityStorageBase::doPreSave
    // @see \Drupal\Core\Entity\ContentEntityStorageBase::getFromPersistentCache
    if (isset($this->original)) {
      $this->original->setData($this->originalData);
      $this->original->setOriginalData($this->original->getData());
    }

    $request_time = \Drupal::time()->getRequestTime();

    // Set created.
    if (!$this->created->value) {
      $this->created->value = $request_time;
    }

    // Set changed.
    $this->changed->value = $request_time;

    // Set completed.
    if ($this->isDraft()) {
      $this->completed->value = NULL;
    }
    elseif (!$this->isCompleted()) {
      $this->completed->value = $request_time;
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
    if (!$this->getWebform()->hasRemoteAddr()) {
      $this->get('remote_addr')->value = '';
    }

    return parent::save();
  }

  /**
   * {@inheritdoc}
   */
  public function resave() {
    return $this->entityTypeManager()->getStorage($this->entityTypeId)->resave($this);
  }

  /**
   * {@inheritdoc}
   */
  public function access($operation, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $access = parent::access($operation, $account, TRUE)
      ->orIf($this->invokeWebformHandlers('access', $operation, $account));
    return $return_as_object ? $access : $access->isAllowed();
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
