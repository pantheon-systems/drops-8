<?php

namespace Drupal\webform\Entity;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Drupal\webform\Plugin\WebformElement\WebformActions;
use Drupal\webform\Plugin\WebformElement\WebformManagedFileBase;
use Drupal\webform\Plugin\WebformElement\WebformWizardPage;
use Drupal\webform\Utility\WebformElementHelper;
use Drupal\webform\Utility\WebformReflectionHelper;
use Drupal\webform\Plugin\WebformHandlerInterface;
use Drupal\webform\Plugin\WebformHandlerPluginCollection;
use Drupal\webform\WebformException;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\WebformSubmissionStorageInterface;

/**
 * Defines the webform entity.
 *
 * @ConfigEntityType(
 *   id = "webform",
 *   label = @Translation("Webform"),
 *   handlers = {
 *     "storage" = "\Drupal\webform\WebformEntityStorage",
 *     "view_builder" = "Drupal\webform\WebformEntityViewBuilder",
 *     "list_builder" = "Drupal\webform\WebformEntityListBuilder",
 *     "access" = "Drupal\webform\WebformEntityAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\webform\WebformEntityAddForm",
 *       "duplicate" = "Drupal\webform\WebformEntityAddForm",
 *       "delete" = "Drupal\webform\WebformEntityDeleteForm",
 *       "edit" = "Drupal\webform\WebformEntityElementsForm",
 *       "settings" = "Drupal\webform\EntitySettings\WebformEntitySettingsGeneralForm",
 *       "settings_form" = "Drupal\webform\EntitySettings\WebformEntitySettingsFormForm",
 *       "settings_submissions" = "Drupal\webform\EntitySettings\WebformEntitySettingsSubmissionsForm",
 *       "settings_confirmation" = "Drupal\webform\EntitySettings\WebformEntitySettingsConfirmationForm",
 *       "settings_assets" = "Drupal\webform\EntitySettings\WebformEntitySettingsAssetsForm",
 *       "settings_access" = "Drupal\webform\EntitySettings\WebformEntitySettingsAccessForm",
 *       "handlers" = "Drupal\webform\WebformEntityHandlersForm",
 *     }
 *   },
 *   admin_permission = "administer webform",
 *   bundle_of = "webform_submission",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *   },
 *   links = {
 *     "canonical" = "/webform/{webform}",
 *     "submissions" = "/webform/{webform}/submissions",
 *     "add-form" = "/webform/{webform}",
 *     "edit-form" = "/admin/structure/webform/manage/{webform}/elements",
 *     "test-form" = "/webform/{webform}/test",
 *     "duplicate-form" = "/admin/structure/webform/manage/{webform}/duplicate",
 *     "delete-form" = "/admin/structure/webform/manage/{webform}/delete",
 *     "export-form" = "/admin/structure/webform/manage/{webform}/export",
 *     "settings" = "/admin/structure/webform/manage/{webform}/settings",
 *     "settings-form" = "/admin/structure/webform/manage/{webform}/settings/form",
 *     "settings-submissions" = "/admin/structure/webform/manage/{webform}/settings/submissions",
 *     "settings-confirmation" = "/admin/structure/webform/manage/{webform}/settings/confirmation",
 *     "settings-assets" = "/admin/structure/webform/manage/{webform}/settings/assets",
 *     "settings-access" = "/admin/structure/webform/manage/{webform}/settings/access",
 *     "handlers" = "/admin/structure/webform/manage/{webform}/handlers",
 *     "results-submissions" = "/admin/structure/webform/manage/{webform}/results/submissions",
 *     "results-export" = "/admin/structure/webform/manage/{webform}/results/download",
 *     "results-log" = "/admin/structure/webform/manage/{webform}/results/log",
 *     "results-clear" = "/admin/structure/webform/manage/{webform}/results/clear",
 *     "collection" = "/admin/structure/webform",
 *   },
 *   config_export = {
 *     "status",
 *     "open",
 *     "close",
 *     "uid",
 *     "template",
 *     "id",
 *     "uuid",
 *     "title",
 *     "description",
 *     "category",
 *     "elements",
 *     "css",
 *     "javascript",
 *     "settings",
 *     "access",
 *     "handlers",
 *     "third_party_settings",
 *   },
 *   lookup_keys = {
 *     "status",
 *     "template",
 *   },
 * )
 */
class Webform extends ConfigEntityBundleBase implements WebformInterface {

  use StringTranslationTrait;

  /**
   * The webform ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The webform UUID.
   *
   * @var string
   */
  protected $uuid;

  /**
   * The webform override state.
   *
   * When set to TRUE the webform can't not be saved.
   *
   * @var bool
   */
  protected $override = FALSE;

  /**
   * The webform status.
   *
   * @var string
   */
  protected $status = WebformInterface::STATUS_OPEN;

  /**
   * The webform open date/time.
   *
   * @var bool
   */
  protected $open;

  /**
   * The webform close date/time.
   *
   * @var bool
   */
  protected $close;

  /**
   * The webform template indicator.
   *
   * @var bool
   */
  protected $template = FALSE;

  /**
   * The webform title.
   *
   * @var string
   */
  protected $title;

  /**
   * The webform description.
   *
   * @var string
   */
  protected $description;

  /**
   * The webform options category.
   *
   * @var string
   */
  protected $category;

  /**
   * The owner's uid.
   *
   * @var int
   */
  protected $uid;

  /**
   * The webform settings.
   *
   * @var array
   */
  protected $settings = [];

  /**
   * The webform access controls.
   *
   * @var array
   */
  protected $access = [];

  /**
   * The webform elements.
   *
   * @var string
   */
  protected $elements;

  /**
   * The CSS style sheet.
   *
   * @var string
   */
  protected $css = '';

  /**
   * The JavaScript.
   *
   * @var string
   */
  protected $javascript = '';

  /**
   * The array of webform handlers for this webform.
   *
   * @var array
   */
  protected $handlers = [];

  /**
   * Holds the collection of webform handlers that are used by this webform.
   *
   * @var \Drupal\webform\Plugin\WebformHandlerPluginCollection
   */
  protected $handlersCollection;

  /**
   * The webform elements original.
   *
   * @var string
   */
  protected $elementsOriginal;

  /**
   * The webform elements decoded.
   *
   * @var array
   */
  protected $elementsDecoded;

  /**
   * The webform elements initializes (and decoded).
   *
   * @var array
   */
  protected $elementsInitialized;

  /**
   * The webform elements decoded and flattened.
   *
   * @var array
   */
  protected $elementsDecodedAndFlattened;

  /**
   * The webform elements initialized and flattened.
   *
   * @var array
   */
  protected $elementsInitializedAndFlattened;

  /**
   * The webform elements initialized, flattened, and has value.
   *
   * @var array
   */
  protected $elementsInitializedFlattenedAndHasValue;

  /**
   * The webform elements translations.
   *
   * @var array
   */
  protected $elementsTranslations;

  /**
   * Track the elements that are 'webform_actions' (aka submit buttons).
   *
   * @var array
   */
  protected $elementsActions = [];

  /**
   * Track the elements that are 'webform_pages' (aka Wizard pages).
   *
   * @var array
   */
  protected $elementsWizardPages = [];

  /**
   * The webform pages.
   *
   * @var array
   */
  protected $pages;

  /**
   * Track if the webform has a managed file (upload) element.
   *
   * @var bool
   */
  protected $hasManagedFile = FALSE;

  /**
   * Track if the webform is using a flexbox layout.
   *
   * @var bool
   */
  protected $hasFlexboxLayout = FALSE;

  /**
   * Track if the webform has container.
   *
   * @var bool
   */
  protected $hasContainer = FALSE;

  /**
   * Track if the webform has conditions (i.e. #states).
   *
   * @var bool
   */
  protected $hasConditions = FALSE;

  /**
   * Track if the webform has translations.
   *
   * @var bool
   */
  protected $hasTranslations;

  /**
   * {@inheritdoc}
   */
  public function getLangcode() {
    return $this->langcode;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->uid ? User::load($this->uid) : NULL;
  }

  /**
   * Sets the entity owner's user entity.
   *
   * @param \Drupal\user\UserInterface $account
   *   The owner user entity.
   *
   * @return $this
   */
  public function setOwner(UserInterface $account) {
    $this->uid = $account->id();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->uid;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->uid = ($uid) ? $uid : NULL;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOverride($override = TRUE) {
    $this->override = $override;
  }

  /**
   * {@inheritdoc}
   */
  public function isOverridden() {
    return $this->override;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus($status) {
    if ($status === NULL || $status === WebformInterface::STATUS_SCHEDULED) {
      $this->status = WebformInterface::STATUS_SCHEDULED;
    }
    elseif ($status === WebformInterface::STATUS_OPEN) {
      $this->status = WebformInterface::STATUS_OPEN;
    }
    elseif ($status === WebformInterface::STATUS_CLOSED) {
      $this->status = WebformInterface::STATUS_CLOSED;
    }
    else {
      $this->status = ((bool) $status) ? WebformInterface::STATUS_OPEN : WebformInterface::STATUS_CLOSED;
    }

    // Clear open and close if status is not scheduled.
    if ($this->status !== WebformInterface::STATUS_SCHEDULED) {
      $this->open = NULL;
      $this->close = NULL;
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function status() {
    return $this->isOpen();
  }

  /**
   * {@inheritdoc}
   */
  public function isOpen() {
    switch ($this->status) {
      case WebformInterface::STATUS_OPEN:
        return TRUE;

      case WebformInterface::STATUS_CLOSED:
        return FALSE;

      case WebformInterface::STATUS_SCHEDULED:
        $is_opened = TRUE;
        if ($this->open && strtotime($this->open) > time()) {
          $is_opened = FALSE;
        }

        $is_closed = FALSE;
        if ($this->close && strtotime($this->close) < time()) {
          $is_closed = TRUE;
        }

        return ($is_opened && !$is_closed) ? TRUE : FALSE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isClosed() {
    return !$this->isOpen();
  }

  /**
   * {@inheritdoc}
   */
  public function isScheduled() {
    return ($this->status === WebformInterface::STATUS_SCHEDULED);
  }

  /**
   * {@inheritdoc}
   */
  public function isOpening() {
    return ($this->isScheduled() && ($this->open && strtotime($this->open) > time())) ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isTemplate() {
    return $this->template ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isConfidential() {
    return $this->getSetting('form_confidential');
  }

  /**
   * {@inheritdoc}
   */
  public function hasSubmissions() {
    /** @var \Drupal\webform\WebformSubmissionStorageInterface $submission_storage */
    $submission_storage = \Drupal::entityTypeManager()->getStorage('webform_submission');
    return ($submission_storage->getTotal($this)) ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function hasSubmissionLog() {
    return $this->getSetting('submission_log', TRUE) ?: FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function hasTranslations() {
    if (isset($this->hasTranslations)) {
      return $this->hasTranslations;
    }

    // Make sure the config translation module is enabled.
    if (!\Drupal::moduleHandler()->moduleExists('config_translation')) {
      $this->hasTranslations = FALSE;
      return $this->hasTranslations;
    }

    /** @var \Drupal\locale\LocaleConfigManager $local_config_manager */
    $local_config_manager = \Drupal::service('locale.config_manager');
    $languages = \Drupal::languageManager()->getLanguages();
    foreach ($languages as $langcode => $language) {
      if ($local_config_manager->hasTranslation('webform.webform.' . $this->id(), $langcode)) {
        $this->hasTranslations = TRUE;
        return $this->hasTranslations;
      }
    }

    $this->hasTranslations = FALSE;
    return $this->hasTranslations;
  }

  /**
   * {@inheritdoc}
   */
  public function hasPage() {
    $settings = $this->getSettings();
    return $settings['page'] ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function hasManagedFile() {
    $this->initElements();
    return $this->hasManagedFile;
  }

  /**
   * {@inheritdoc}
   */
  public function hasFlexboxLayout() {
    $this->initElements();
    return $this->hasFlexboxLayout;
  }

  /**
   * {@inheritdoc}
   */
  public function hasContainer() {
    $this->initElements();
    return $this->hasContainer;
  }

  /**
   * {@inheritdoc}
   */
  public function hasConditions() {
    $this->initElements();
    return $this->hasConditions;
  }

  /**
   * {@inheritdoc}
   */
  public function hasActions() {
    return $this->getNumberOfActions() ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getNumberOfActions() {
    $this->initElements();
    return count($this->elementsActions);
  }

  /**
   * {@inheritdoc}
   */
  public function hasWizardPages() {
    return $this->getNumberOfWizardPages() ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getNumberOfWizardPages() {
    $this->initElements();
    return count($this->elementsWizardPages);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    $this->description = $description;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAssets() {
    $shared_css = \Drupal::config('webform.settings')->get('assets.css') ?: '';
    $webform_css = $this->css ?: '';

    $shared_javascript = \Drupal::config('webform.settings')->get('assets.javascript') ?: '';
    $webform_javascript = $this->javascript ?: '';

    return [
      'css' => $shared_css . (($shared_css && $webform_css) ? PHP_EOL : '') . $webform_css,
      'javascript' => $shared_javascript . (($shared_javascript && $webform_javascript) ? PHP_EOL : '') . $webform_javascript,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCss() {
    return $this->css;
  }

  /**
   * {@inheritdoc}
   */
  public function setCss($css) {
    $this->css = $css;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getJavaScript() {
    return $this->javascript;
  }

  /**
   * {@inheritdoc}
   */
  public function setJavaScript($javascript) {
    $this->javascript = $javascript;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings() {
    // Settings should not be empty even.
    // https://www.drupal.org/node/2880392.
    return (isset($this->settings)) ? $this->settings +
      self::getDefaultSettings() : self::getDefaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function setSettings(array $settings) {
    // Always apply the default settings.
    $this->settings += static::getDefaultSettings();

    // Now apply new settings.
    foreach ($settings as $name => $value) {
      $this->settings[$name] = $value;
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSetting($key, $default = FALSE) {
    $settings = $this->getSettings();
    $value = (isset($settings[$key])) ? $settings[$key] : NULL;
    if ($default) {
      return $value ?: \Drupal::config('webform.settings')->get('settings.default_' . $key);
    }
    else {
      return $value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setSetting($key, $value) {
    $settings = $this->getSettings();
    $settings[$key] = $value;
    $this->setSettings($settings);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setSettingsOverride(array $settings) {
    $this->setSettings($settings);
    $this->setOverride();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setSettingOverride($key, $value) {
    $this->setSetting($key, $value);
    $this->setOverride();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setPropertyOverride($property_name, $value) {
    $this->set($property_name, $value);
    $this->setOverride();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessRules() {
    return $this->access;
  }

  /**
   * {@inheritdoc}
   */
  public function setAccessRules(array $access) {
    $this->access = $access;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function getDefaultSettings() {
    return [
      'ajax' => FALSE,
      'ajax_scroll_top' => 'form',
      'page' => TRUE,
      'page_submit_path' => '',
      'page_confirm_path' => '',
      'form_submit_once' => FALSE,
      'form_exception_message' => '',
      'form_open_message' => '',
      'form_close_message' => '',
      'form_previous_submissions' => TRUE,
      'form_confidential' => FALSE,
      'form_confidential_message' => '',
      'form_convert_anonymous' => FALSE,
      'form_prepopulate' => FALSE,
      'form_prepopulate_source_entity' => FALSE,
      'form_prepopulate_source_entity_required' => FALSE,
      'form_prepopulate_source_entity_type' => FALSE,
      'form_reset' => FALSE,
      'form_disable_autocomplete' => FALSE,
      'form_novalidate' => FALSE,
      'form_unsaved' => FALSE,
      'form_disable_back' => FALSE,
      'form_autofocus' => FALSE,
      'form_details_toggle' => FALSE,
      'submission_label' => '',
      'submission_log' => FALSE,
      'submission_user_columns' => [],
      'wizard_progress_bar' => TRUE,
      'wizard_progress_pages' => FALSE,
      'wizard_progress_percentage' => FALSE,
      'wizard_start_label' => '',
      'wizard_confirmation' => TRUE,
      'wizard_confirmation_label' => '',
      'wizard_track' => '',
      'preview' => DRUPAL_DISABLED,
      'preview_label' => '',
      'preview_title' => '',
      'preview_message' => '',
      'preview_attributes' => [],
      'preview_excluded_elements' => [],
      'preview_exclude_empty' => TRUE,
      'draft' => self::DRAFT_NONE,
      'draft_multiple' => FALSE,
      'draft_auto_save' => FALSE,
      'draft_saved_message' => '',
      'draft_loaded_message' => '',
      'confirmation_type' => WebformInterface::CONFIRMATION_PAGE,
      'confirmation_title' => '',
      'confirmation_message' => '',
      'confirmation_url' => '',
      'confirmation_attributes' => [],
      'confirmation_back' => TRUE,
      'confirmation_back_label' => '',
      'confirmation_back_attributes' => [],
      'limit_total' => NULL,
      'limit_total_message' => '',
      'limit_user' => NULL,
      'limit_user_message' => '',
      'purge' => WebformSubmissionStorageInterface::PURGE_NONE,
      'purge_days' => NULL,
      'entity_limit_total' => NULL,
      'entity_limit_user' => NULL,
      'results_disabled' => FALSE,
      'results_disabled_ignore' => FALSE,
      'token_update' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function getDefaultAccessRules() {
    return [
      'create' => [
        'roles' => [
          'anonymous',
          'authenticated',
        ],
        'users' => [],
        'permissions' => [],
      ],
      'view_any' => [
        'roles' => [],
        'users' => [],
        'permissions' => [],
      ],
      'update_any' => [
        'roles' => [],
        'users' => [],
        'permissions' => [],
      ],
      'delete_any' => [
        'roles' => [],
        'users' => [],
        'permissions' => [],
      ],
      'purge_any' => [
        'roles' => [],
        'users' => [],
        'permissions' => [],
      ],
      'view_own' => [
        'roles' => [],
        'users' => [],
        'permissions' => [],
      ],
      'update_own' => [
        'roles' => [],
        'users' => [],
        'permissions' => [],
      ],
      'delete_own' => [
        'roles' => [],
        'users' => [],
        'permissions' => [],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function checkAccessRules($operation, AccountInterface $account, WebformSubmissionInterface $webform_submission = NULL) {
    // Always grant access to user that can administer webforms and submissions
    if ($account->hasPermission('administer webform') || $account->hasPermission('administer webform submission')) {
      return TRUE;
    }

    // The "page" operation is the same as "create" but requires that the
    // Webform is allowed to be displayed as dedicated page.
    // Used by the 'entity.webform.canonical' route.
    if ($operation == 'page') {
      if (empty($this->settings['page'])) {
        return FALSE;
      }
      else {
        $operation = 'create';
      }
    }

    $access_rules = $this->getAccessRules() + static::getDefaultAccessRules();

    if (in_array($operation, ['create', 'view_any', 'update_any', 'delete_any', 'purge_any'])
      && $this->checkAccessRule($access_rules[$operation], $account)) {
      return TRUE;
    }

    if (isset($access_rules[$operation . '_any'])
      && $this->checkAccessRule($access_rules[$operation . '_any'], $account)) {
      return TRUE;
    }

    // If webform submission is not set then check 'view own'.
    // @see \Drupal\webform\WebformSubmissionForm::displayMessages.
    if (empty($webform_submission)
      && $operation === 'view_own'
      && $this->checkAccessRule($access_rules[$operation], $account)) {
        return TRUE;
    }

    // If webform submission is set then check the webform submission owner.
    if (!empty($webform_submission)) {
      $is_authenticated_owner = ($account->isAuthenticated() && $account->id() === $webform_submission->getOwnerId());
      $is_anonymous_owner = ($account->isAnonymous() && !empty($_SESSION['webform_submissions']) && isset($_SESSION['webform_submissions'][$webform_submission->id()]));
      $is_owner = ($is_authenticated_owner || $is_anonymous_owner);
      if ($is_owner) {
        if (isset($access_rules[$operation . '_own'])
          && $this->checkAccessRule($access_rules[$operation . '_own'], $account)) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

  /**
   * Checks an access rule against a user account's roles and id.
   *
   * @param array $access_rule
   *   An access rule.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user session for which to check access.
   *
   * @return bool
   *   The access result. Returns a TRUE if access is allowed.
   *
   * @see \Drupal\webform\Plugin\WebformElementBase::checkAccessRule
   */
  protected function checkAccessRule(array $access_rule, AccountInterface $account) {
    if (!empty($access_rule['roles']) && array_intersect($access_rule['roles'], $account->getRoles())) {
      return TRUE;
    }
    elseif (!empty($access_rule['users']) && in_array($account->id(), $access_rule['users'])) {
      return TRUE;
    }
    elseif (!empty($access_rule['permissions'])) {
      foreach ($access_rule['permissions'] as $permission) {
        if ($account->hasPermission($permission)) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSubmissionForm(array $values = [], $operation = 'add') {
    // Set this webform's id which can be used by preCreate hooks.
    $values['webform_id'] = $this->id();

    /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
    $webform_submission = $this->entityTypeManager()
      ->getStorage('webform_submission')
      ->create($values);

    // Pass this webform to the webform submission as a direct entity reference.
    // This guarantees overridden properties and settings are maintained.
    // Not sure why the overridden webform is not being correctly passed to the
    // webform submission.
    // @see \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem::setValue
    if ($this->isOverridden()) {
      $webform_submission->webform_id->entity = $this;
    }

    return \Drupal::service('entity.form_builder')
      ->getForm($webform_submission, $operation);
  }


  /**
   * {@inheritdoc}
   */
  public function getElementsOriginalRaw() {
    return $this->elementsOriginal;
  }

  /**
   * {@inheritdoc}
   */
  public function getElementsOriginalDecoded() {
    $this->elementsOriginal;
    try {
      $elements = Yaml::decode($this->elementsOriginal);
      return (is_array($elements)) ? $elements : [];
    }
    catch (\Exception $exception) {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getElementsRaw() {
    return $this->elements;
  }

  /**
   * {@inheritdoc}
   */
  public function getElementsDecoded() {
    $this->initElements();
    return $this->elementsDecoded;
  }

  /**
   * {@inheritdoc}
   */
  public function getElementsInitialized() {
    $this->initElements();
    return $this->elementsInitialized;
  }

  /**
   * {@inheritdoc}
   */
  public function getElementsInitializedAndFlattened($operation = NULL) {
    $this->initElements();
    return $this->checkElementsFlattenedAccess($operation, $this->elementsInitializedAndFlattened);
  }

  /**
   * {@inheritdoc}
   */
  public function getElementsDecodedAndFlattened($operation = NULL) {
    $this->initElements();
    return $this->checkElementsFlattenedAccess($operation, $this->elementsDecodedAndFlattened);
  }

  /**
   * {@inheritdoc}
   */
  public function getElementsInitializedFlattenedAndHasValue($operation = NULL) {
    $this->initElements();
    return $this->checkElementsFlattenedAccess($operation, $this->elementsInitializedFlattenedAndHasValue);
  }

  /**
   * Check operation access for each element.
   *
   * @param string $operation
   *   (optional) The operation that is to be performed on the element.
   * @param array $elements
   *   An associative array of flattened form elements.
   *
   * @return array
   *   An associative array of flattened form elements with each element's
   *   operation access checked.
   */
  protected function checkElementsFlattenedAccess($operation = NULL, array $elements) {
    if ($operation === NULL) {
      return $elements;
    }

    /** @var \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager */
    $element_manager = \Drupal::service('plugin.manager.webform.element');
    foreach ($elements as $key => $element) {
      $element_plugin = $element_manager->getElementInstance($element);
      if (!$element_plugin->checkAccessRules($operation, $element)) {
        unset($elements[$key]);
      }
    }
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function getElementsSelectorOptions() {
    /** @var \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager */
    $element_manager = \Drupal::service('plugin.manager.webform.element');

    $selectors = [];
    $elements = $this->getElementsInitializedAndFlattened();
    foreach ($elements as $element) {
      $element_plugin = $element_manager->getElementInstance($element);
      $selectors += $element_plugin->getElementSelectorOptions($element);
    }
    return $selectors;
  }

  /**
   * {@inheritdoc}
   */
  public function setElements(array $elements) {
    $this->elements = Yaml::encode($elements);
    $this->resetElements();
    return $this;
  }

  /**
   * Initialize parse webform elements.
   */
  protected function initElements() {
    if (isset($this->elementsInitialized)) {
      return;
    }

    $this->hasManagedFile = FALSE;
    $this->hasFlexboxLayout = FALSE;
    $this->hasContainer = FALSE;
    $this->hasConditions = FALSE;
    $this->elementsActions = [];
    $this->elementsWizardPages = [];
    $this->elementsDecodedAndFlattened = [];
    $this->elementsInitializedAndFlattened = [];
    $this->elementsInitializedFlattenedAndHasValue = [];
    $this->elementsTranslations = [];
    try {
      $config_translation = \Drupal::moduleHandler()->moduleExists('config_translation');
      /** @var \Drupal\webform\WebformTranslationManagerInterface $translation_manager */
      $translation_manager = \Drupal::service('webform.translation_manager');
      /** @var \Drupal\Core\Language\LanguageManagerInterface $language_manager */
      $language_manager = \Drupal::service('language_manager');

      // If current webform is translated, load the base (default) webform and apply
      // the translation to the elements.
      if ($config_translation && $this->langcode != $language_manager->getCurrentLanguage()->getId()) {
        $elements = $translation_manager->getElements($this);
        $this->elementsTranslations = Yaml::decode($this->elements);
      }
      else {
        $elements = Yaml::decode($this->elements);
      }

      // Since YAML supports simple values.
      $elements = (is_array($elements)) ? $elements : [];
      $this->elementsDecoded = $elements;
    }
    catch (\Exception $exception) {
      $link = $this->link($this->t('Edit'), 'edit-form');
      \Drupal::logger('webform')
        ->notice('%title elements are not valid. @message', [
          '%title' => $this->label(),
          '@message' => $exception->getMessage(),
          'link' => $link,
        ]);
      $elements = FALSE;
    }

    if ($elements !== FALSE) {
      $this->initElementsRecursive($elements);
      $this->invokeHandlers('alterElements', $elements, $this);
    }

    $this->elementsInitialized = $elements;
  }

  /**
   * Reset parsed and cached webform elements.
   */
  protected function resetElements() {
    $this->pages = NULL;
    $this->hasManagedFile = NULL;
    $this->hasFlexboxLayout = NULL;
    $this->hasContainer = NULL;
    $this->hasConditions = NULL;
    $this->elementsActions = [];
    $this->elementsWizardPages = [];
    $this->elementsDecoded = NULL;
    $this->elementsInitialized = NULL;
    $this->elementsDecodedAndFlattened = NULL;
    $this->elementsInitializedAndFlattened = NULL;
    $this->elementsInitializedFlattenedAndHasValue = NULL;
    $this->elementsTranslations = NULL;
  }

  /**
   * Initialize webform elements into a flatten array.
   *
   * @param array $elements
   *   The webform elements.
   * @param string $parent
   *   The parent key.
   * @param int $depth
   *   The element's depth.
   */
  protected function initElementsRecursive(array &$elements, $parent = '', $depth = 0) {
    /** @var \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager */
    $element_manager = \Drupal::service('plugin.manager.webform.element');

    // Remove ignored properties.
    $elements = WebformElementHelper::removeIgnoredProperties($elements);

    foreach ($elements as $key => &$element) {
      if (Element::property($key) || !is_array($element)) {
        continue;
      }

      // Apply translation to element.
      if (isset($this->elementsTranslations[$key])) {
        WebformElementHelper::applyTranslation($element, $this->elementsTranslations[$key]);
      }

      // Copy only the element properties to decoded and flattened elements.
      $this->elementsDecodedAndFlattened[$key] = WebformElementHelper::getProperties($element);

      // Set id, key, parent_key, depth, and parent children.
      $element['#webform_id'] = $this->id() . '--' . $key;
      $element['#webform_key'] = $key;
      $element['#webform_parent_key'] = $parent;
      $element['#webform_parent_flexbox'] = FALSE;
      $element['#webform_depth'] = $depth;
      $element['#webform_children'] = [];
      $element['#webform_multiple'] = FALSE;
      $element['#webform_composite'] = FALSE;

      if (!empty($parent)) {
        $parent_element = $this->elementsInitializedAndFlattened[$parent];
        // Add element to the parent element's children.
        $parent_element['#webform_children'][$key] = $key;
        // Set #parent_flexbox to TRUE is the parent element is a
        // 'webform_flexbox'.
        $element['#webform_parent_flexbox'] = (isset($parent_element['#type']) && $parent_element['#type'] == 'webform_flexbox') ? TRUE : FALSE;

        $element['#webform_parents'] = $parent_element['#webform_parents'];
      }

      // Add element key to parents.
      // #webform_parents allows make it possible to use NestedArray::getValue
      // to get the entire unflattened element.
      $element['#webform_parents'][] = $key;

      // Set #title and #admin_title to NULL if it is not defined.
      $element += [
        '#title' => NULL,
        '#admin_title' => NULL,
      ];

      // If #private set #access.
      if (!empty($element['#private'])) {
        $element['#access'] = $this->access('submission_view_any');
      }

      // Set #markup type to 'webform_markup' to trigger #display_on behavior.
      // @see https://www.drupal.org/node/2036237
      if (empty($element['#type']) && empty($element['#theme']) && isset($element['#markup'])) {
        $element['#type'] = 'webform_markup';
      }

      $element_plugin = NULL;
      if (isset($element['#type'])) {
        // Load the element's handler.
        $element_plugin = $element_manager->getElementInstance($element);

        // Initialize the element.
        // Note: Composite sub elements are initialized via
        // \Drupal\webform\Plugin\WebformElement\WebformCompositeBase::initialize
        // and stored in the '#webform_composite_elements' property.
        $element_plugin->initialize($element);

        // Track managed file upload.
        if ($element_plugin instanceof WebformManagedFileBase) {
          $this->hasManagedFile = TRUE;
        }

        // Track flexbox.
        if ($element['#type'] == 'flexbox' || $element['#type'] == 'webform_flexbox') {
          $this->hasFlexboxLayout = TRUE;
        }

        // Track container.
        if ($element_plugin->isContainer($element)) {
          $this->hasContainer = TRUE;
        }

        // Track conditional.
        if (!empty($element['#states'])) {
          $this->hasConditions = TRUE;
        }

        // Track actions.
        if ($element_plugin instanceof WebformActions) {
          $this->elementsActions[$key] = $key;
        }

        // Track wizard.
        if ($element_plugin instanceof WebformWizardPage) {
          $this->elementsWizardPages[$key] = $key;
        }

        $element['#webform_multiple'] = $element_plugin->hasMultipleValues($element);
        $element['#webform_composite'] = $element_plugin->isComposite();
      }

      // Copy only the element properties to initialized and flattened elements.
      $this->elementsInitializedAndFlattened[$key] = WebformElementHelper::getProperties($element);

      // Check if element has value (aka can be exported) and add it to
      // flattened has value array.
      if ($element_plugin && $element_plugin->isInput($element)) {
        $this->elementsInitializedFlattenedAndHasValue[$key] = &$this->elementsInitializedAndFlattened[$key];
      }

      $this->initElementsRecursive($element, $key, $depth + 1);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getElement($key, $include_children = FALSE) {
    $elements_flattened = $this->getElementsInitializedAndFlattened();
    $element = (isset($elements_flattened[$key])) ? $elements_flattened[$key] : NULL;
    if ($element && $include_children) {
      $elements = $this->getElementsInitialized();
      return NestedArray::getValue($elements, $element['#webform_parents']);
    }
    else {
      return $element;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getElementDecoded($key) {
    $elements = $this->getElementsDecodedAndFlattened();
    return (isset($elements[$key])) ? $elements[$key] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getElementInitialized($key) {
    $elements = $this->getElementsInitializedAndFlattened();
    return (isset($elements[$key])) ? $elements[$key] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setElementProperties($key, array $properties, $parent_key = '') {
    $elements = $this->getElementsDecoded();
    // If element is was not added to elements, add it as the last element.
    if (!$this->setElementPropertiesRecursive($elements, $key, $properties, $parent_key)) {
      if ($this->hasActions()) {
        // Add element before the last 'webform_actions' element.
        $last_action_key = end($this->elementsActions);
        $updated_elements = [];
        foreach ($elements as $element_key => $element) {
          if ($element_key == $last_action_key) {
            $updated_elements[$key] = $properties;
          }
          $updated_elements[$element_key] = $element;
        }
        $elements = $updated_elements;
      }
      else {
        $elements[$key] = $properties;
      }
    }
    $this->setElements($elements);
    return $this;
  }

  /**
   * Set element properties.
   *
   * @param array $elements
   *   An associative nested array of elements.
   * @param string $key
   *   The element's key.
   * @param array $properties
   *   An associative array of properties.
   * @param string $parent_key
   *   (optional) The element's parent key. Only used for new elements.
   *
   * @return bool
   *   TRUE when the element's properties has been set. FALSE when the element
   *   has not been found.
   */
  protected function setElementPropertiesRecursive(array &$elements, $key, array $properties, $parent_key = '') {
    foreach ($elements as $element_key => &$element) {
      if (Element::property($element_key) || !is_array($element)) {
        continue;
      }

      if ($element_key == $key) {
        $element = $properties + WebformElementHelper::removeProperties($element);
        return TRUE;
      }

      if ($element_key == $parent_key) {
        $element[$key] = $properties;
        return TRUE;
      }

      if ($this->setElementPropertiesRecursive($element, $key, $properties, $parent_key)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteElement($key) {
    // Delete element from the elements render array.
    $elements = $this->getElementsDecoded();
    $sub_element_keys = $this->deleteElementRecursive($elements, $key);
    $this->setElements($elements);

    // Delete submission element key data.
    \Drupal::database()->delete('webform_submission_data')
      ->condition('webform_id', $this->id())
      ->condition('name', $sub_element_keys, 'IN')
      ->execute();
  }

  /**
   * Remove an element by key from a render array.
   *
   * @param array $elements
   *   An associative nested array of elements.
   * @param string $key
   *   The element's key.
   *
   * @return bool|array
   *   An array containing the deleted element and sub element keys.
   *   FALSE is no sub elements are found.
   */
  protected function deleteElementRecursive(array &$elements, $key) {
    foreach ($elements as $element_key => &$element) {
      if (Element::property($element_key) || !is_array($element)) {
        continue;
      }

      if ($element_key == $key) {
        $sub_element_keys = [$element_key => $element_key];
        $this->collectSubElementKeysRecursive($sub_element_keys, $element);
        unset($elements[$element_key]);
        return $sub_element_keys;
      }

      if ($sub_element_keys = $this->deleteElementRecursive($element, $key)) {
        return $sub_element_keys;
      }
    }

    return FALSE;
  }

  /**
   * Collect sub element keys from a render array.
   *
   * @param array $sub_element_keys
   *   An array to be populated with sub element keys.
   * @param array $elements
   *   A render array.
   */
  protected function collectSubElementKeysRecursive(array &$sub_element_keys, array $elements) {
    foreach ($elements as $key => &$element) {
      if (Element::property($key) || !is_array($element)) {
        continue;
      }
      $sub_element_keys[$key] = $key;
      $this->collectSubElementKeysRecursive($sub_element_keys, $element);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getPages($operation = 'default') {
    if (isset($this->pages[$operation])) {
      return $this->pages[$operation];
    }

    /** @var \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager */
    $element_manager = \Drupal::service('plugin.manager.webform.element');
    /** @var \Drupal\webform\Plugin\WebformElementInterface $element_plugin */
    $element_plugin = $element_manager->createInstance('webform_wizard_page');

    $wizard_properties = [
      '#title' => '#title',
      '#prev_button_label' => '#prev_button_label',
      '#next_button_label' => '#next_button_label',
      '#states' => '#states',
    ];

    $pages = [];

    // Add webform wizard pages
    $elements = $this->getElementsInitialized();
    if (is_array($elements) && !in_array($operation, ['edit_all', 'api'])) {
      foreach ($elements as $key => $element) {
        if (!isset($element['#type']) || $element['#type'] != 'webform_wizard_page') {
          continue;
        }

        // Check element access rules and only include pages that are visible
        // to the current user.
        $access_operation = (in_array($operation, ['default', 'add'])) ? 'create' : 'update';
        if ($element_plugin->checkAccessRules($access_operation, $element)) {
          $pages[$key] = array_intersect_key($element, $wizard_properties);
          $pages[$key]['#access'] = TRUE;
        }
      }
    }

    // Add preview page.
    $settings = $this->getSettings();
    if ($settings['preview'] != DRUPAL_DISABLED) {
      // If there is no start page, we must define one.
      if (empty($pages)) {
        $pages['webform_start'] = [
          '#title' => $this->getSetting('wizard_start_label', TRUE),
          '#access' => TRUE,
        ];
      }
      $pages['webform_preview'] = [
        '#title' => $this->getSetting('preview_label', TRUE),
        '#access' => TRUE,
      ];
    }

    // Only add complete page, if there are some pages.
    if ($pages && $this->getSetting('wizard_confirmation')) {
      $pages['webform_confirmation'] = [
        '#title' => $this->getSetting('wizard_confirmation_label', TRUE),
        '#access' => TRUE,
      ];
    }

    $this->pages[$operation] = $pages;

    return $this->pages[$operation];
  }

  /**
   * {@inheritdoc}
   */
  public function getPage($operation, $key) {
    $pages = $this->getPages($operation);
    return (isset($pages[$key])) ? $pages[$key] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function createDuplicate() {
    /** @var \Drupal\webform\WebformInterface $duplicate */
    $duplicate = parent::createDuplicate();

    // Clear path aliases, which must be unique.
    $duplicate->setSetting('page_submit_path', '');
    $duplicate->setSetting('page_confirm_path', '');

    // Update owner to current user.
    $duplicate->setOwnerId(\Drupal::currentUser()->id());
    
    // If template, clear the description and remove the template flag.
    if ($duplicate->isTemplate()) {
      $duplicate->set('description', '');
      $duplicate->set('template', FALSE);
    }

    // Remove enforce module dependency when a sub-module's webform is
    // duplicated.
    if (isset($duplicate->dependencies['enforced']['module'])) {
      $modules = WebformReflectionHelper::getSubModules();
      $duplicate->dependencies['enforced']['module'] = array_diff($duplicate->dependencies['enforced']['module'], $modules);
      if (empty($duplicate->dependencies['enforced']['module'])) {
        unset($duplicate->dependencies['enforced']['module']);
        if (empty($duplicate->dependencies['enforced'])) {
          unset($duplicate->dependencies['enforced']);
        }
      }
    }

    return $duplicate;
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage, array &$values) {
    $values += [
      'status' => WebformInterface::STATUS_OPEN,
      'uid' => \Drupal::currentUser()->id(),
      'settings' => static::getDefaultSettings(),
      'access' => static::getDefaultAccessRules(),
    ];

    // Convert boolean status to STATUS constant.
    if ($values['status'] === TRUE) {
      $values['status'] = WebformInterface::STATUS_OPEN;
    }
    elseif ($values['status'] === FALSE) {
      $values['status'] = WebformInterface::STATUS_CLOSED;
    }
    elseif ($values['status'] === NULL) {
      $values['status'] = WebformInterface::STATUS_SCHEDULED;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postLoad(EntityStorageInterface $storage, array &$entities) {
    foreach ($entities as $entity) {
      $entity->elementsOriginal = $entity->elements;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    /** @var \Drupal\webform\WebformInterface[] $entities */
    parent::preDelete($storage, $entities);

    // Delete all paths and states associated with this webform.
    foreach ($entities as $entity) {
      // Delete all paths.
      $entity->deletePaths();

      // Delete the state.
      \Drupal::state()->delete('webform.webform.' . $entity->id());
    }

    // Delete all submission associated with this webform.
    $submission_ids = \Drupal::entityQuery('webform_submission')
      ->condition('webform_id', array_keys($entities), 'IN')
      ->sort('sid')
      ->execute();
    $submission_storage = \Drupal::entityTypeManager()->getStorage('webform_submission');
    $submissions = $submission_storage->loadMultiple($submission_ids);
    $submission_storage->delete($submissions);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $cache_tags = parent::getCacheTags();
    // Add webform to cache tags which are used by the WebformSubmissionForm.
    $cache_tags[] = 'webform:' . $this->id();
    // Add webform settings to cache tags which are used to define
    // default settings.
    $cache_tags[] = 'config:webform.settings';
    return $cache_tags;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $cache_contexts = parent::getCacheContexts();

    // Add paths to cache contexts since webform can be placed on multiple
    // pages.
    $cache_contexts[] = 'url.path';

    // Add all prepopulate query string parameters.
    if ($this->getSetting('form_prepopulate')) {
      $cache_contexts[] = 'url.query_args';
    }
    else {
      // Add source entity type and id query string parameters.
      if ($this->getSetting('form_prepopulate_source_entity')) {
        $cache_contexts[] = 'url.query_args:entity_type';
        $cache_contexts[] = 'url.query_args:entity_id';
      }
      // Add webform (secure) token query string parameter.
      if ($this->getSetting('token_update')) {
        $cache_contexts[] = 'url.query_args:token';
      }
    }

    return $cache_contexts;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    // Throw exception when saving overridden webform.
    if ($this->isOverridden()) {
      throw new WebformException(sprintf('The %s webform [%s] has overridden settings and/or properties and can not be saved.', $this->label(), $this->id()));
    }

    // Always unpublish templates.
    if ($this->isTemplate()) {
      $this->setStatus(WebformInterface::STATUS_CLOSED);
    }

    // Serialize elements array to YAML.
    if (is_array($this->elements)) {
      $this->elements = Yaml::encode($this->elements);
    }

    parent::preSave($storage);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // Update paths.
    $this->updatePaths();

    // Invoke handler element CRUD methods.
    // Note: Comparing parsed YAML since the actual YAML formatting could be
    // different.
    $elements_original = $this->getElementsOriginalDecoded() ?: [];
    $elements = $this->getElementsDecoded() ?: [];
    if ($elements_original != $elements) {
      $elements_original = WebformElementHelper::getFlattened($elements_original);
      $elements = WebformElementHelper::getFlattened($elements);

      // Handle create element.
      if ($created_elements = array_diff_key($elements, $elements_original)) {
        foreach ($created_elements as $element_key => $element) {
          $this->invokeHandlers('createElement', $element_key, $element);
        }
      }

      // Handle delete element.
      if ($deleted_elements = array_diff_key($elements_original, $elements)) {
        foreach ($deleted_elements as $element_key => $element) {
          $this->invokeHandlers('deleteElement', $element_key, $element);
        }
      }

      // Handle update element.
      foreach ($elements as $element_key => $element) {
        if (isset($elements_original[$element_key]) && $elements_original[$element_key] != $element) {
          $this->invokeHandlers('updateElement', $element_key, $element, $elements_original[$element_key]);
        }
      }
    }

    // Reset elements.
    $this->resetElements();
    $this->elementsOriginal = $this->elements;
  }

  /**
   * {@inheritdoc}
   */
  public function updatePaths() {
    // Path module must be enable for URL aliases to be updated.
    if (!\Drupal::moduleHandler()->moduleExists('path')) {
      return;
    }

    // If 'Allow users to post submission from a dedicated URL' is disabled,
    // delete all existing paths.
    if (empty($this->settings['page'])) {
      $this->deletePaths();
      return;
    }

    $submit_base_path = $this->settings['page_submit_path'] ?: trim(\Drupal::config('webform.settings')->get('settings.default_page_base_path'), '/') . '/' . str_replace('_', '-', $this->id());
    $submit_base_path = '/' . trim($submit_base_path, '/');

    // Update submit path.
    $submit_suffixes = [
      '',
      '/submissions',
      '/drafts',
    ];
    foreach ($submit_suffixes as $submit_suffix) {
      $submit_source = '/webform/' . $this->id() . $submit_suffix;
      $submit_alias = $submit_base_path . $submit_suffix;
      $this->updatePath($submit_source, $submit_alias, $this->langcode);
      $this->updatePath($submit_source, $submit_alias, LanguageInterface::LANGCODE_NOT_SPECIFIED);
    }

    // Update confirm path.
    $confirm_source = '/webform/' . $this->id() . '/confirmation';
    $confirm_alias = $this->settings['page_confirm_path'] ?:  $submit_base_path . '/confirmation';
    $confirm_alias = '/' . trim($confirm_alias, '/');
    $this->updatePath($confirm_source, $confirm_alias, $this->langcode);
    $this->updatePath($confirm_source, $confirm_alias, LanguageInterface::LANGCODE_NOT_SPECIFIED);
  }

  /**
   * {@inheritdoc}
   */
  public function deletePaths() {
    /** @var \Drupal\Core\Path\AliasStorageInterface $path_alias_storage */
    $path_alias_storage = \Drupal::service('path.alias_storage');
    $path_alias_storage->delete(['source' => '/webform/' . $this->id()]);
    $path_alias_storage->delete(['source' => '/webform/' . $this->id() . '/confirmation']);
  }

  /**
   * Saves a path alias to the database.
   *
   * @param string $source
   *   The internal system path.
   * @param string $alias
   *   The URL alias.
   * @param string $langcode
   *   (optional) The language code of the alias.
   */
  protected function updatePath($source, $alias, $langcode = LanguageInterface::LANGCODE_NOT_SPECIFIED) {
    /** @var \Drupal\Core\Path\AliasStorageInterface $path_alias_storage */
    $path_alias_storage = \Drupal::service('path.alias_storage');

    $path = $path_alias_storage->load(['source' => $source, 'langcode' => $langcode]);

    // Check if the path alias is already setup.
    if ($path && ($path['alias'] == $alias)) {
      return;
    }

    $path_alias_storage->save($source, $alias, $langcode, $path['pid']);
  }

  /**
   * Returns the webform handler plugin manager.
   *
   * @return \Drupal\Component\Plugin\PluginManagerInterface
   *   The webform handler plugin manager.
   */
  protected function getWebformHandlerPluginManager() {
    return \Drupal::service('plugin.manager.webform.handler');
  }

  /**
   * {@inheritdoc}
   */
  public function getHandler($handler_id) {
    return $this->getHandlers()->get($handler_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getHandlers($plugin_id = NULL, $status = NULL, $results = NULL, $submission = NULL) {
    if (!$this->handlersCollection) {
      $this->handlersCollection = new WebformHandlerPluginCollection($this->getWebformHandlerPluginManager(), $this->handlers);
      /** @var \Drupal\webform\Plugin\WebformHandlerBase $handler */
      foreach ($this->handlersCollection as $handler) {
        // Initialize the handler and pass in the webform.
        $handler->setWebform($this);
      }
      $this->handlersCollection->sort();
    }

    /** @var \Drupal\webform\Plugin\WebformHandlerPluginCollection $handlers */
    $handlers = $this->handlersCollection;

    // Clone the handlers if they are being filtered.
    if (isset($plugin_id) || isset($status) || isset($results)) {
      /** @var \Drupal\webform\Plugin\WebformHandlerPluginCollection $handlers */
      $handlers = clone $this->handlersCollection;
    }

    // Filter the handlers by plugin id.
    // This is used to limit track and enforce a handlers cardinality.
    if (isset($plugin_id)) {
      foreach ($handlers as $instance_id => $handler) {
        if ($handler->getPluginId() != $plugin_id) {
          $handlers->removeInstanceId($instance_id);
        }
      }
    }

    // Filter the handlers by status.
    // This is used to limit track and enforce a handlers cardinality.
    if (isset($status)) {
      foreach ($handlers as $instance_id => $handler) {
        if ($handler->getStatus() != $status) {
          $handlers->removeInstanceId($instance_id);
        }
      }
    }

    // Filter the handlers by results.
    // This is used to track is results are processed or ignored.
    if (isset($results)) {
      foreach ($handlers as $instance_id => $handler) {
        $plugin_definition = $handler->getPluginDefinition();
        if ($plugin_definition['results'] != $results) {
          $handlers->removeInstanceId($instance_id);
        }
      }
    }

    // Filter the handlers by submission.
    // This is used to track is submissions must be saved to the database.
    if (isset($submission)) {
      foreach ($handlers as $instance_id => $handler) {
        $plugin_definition = $handler->getPluginDefinition();
        if ($plugin_definition['submission'] != $submission) {
          $handlers->removeInstanceId($instance_id);
        }
      }
    }

    return $handlers;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return ['handlers' => $this->getHandlers()];
  }

  /**
   * {@inheritdoc}
   */
  public function addWebformHandler(WebformHandlerInterface $handler) {
    $handler->setWebform($this);
    $handler_id = $handler->getHandlerId();
    $configuration = $handler->getConfiguration();
    $this->getHandlers()->addInstanceId($handler_id, $configuration);
    $this->save();
    $handler->createHandler();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function updateWebformHandler(WebformHandlerInterface $handler) {
    $handler->setWebform($this);
    $handler_id = $handler->getHandlerId();
    $configuration = $handler->getConfiguration();
    $this->getHandlers()->setInstanceConfiguration($handler_id, $configuration);
    $this->save();
    $handler->updateHandler();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteWebformHandler(WebformHandlerInterface $handler) {
    $handler->setWebform($this);
    $this->getHandlers()->removeInstanceId($handler->getHandlerId());
    $handler->deleteHandler();
    $this->save();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function invokeHandlers($method, &$data, &$context1 = NULL, &$context2 = NULL) {
    $handlers = $this->getHandlers();

    // Get webform submission from arguments for conditions validations.
    $webform_submission = NULL;
    $args = func_get_args();
    foreach ($args as $arg) {
      if ($arg instanceof WebformSubmissionInterface) {
        $webform_submission = $arg;
        break;
      }
    }

    foreach ($handlers as $handler) {
      // If the handler is disabled never invoke it.
      if ($handler->isDisabled()) {
        continue;
      }

      // If the arguments contain the webform submission check conditions.
      if ($webform_submission && !$handler->checkConditions($webform_submission)) {
        continue;
      }

      $handler->$method($data, $context1, $context2);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function invokeElements($method, &$data, &$context1 = NULL, &$context2 = NULL) {
    /** @var \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager */
    $element_manager = \Drupal::service('plugin.manager.webform.element');

    $elements = $this->getElementsInitializedAndFlattened();
    foreach ($elements as $element) {
      $element_manager->invokeMethod($method, $element, $data, $context1, $context2);
    }
  }

  /**
   * {@inheritdoc}
   *
   * Overriding so that URLs pointing to webform default to 'canonical'
   * submission webform and not the back-end 'edit-form'.
   */
  public function url($rel = 'canonical', $options = []) {
    // Do not remove this override: the default value of $rel is different.
    return parent::url($rel, $options);
  }

  /**
   * {@inheritdoc}
   *
   * Overriding so that URLs pointing to webform default to 'canonical'
   * submission webform and not the back-end 'edit-form'.
   */
  public function toUrl($rel = 'canonical', array $options = []) {
    return parent::toUrl($rel, $options);
  }

  /**
   * {@inheritdoc}
   *
   * Overriding so that URLs pointing to webform default to 'canonical'
   * submission webform and not the back-end 'edit-form'.
   */
  public function urlInfo($rel = 'canonical', array $options = []) {
    return parent::urlInfo($rel, $options);
  }

  /**
   * {@inheritdoc}
   *
   * Overriding so that links to webform default to 'canonical' submission
   * webform and not the back-end 'edit-form'.
   */
  public function toLink($text = NULL, $rel = 'canonical', array $options = []) {
    return parent::toLink($text, $rel, $options);
  }

  /**
   * {@inheritdoc}
   *
   * Overriding so that links to webform default to 'canonical' submission
   * webform and not the back-end 'edit-form'.
   */
  public function link($text = NULL, $rel = 'canonical', array $options = []) {
    return parent::link($text, $rel, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function isDefaultRevision() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getState($key, $default = NULL) {
    $namespace = 'webform.webform.' . $this->id();
    $values = \Drupal::state()->get($namespace, []);
    return (isset($values[$key])) ? $values[$key] : $default;
  }

  /**
   * {@inheritdoc}
   */
  public function setState($key, $value) {
    $namespace = 'webform.webform.' . $this->id();
    $values = \Drupal::state()->get($namespace, []);
    $values[$key] = $value;
    \Drupal::state()->set($namespace, $values);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteState($key) {
    $namespace = 'webform.webform.' . $this->id();
    $values = \Drupal::state()->get($namespace, []);
    unset($values[$key]);
    \Drupal::state()->set($namespace, $values);
  }

  /**
   * {@inheritdoc}
   */
  public function hasState($key) {
    $namespace = 'webform.webform.' . $this->id();
    $values = \Drupal::state()->get($namespace, []);
    return (isset($values[$key])) ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    $changed = parent::onDependencyRemoval($dependencies);

    $handlers = $this->getHandlers();
    if (empty($handlers)) {
      return $changed;
    }

    foreach ($handlers as $handler) {
      $plugin_definition = $handler->getPluginDefinition();
      $provider = $plugin_definition['provider'];
      if (in_array($provider, $dependencies['module'])) {
        $this->deleteWebformHandler($handler);
        $changed = TRUE;
      }
    }
    return $changed;
  }

  /**
   * {@inheritdoc}
   */
  public static function sort(ConfigEntityInterface $a, ConfigEntityInterface $b) {
    $a_label = $a->get('category') . $a->label();
    $b_label = $b->get('category') . $b->label();
    return strnatcasecmp($a_label, $b_label);
  }

  /**
   * Define empty array iterator.
   *
   * See: Issue #2759267: Undefined method Webform::getIterator().
   */
  public function getIterator() {
    return new \ArrayIterator([]);
  }

}
