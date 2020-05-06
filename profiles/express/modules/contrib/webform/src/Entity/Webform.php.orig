<?php

namespace Drupal\webform\Entity;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Drupal\webform\Plugin\WebformElement\WebformActions;
use Drupal\webform\Plugin\WebformElement\WebformWizardPage;
use Drupal\webform\Plugin\WebformElementAssetInterface;
use Drupal\webform\Plugin\WebformElementAttachmentInterface;
use Drupal\webform\Plugin\WebformElementComputedInterface;
use Drupal\webform\Plugin\WebformElementVariantInterface;
use Drupal\webform\Plugin\WebformElementWizardPageInterface;
use Drupal\webform\Plugin\WebformHandlerMessageInterface;
use Drupal\webform\Plugin\WebformVariantInterface;
use Drupal\webform\Plugin\WebformVariantPluginCollection;
use Drupal\webform\Utility\WebformElementHelper;
use Drupal\webform\Utility\WebformReflectionHelper;
use Drupal\webform\Plugin\WebformHandlerInterface;
use Drupal\webform\Plugin\WebformHandlerPluginCollection;
use Drupal\webform\Utility\WebformTextHelper;
use Drupal\webform\Utility\WebformYaml;
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
 *   label_collection = @Translation("Webforms"),
 *   label_singular = @Translation("webform"),
 *   label_plural = @Translation("webforms"),
 *   label_count = @PluralTranslation(
 *     singular = "@count webform",
 *     plural = "@count webforms",
 *   ),
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
 *       "export" = "Drupal\webform\WebformEntityExportForm",
 *       "settings" = "Drupal\webform\EntitySettings\WebformEntitySettingsGeneralForm",
 *       "settings_form" = "Drupal\webform\EntitySettings\WebformEntitySettingsFormForm",
 *       "settings_submissions" = "Drupal\webform\EntitySettings\WebformEntitySettingsSubmissionsForm",
 *       "settings_confirmation" = "Drupal\webform\EntitySettings\WebformEntitySettingsConfirmationForm",
 *       "settings_assets" = "Drupal\webform\EntitySettings\WebformEntitySettingsAssetsForm",
 *       "settings_access" = "Drupal\webform\EntitySettings\WebformEntitySettingsAccessForm",
 *       "handlers" = "Drupal\webform\WebformEntityHandlersForm",
 *       "variants" = "Drupal\webform\WebformEntityVariantsForm",
 *     }
 *   },
 *   admin_permission = "administer webform",
 *   bundle_of = "webform_submission",
 *   static_cache = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *   },
 *   links = {
 *     "canonical" = "/webform/{webform}",
 *     "access-denied" = "/webform/{webform}/access-denied",
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
 *     "variants" = "/admin/structure/webform/manage/{webform}/variants",
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
 *     "weight",
 *     "uid",
 *     "template",
 *     "archive",
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
 *     "variants",
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
   * The webform's current operation.
   *
   * @var string
   */
  protected $operation;

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
   * The webform weight.
   *
   * @var int
   */
  protected $weight = 0;

  /**
   * The webform template indicator.
   *
   * @var bool
   */
  protected $template = FALSE;

  /**
   * The webform archive indicator.
   *
   * @var bool
   */
  protected $archive = FALSE;

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
   * The webform settings original.
   *
   * @var string
   */
  protected $settingsOriginal;

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
   * The array of webform variants for this webform.
   *
   * @var array
   */
  protected $variants = [];

  /**
   * Holds the collection of webform variants that are used by this webform.
   *
   * @var \Drupal\webform\Plugin\WebformVariantsPluginCollection
   */
  protected $variantsCollection;

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
   * Track the elements that are prepopulated.
   *
   * @var array
   */
  protected $elementsPrepopulate = [];

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
   * Track managed file elements.
   *
   * @var array
   */
  protected $elementsManagedFiles = [];

  /**
   * Track attachment elements.
   *
   * @var array
   */
  protected $elementsAttachments = [];

  /**
   * Track computed elements.
   *
   * @var array
   */
  protected $elementsComputed = [];

  /**
   * Track variant elements.
   *
   * @var array
   */
  protected $elementsVariant = [];

  /**
   * Track elements CSS.
   *
   * @var array
   */
  protected $elementsCss = [];

  /**
   * Track elements JavaScript.
   *
   * @var array
   */
  protected $elementsJavaScript = [];

  /**
   * A webform's default data extracted from each element's default value or value.
   *
   * @var array
   */
  protected $elementsDefaultData = [];

  /**
   * The webform pages.
   *
   * @var array
   */
  protected $pages;

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
   * Track if the webform has required elements.
   *
   * @var bool
   */
  protected $hasRequired = FALSE;

  /**
   * Track if the webform has translations.
   *
   * @var bool
   */
  protected $hasTranslations;

  /**
   * Track if the webform has message handler.
   *
   * @var bool
   */
  protected $hasMessageHandler;

  /**
   * Track if a webform handler requires anonymous submission tracking .
   *
   * @var bool
   */
  protected $hasAnonymousSubmissionTrackingHandler;

  /**
   * {@inheritdoc}
   */
  public function getLangcode() {
    return $this->langcode;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->weight;
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
  public function getOperation() {
    return $this->operation;

  }

  /**
   * {@inheritdoc}
   */
  public function setOperation($operation) {
    $this->operation = $operation;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isTest() {
    return ($this->operation === 'test') ? TRUE : FALSE;
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
    // Archived webforms are always closed.
    if ($this->isArchived()) {
      return FALSE;
    }

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
  public function isArchived() {
    return $this->archive ? TRUE : FALSE;
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
  public function hasRemoteAddr() {
    return (!$this->isConfidential() && $this->getSetting('form_remote_addr')) ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isResultsDisabled() {
    $elements = $this->getElementsDecoded();
    $settings = $this->getSettings();
    return (!empty($settings['results_disabled']) || !empty($elements['#method'])) ? TRUE : FALSE;
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
    return (!empty($this->elementsManagedFiles)) ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function hasAttachments() {
    $this->initElements();
    return (!empty($this->elementsAttachments)) ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function hasComputed() {
    $this->initElements();
    return (!empty($this->elementsComputed)) ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function hasVariants() {
    $this->initElements();
    return (!empty($this->elementsVariant)) ? TRUE : FALSE;
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
  public function hasRequired() {
    $this->initElements();
    return $this->hasRequired;
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
  public function hasPreview() {
    return ($this->getSetting('preview') != DRUPAL_DISABLED);
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
    $this->initElements();

    // Css.
    $css = [];
    $shared_css = \Drupal::config('webform.settings')->get('assets.css') ?: '';
    if ($shared_css) {
      $css[] = $shared_css;
    }
    $webform_css = $this->css ?: '';
    if ($webform_css) {
      $css[] = $webform_css;
    }
    $css += $this->elementsCss;

    // JavaScript.
    $javascript = [];
    $shared_javascript = \Drupal::config('webform.settings')->get('assets.javascript') ?: '';
    if ($shared_javascript) {
      $javascript[] = $shared_javascript;
    }
    $webform_javascript = $this->javascript ?: '';
    if ($webform_javascript) {
      $javascript[] = $webform_javascript;
    }
    $javascript += $this->elementsJavaScript;

    return [
      'css' => implode(PHP_EOL, $css),
      'javascript' => implode(PHP_EOL, $javascript),
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
      if (array_key_exists($name, $this->settings)) {
        $this->settings[$name] = $value;
      }
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
  public function resetSettings() {
    $this->settings = $this->settingsOriginal;
    $this->setOverride(FALSE);
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
      'ajax_progress_type' => '',
      'ajax_effect' => '',
      'ajax_speed' => NULL,
      'page' => TRUE,
      'page_submit_path' => '',
      'page_confirm_path' => '',
      'page_admin_theme' => FALSE,
      'form_title' => 'both',
      'form_submit_once' => FALSE,
      'form_exception_message' => '',
      'form_open_message' => '',
      'form_close_message' => '',
      'form_previous_submissions' => TRUE,
      'form_confidential' => FALSE,
      'form_confidential_message' => '',
      'form_remote_addr' => TRUE,
      'form_convert_anonymous' => FALSE,
      'form_prepopulate' => FALSE,
      'form_prepopulate_source_entity' => FALSE,
      'form_prepopulate_source_entity_required' => FALSE,
      'form_prepopulate_source_entity_type' => FALSE,
      'form_reset' => FALSE,
      'form_disable_autocomplete' => FALSE,
      'form_novalidate' => FALSE,
      'form_disable_inline_errors' => FALSE,
      'form_required' => FALSE,
      'form_unsaved' => FALSE,
      'form_disable_back' => FALSE,
      'form_submit_back' => FALSE,
      'form_autofocus' => FALSE,
      'form_details_toggle' => FALSE,
      'form_access_denied' => WebformInterface::ACCESS_DENIED_DEFAULT,
      'form_access_denied_title' => '',
      'form_access_denied_message' => '',
      'form_access_denied_attributes' => [],
      'form_file_limit' => '',
      'submission_label' => '',
      'submission_log' => FALSE,
      'submission_views' => [],
      'submission_views_replace' => [],
      'submission_user_columns' => [],
      'submission_user_duplicate' => FALSE,
      'submission_access_denied' => WebformInterface::ACCESS_DENIED_DEFAULT,
      'submission_access_denied_title' => '',
      'submission_access_denied_message' => '',
      'submission_access_denied_attributes' => [],
      'submission_exception_message' => '',
      'submission_locked_message' => '',
      'submission_excluded_elements' => [],
      'submission_exclude_empty' => FALSE,
      'submission_exclude_empty_checkbox' => FALSE,
      'previous_submission_message' => '',
      'previous_submissions_message' => '',
      'autofill' => FALSE,
      'autofill_message' => '',
      'autofill_excluded_elements' => [],
      'wizard_progress_bar' => TRUE,
      'wizard_progress_pages' => FALSE,
      'wizard_progress_percentage' => FALSE,
      'wizard_progress_link' => FALSE,
      'wizard_progress_states' => FALSE,
      'wizard_start_label' => '',
      'wizard_preview_link' => FALSE,
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
      'preview_exclude_empty_checkbox' => FALSE,
      'draft' => self::DRAFT_NONE,
      'draft_multiple' => FALSE,
      'draft_auto_save' => FALSE,
      'draft_saved_message' => '',
      'draft_loaded_message' => '',
      'draft_pending_single_message' => '',
      'draft_pending_multiple_message' => '',
      'confirmation_type' => WebformInterface::CONFIRMATION_PAGE,
      'confirmation_title' => '',
      'confirmation_message' => '',
      'confirmation_url' => '',
      'confirmation_attributes' => [],
      'confirmation_back' => TRUE,
      'confirmation_back_label' => '',
      'confirmation_back_attributes' => [],
      'confirmation_exclude_query' => FALSE,
      'confirmation_exclude_token' => FALSE,
      'confirmation_update' => FALSE,
      'limit_total' => NULL,
      'limit_total_interval' => NULL,
      'limit_total_message' => '',
      'limit_total_unique' => FALSE,
      'limit_user' => NULL,
      'limit_user_interval' => NULL,
      'limit_user_message' => '',
      'limit_user_unique' => FALSE,
      'entity_limit_total' => NULL,
      'entity_limit_total_interval' => NULL,
      'entity_limit_user' => NULL,
      'entity_limit_user_interval' => NULL,
      'purge' => WebformSubmissionStorageInterface::PURGE_NONE,
      'purge_days' => NULL,
      'results_disabled' => FALSE,
      'results_disabled_ignore' => FALSE,
      'results_customize' => FALSE,
      'token_view' => FALSE,
      'token_update' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSubmissionForm(array $values = [], $operation = 'add') {
    // Test a single webform variant which is set via
    // ?_webform_handler[ELEMENT_KEY]={variant_id}.
    $webform_variant = \Drupal::request()->query->get('_webform_variant') ?: [];
    if ($webform_variant &&
      ($operation === 'add' && $this->access('update') || $operation === 'test' && $this->access('test'))) {
      $values += ['data' => []];
      $values['data'] = $webform_variant + $values['data'];
    }

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
   * {@inheritdoc}
   */
  public function getElementsManagedFiles() {
    $this->initElements();
    return $this->elementsManagedFiles;
  }

  /**
   * {@inheritdoc}
   */
  public function getElementsAttachments() {
    $this->initElements();
    return $this->elementsAttachments;
  }

  /**
   * {@inheritdoc}
   */
  public function getElementsComputed() {
    $this->initElements();
    return $this->elementsComputed;
  }

  /**
   * {@inheritdoc}
   */
  public function getElementsVariant() {
    $this->initElements();
    return $this->elementsVariant;
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
      $element_plugin = $element_manager->getElementInstance($element, $this);
      if (!$element_plugin->checkAccessRules($operation, $element)) {
        unset($elements[$key]);
      }
    }
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function getElementsSelectorOptions(array $options = []) {
    /** @var \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager */
    $element_manager = \Drupal::service('plugin.manager.webform.element');

    // The value element is excluded because it is not available
    // to the #states API. The value element is available to WebformHandles.
    // @see \Drupal\webform\Form\WebformHandlerFormBase
    $options += ['excluded_elements' => ['value']];

    $selectors = [];
    $elements = $this->getElementsInitializedAndFlattened();
    foreach ($elements as $element) {
      $element_plugin = $element_manager->getElementInstance($element, $this);

      // Check excluded elements.
      if ($options['excluded_elements'] && in_array($element_plugin->getPluginId(), $options['excluded_elements'])) {
        continue;
      }

      $element_selectors = $element_plugin->getElementSelectorOptions($element);
      foreach ($element_selectors as $element_selector_key => $element_selector_value) {
        // Suffix duplicate selector optgroups with empty characters.
        //
        // This prevents elements with the same titles and multiple selectors
        // from clobbering each other.
        if (isset($selectors[$element_selector_key]) && is_array($element_selector_value)) {
          while (isset($selectors[$element_selector_key])) {
            $element_selector_key .= ' ';
          }
          $selectors[$element_selector_key] = $element_selector_value;
        }
        else {
          $selectors[$element_selector_key] = $element_selector_value;
        }
      }
      $selectors += $element_plugin->getElementSelectorOptions($element);
    }
    return $selectors;
  }

  /**
   * {@inheritdoc}
   */
  public function getElementsSelectorSourceValues() {
    /** @var \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager */
    $element_manager = \Drupal::service('plugin.manager.webform.element');

    $source_values = [];
    $elements = $this->getElementsInitializedAndFlattened();
    foreach ($elements as $element) {
      $element_plugin = $element_manager->getElementInstance($element, $this);
      $source_values += $element_plugin->getElementSelectorSourceValues($element);
    }
    return $source_values;
  }

  /**
   * {@inheritdoc}
   */
  public function getElementsPrepopulate() {
    $this->initElements();
    return $this->elementsPrepopulate;
  }

  /**
   * {@inheritdoc}
   */
  public function getElementsDefaultData() {
    $this->initElements();
    return $this->elementsDefaultData;
  }

  /**
   * {@inheritdoc}
   */
  public function setElements(array $elements) {
    $this->elements = WebformYaml::encode($elements);
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

    // @see \Drupal\webform\Entity\Webform::resetElements
    $this->hasFlexboxLayout = FALSE;
    $this->hasContainer = FALSE;
    $this->hasConditions = FALSE;
    $this->hasRequired = FALSE;
    $this->elementsPrepopulate = [];
    $this->elementsActions = [];
    $this->elementsWizardPages = [];
    $this->elementsDecodedAndFlattened = [];
    $this->elementsInitializedAndFlattened = [];
    $this->elementsInitializedFlattenedAndHasValue = [];
    $this->elementsTranslations = [];
    $this->elementsManagedFiles = [];
    $this->elementsAttachments = [];
    $this->elementsComputed = [];
    $this->elementsVariant = [];
    $this->elementsCss = [];
    $this->elementsJavaScript = [];
    $this->elementsDefaultData = [];

    try {
      $config_translation = \Drupal::moduleHandler()->moduleExists('config_translation');
      /** @var \Drupal\webform\WebformTranslationManagerInterface $translation_manager */
      $translation_manager = \Drupal::service('webform.translation_manager');
      /** @var \Drupal\Core\Language\LanguageManagerInterface $language_manager */
      $language_manager = \Drupal::service('language_manager');

      // If current webform is translated, load the base (default) webform and
      // apply the translation to the elements.
      if ($config_translation
        && ($this->langcode != $language_manager->getCurrentLanguage()->getId())) {
        // Always get the elements in the original language.
        $elements = $translation_manager->getElements($this);
        // For none admin routes get the element (label) translations.
        if (!$translation_manager->isAdminRoute()) {
          $this->elementsTranslations = $translation_manager->getElements($this, $language_manager->getCurrentLanguage()->getId());
        }
      }
      else {
        $elements = Yaml::decode($this->elements);
      }

      // Since YAML supports simple values.
      $elements = (is_array($elements)) ? $elements : [];
      $this->elementsDecoded = $elements;
    }
    catch (\Exception $exception) {
      $link = $this->toLink($this->t('Edit'), 'edit-form')->toString();
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
    $this->hasFlexboxLayout = NULL;
    $this->hasContainer = NULL;
    $this->hasConditions = NULL;
    $this->hasRequired = NULL;
    $this->elementsPrepopulate = [];
    $this->elementsActions = [];
    $this->elementsWizardPages = [];
    $this->elementsDecoded = NULL;
    $this->elementsInitialized = NULL;
    $this->elementsDecodedAndFlattened = NULL;
    $this->elementsInitializedAndFlattened = NULL;
    $this->elementsInitializedFlattenedAndHasValue = NULL;
    $this->elementsTranslations = NULL;
    $this->elementsManagedFiles = [];
    $this->elementsAttachments = [];
    $this->elementsComputed = [];
    $this->elementsVariant = [];
    $this->elementsCss = [];
    $this->elementsJavaScript = [];
    $this->elementsDefaultData = [];
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
      if (!WebformElementHelper::isElement($element, $key)) {
        continue;
      }

      // Apply translation to element.
      if (isset($this->elementsTranslations[$key])) {
        WebformElementHelper::applyTranslation($element, $this->elementsTranslations[$key]);
      }

      // Prevent regressions where webform_computed_* element is still using
      // #value instead of #template.
      if (isset($element['#type']) && strpos($element['#type'], 'webform_computed_') === 0) {
        if (isset($element['#value']) && !isset($element['#template'])) {
          $element['#template'] = $element['#value'];
          unset($element['#value']);
        }
      }

      // Copy only the element properties to decoded and flattened elements.
      $this->elementsDecodedAndFlattened[$key] = WebformElementHelper::getProperties($element);

      // Set webform, id, key, parent_key, depth, and parent children.
      $element['#webform'] = $this->id();
      $element['#webform_id'] = $this->id() . '--' . $key;
      $element['#webform_key'] = $key;
      $element['#webform_parent_key'] = $parent;
      $element['#webform_parent_flexbox'] = FALSE;
      $element['#webform_depth'] = $depth;
      $element['#webform_children'] = [];
      $element['#webform_multiple'] = FALSE;
      $element['#webform_composite'] = FALSE;

      if (!empty($parent)) {
        $parent_element =& $this->elementsInitializedAndFlattened[$parent];
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

      // Set #markup type to 'webform_markup' to trigger #display_on behavior.
      // @see https://www.drupal.org/node/2036237
      if (empty($element['#type']) && empty($element['#theme']) && isset($element['#markup'])) {
        $element['#type'] = 'webform_markup';
      }

      $element_plugin = NULL;
      if (isset($element['#type'])) {
        // Load the element's handler.
        $element_plugin = $element_manager->getElementInstance($element, $this);

        // Store a reference to the plugin id which is used by derivatives.
        // Webform elements support derivatives but Form API elements
        // do not support derivatives. Therefore we need to store a
        // reference to the plugin id for when a webform element derivative
        // changes the $elements['#type'] property.
        // @see \Drupal\webform\Plugin\WebformElementManager::getElementPluginId
        // @see \Drupal\webform_options_custom\Plugin\WebformElement\WebformOptionsCustom::setOptions
        $element['#webform_plugin_id'] = $element_plugin->getPluginId();

        // Initialize the element.
        // Note: Composite sub elements are initialized via
        // \Drupal\webform\Plugin\WebformElement\WebformCompositeBase::initialize
        // and stored in the '#webform_composite_elements' property.
        $element_plugin->initialize($element);

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

        // Track required.
        if (!empty($element['#required']) || (!empty($element['#states']) && (!empty($element['#states']['required']) || !empty($element['#states']['optional'])))) {
          $this->hasRequired = TRUE;
        }

        // Track prepopulated.
        if (!empty($element['#prepopulate']) && $element_plugin->hasProperty('prepopulate')) {
          $this->elementsPrepopulate[$key] = $key;
        }

        // Track actions.
        if ($element_plugin instanceof WebformActions) {
          $this->elementsActions[$key] = $key;
        }

        // Track wizard.
        if ($element_plugin instanceof WebformWizardPage) {
          $this->elementsWizardPages[$key] = $key;
        }

        // Track managed files.
        if ($element_plugin->hasManagedFiles($element)) {
          $this->elementsManagedFiles[$key] = $key;
        }

        // Track attachments.
        if ($element_plugin instanceof WebformElementAttachmentInterface) {
          $this->elementsAttachments[$key] = $key;
        }

        // Track computed.
        if ($element_plugin instanceof WebformElementComputedInterface) {
          $this->elementsComputed[$key] = $key;
        }

        // Track variant.
        if ($element_plugin instanceof WebformElementVariantInterface) {
          $this->elementsVariant[$key] = $key;
        }

        // Track assets (CSS and JavaScript).
        // @see \Drupal\webform_options_custom\Plugin\WebformElement\WebformOptionsCustom
        if ($element_plugin instanceof WebformElementAssetInterface) {
          $asset_id = $element_plugin->getAssetId();
          if (!isset($this->elementsCss[$asset_id])) {
            if ($css = $element_plugin->getCss()) {
              $this->elementsCss[$asset_id] = $css;
            }
          }
          if (!isset($this->elementsJavaScript[$asset_id])) {
            if ($javascript = $element_plugin->getJavaScript()) {
              $this->elementsJavaScript[$asset_id] = $javascript;
            }
          }
        }

        // Track default data.
        if (isset($element['#value'])) {
          $this->elementsDefaultData[$key] = $element['#value'];
        }
        elseif (isset($element['#default_value'])) {
          $this->elementsDefaultData[$key] = $element['#default_value'];
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
      if ($this->hasActions() && array_key_exists(end($this->elementsActions), $elements)) {
        // Add element before the last 'webform_actions' element if action is
        // not placed into container.
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
      if (!WebformElementHelper::isElement($element, $element_key)) {
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
    $element = $this->getElementDecoded($key);

    // Delete element from the elements render array.
    $elements = $this->getElementsDecoded();
    $sub_element_keys = $this->deleteElementRecursive($elements, $key);
    $this->setElements($elements);

    // Delete the variants.
    $is_variant = (isset($element['#type']) && $element['#type'] === 'webform_variant');
    if ($is_variant) {
      $variants = $this->getVariants(NULL, NULL, $key);
      foreach ($variants as $variant) {
        $this->deleteWebformVariant($variant);
      }
    }

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
      if (!WebformElementHelper::isElement($element, $element_key)) {
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
      if (!WebformElementHelper::isElement($element, $key)) {
        continue;
      }
      $sub_element_keys[$key] = $key;
      $this->collectSubElementKeysRecursive($sub_element_keys, $element);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getPages($operation = 'default', WebformSubmissionInterface $webform_submission = NULL) {
    $pages = $this->buildPages($operation);
    if ($this->getSetting('wizard_progress_states') && $webform_submission) {
      /** @var \Drupal\webform\WebformSubmissionConditionsValidatorInterface $constraint_validator */
      $constraint_validator = \Drupal::service('webform_submission.conditions_validator');
      $pages = $constraint_validator->buildPages($pages, $webform_submission);
    }
    return $pages;
  }

  /**
   * Build and cache a webform's wizard pages based on the current operation.
   *
   * @param string $operation
   *   The webform submission operation.
   *   Usually 'default', 'add', 'edit', 'edit_all', 'api', or 'test'.
   *
   * @return array
   *   An associative array of webform wizard pages.
   */
  protected function buildPages($operation = 'default') {
    if (isset($this->pages[$operation])) {
      return $this->pages[$operation];
    }

    /** @var \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager */
    $element_manager = \Drupal::service('plugin.manager.webform.element');

    $wizard_properties = [
      '#title' => '#title',
      '#prev_button_label' => '#prev_button_label',
      '#next_button_label' => '#next_button_label',
      '#states' => '#states',
    ];

    $pages = [];

    // Add webform wizard pages.
    $elements = $this->getElementsInitialized();
    if (is_array($elements) && !in_array($operation, ['edit_all', 'api'])) {
      foreach ($elements as $key => $element) {
        if (!isset($element['#type'])) {
          continue;
        }

        /** @var \Drupal\webform\Plugin\WebformElementInterface $element_plugin */
        $element_plugin = $element_manager->getElementInstance($element, $this);
        if (!($element_plugin instanceof WebformElementWizardPageInterface)) {
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

    // If template being used to create a new webform, clear description
    // and remove the template flag.
    // @see \Drupal\webform_templates\Controller\WebformTemplatesController::index
    $is_template_duplicate = \Drupal::request()->get('template');
    if ($duplicate->isTemplate() && !$is_template_duplicate) {
      $duplicate->set('description', '');
      $duplicate->set('template', FALSE);
    }

    // If archived, remove archive flag.
    if ($duplicate->isArchived()) {
      $duplicate->set('archive', FALSE);
    }

    // Set default status.
    $duplicate->setStatus(\Drupal::config('webform.settings')->get('settings.default_status'));

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
    /** @var \Drupal\webform\WebformAccessRulesManagerInterface $access_rules_manager */
    $access_rules_manager = \Drupal::service('webform.access_rules_manager');

    $values += [
      'status' => \Drupal::config('webform.settings')->get('settings.default_status'),
      'uid' => \Drupal::currentUser()->id(),
      'settings' => static::getDefaultSettings(),
      'access' => $access_rules_manager->getDefaultAccessRules(),
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
      $entity->settingsOriginal = $entity->settings;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    /** @var \Drupal\webform\WebformInterface[] $entities */
    parent::preDelete($storage, $entities);

    /** @var \Drupal\user\UserDataInterface $user_data */
    $user_data = \Drupal::service('user.data');

    // Delete all paths, states, and user data associated with this webform.
    foreach ($entities as $entity) {
      // Delete all paths.
      $entity->deletePaths();

      // Delete the state.
      // @see \Drupal\webform\Entity\Webform::getState
      \Drupal::state()->delete('webform.webform.' . $entity->id());

      // Delete user data.
      // @see \Drupal\webform\Entity\Webform::getUserData
      $user_data->delete('webform', NULL, $entity->id());
    }

    // Delete all submission associated with this webform.
    $submission_ids = \Drupal::entityQuery('webform_submission')
      ->accessCheck(FALSE)
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
      if ($this->getSetting('token_view') || $this->getSetting('token_update')) {
        $cache_contexts[] = 'url.query_args:token';
      }
    }

    return $cache_contexts;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    if ($this->isScheduled()) {
      $time = time();
      if ($this->open && strtotime($this->open) > $time) {
        return (strtotime($this->open) - $time);
      }
      elseif ($this->close && strtotime($this->close) > $time) {
        return (strtotime($this->close) - $time);
      }
    }

    return parent::getCacheMaxAge();
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
      $created_elements = array_diff_key($elements, $elements_original) ?: [];
      foreach ($created_elements as $element_key => $element) {
        $this->invokeHandlers('createElement', $element_key, $element);
      }

      // Handle delete element.
      $deleted_elements = array_diff_key($elements_original, $elements) ?: [];
      foreach ($deleted_elements as $element_key => $element) {
        $this->invokeHandlers('deleteElement', $element_key, $element);
      }

      // Handle update element.
      foreach ($elements as $element_key => $element) {
        if (isset($elements_original[$element_key]) && $elements_original[$element_key] != $element) {
          $this->invokeHandlers('updateElement', $element_key, $element, $elements_original[$element_key]);
        }
      }

      // Invalidate library_info cache tag if any updated or deleted elements
      // has assets (CSS or JavaScript).
      // @see webform_library_info_build()
      /** @var \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager */
      $element_manager = \Drupal::service('plugin.manager.webform.element');
      $checked_elements = $created_elements + $deleted_elements;
      foreach ($checked_elements as $element_key => $element) {
        $element_plugin = $element_manager->getElementInstance($element, $this);
        if ($element_plugin instanceof WebformElementAssetInterface
          && $element_plugin->hasAssets()) {
          Cache::invalidateTags(['library_info']);
          break;
        }
      }
    }

    // Reset elements.
    $this->resetElements();
    $this->elementsOriginal = $this->elements;

    // Reset settings.
    $this->settingsOriginal = $this->settings;
  }

  /**
   * {@inheritdoc}
   */
  public function updatePaths() {
    // Path module must be enabled for URL aliases to be updated.
    if (!\Drupal::moduleHandler()->moduleExists('path')) {
      return;
    }

    // If 'Allow users to post submission from a dedicated URL' is disabled,
    // delete all existing paths.
    if (empty($this->settings['page'])) {
      $this->deletePaths();
      return;
    }

    $page_submit_path = trim($this->settings['page_submit_path'], '/');
    $default_page_base_path = trim(\Drupal::config('webform.settings')->get('settings.default_page_base_path'), '/');

    // Skip generating paths if submit path and base path are empty.
    if (empty($page_submit_path) && empty($default_page_base_path)) {
      return;
    }

    // Update webform base, confirmation, submissions and drafts paths.
    $path_base_alias = '/' . ($page_submit_path ?: $default_page_base_path . '/' . str_replace('_', '-', $this->id()));
    $path_suffixes = ['', '/confirmation', '/submissions', '/drafts'];
    foreach ($path_suffixes as $path_suffix) {
      $path_source = '/webform/' . $this->id() . $path_suffix;
      $path_alias = $path_base_alias . $path_suffix;
      if ($path_suffix === '/confirmation' && $this->settings['page_confirm_path']) {
        $path_alias = '/' . trim($this->settings['page_confirm_path'], '/');
      }
      $this->updatePath($path_source, $path_alias, $this->langcode);
      $this->updatePath($path_source, $path_alias, LanguageInterface::LANGCODE_NOT_SPECIFIED);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deletePaths() {
    // Path module must be enabled for URL aliases to be updated.
    if (!\Drupal::moduleHandler()->moduleExists('path')) {
      return;
    }

    /** @var \Drupal\Core\Path\AliasStorageInterface $path_alias_storage */
    $path_alias_storage = \Drupal::service('path.alias_storage');

    // Delete webform base, confirmation, submissions and drafts paths.
    $path_suffixes = ['', '/confirmation', '/submissions', '/drafts'];
    foreach ($path_suffixes as $path_suffix) {
      $path_alias_storage->delete(['source' => '/webform/' . $this->id() . $path_suffix]);
    }
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

    $pid = $path['pid'] ?? NULL;
    $path_alias_storage->save($source, $alias, $langcode, $pid);
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return [
      'handlers' => $this->getHandlers(),
      'variants' => $this->getVariants(),
    ];
  }

  /****************************************************************************/
  // Handler plugins.
  /****************************************************************************/

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
   * Reset cached handler settings.
   */
  protected function resetHandlers() {
    $this->hasMessageHandler = NULL;
    $this->hasAnonymousSubmissionTrackingHandler = NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function hasMessageHandler() {
    if (isset($this->hasMessagehandler)) {
      $this->hasMessagehandler;
    }

    $this->hasMessagehandler = FALSE;
    $handlers = $this->getHandlers();
    foreach ($handlers as $handler) {
      if ($handler instanceof WebformHandlerMessageInterface) {
        $this->hasMessagehandler = TRUE;
        break;
      }
    }

    return $this->hasMessagehandler;
  }

  /**
   * {@inheritdoc}
   */
  public function hasAnonymousSubmissionTrackingHandler() {
    if (isset($this->hasAnonymousSubmissionTrackingHandler)) {
      $this->hasAnonymousSubmissionTrackingHandler;
    }

    $this->hasAnonymousSubmissionTrackingHandler = FALSE;
    $handlers = $this->getHandlers();
    foreach ($handlers as $handler) {
      if ($handler->hasAnonymousSubmissionTracking()) {
        $this->hasAnonymousSubmissionTrackingHandler = TRUE;
        break;
      }
    }

    return $this->hasAnonymousSubmissionTrackingHandler;
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
  public function addWebformHandler(WebformHandlerInterface $handler) {
    $handler->setWebform($this);
    $handler_id = $handler->getHandlerId();
    $configuration = $handler->getConfiguration();
    $this->getHandlers()->addInstanceId($handler_id, $configuration);
    $this->save();
    $this->resetHandlers();
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
    $this->resetHandlers();
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
    $this->resetHandlers();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function invokeHandlers($method, &$data, &$context1 = NULL, &$context2 = NULL, &$context3 = NULL) {
    // Get webform submission from arguments for conditions validations.
    $webform_submission = NULL;
    $args = func_get_args();
    foreach ($args as $arg) {
      if ($arg instanceof WebformSubmissionInterface) {
        $webform_submission = $arg;
        break;
      }
    }

    // Get handlers.
    $handlers = $this->getHandlers();

    switch ($method) {
      case 'overrideSettings';
        // If webform submission and alter settings, make sure to completely
        // reset all settings to their original values.
        $this->resetSettings();
        $settings = $this->getSettings();
        foreach ($handlers as $handler) {
          $handler->setWebformSubmission($webform_submission);
          $this->invokeHandlerAlter($handler, $method, $args);
          if ($this->isHandlerEnabled($handler, $webform_submission)) {
            $handler->overrideSettings($settings, $webform_submission);
          }
        }
        // If a handler has change some settings set override.
        // Only look for altered original settings, which prevents issues where
        // a webform saved settings and default settings are out-of-sync.
        if (array_intersect_key($settings, $this->settingsOriginal) != $this->settingsOriginal) {
          $this->setSettingsOverride($settings);
        }
        return NULL;

      case 'access':
      case 'accessElement':
        // WebformHandler::access() and WebformHandler::accessElement()
        // returns a AccessResult.
        /** @var \Drupal\Core\Access\AccessResultInterface $result */
        $result = AccessResult::neutral();
        foreach ($handlers as $handler) {
          $handler->setWebformSubmission($webform_submission);
          $this->invokeHandlerAlter($handler, $method, $args);
          if ($this->isHandlerEnabled($handler, $webform_submission)) {
            $result = $result->orIf($handler->$method($data, $context1, $context2));
          }
        }
        return $result;

      default:
        foreach ($handlers as $handler) {
          $handler->setWebformSubmission($webform_submission);
          $this->invokeHandlerAlter($handler, $method, $args);
          if ($this->isHandlerEnabled($handler, $webform_submission)) {
            $handler->$method($data, $context1, $context2);
          }
        }
        return NULL;
    }
  }

  /**
   * Determine if a webform handler is enabled.
   *
   * @param \Drupal\webform\Plugin\WebformHandlerInterface $handler
   *   A webform handler.
   * @param \Drupal\webform\WebformSubmissionInterface|null $webform_submission
   *   A webform submission.
   *
   * @return bool
   *   TRUE if a webform handler is enabled.
   */
  protected function isHandlerEnabled(WebformHandlerInterface $handler, WebformSubmissionInterface $webform_submission = NULL) {
    // Check if the handler is disabled.
    if ($handler->isDisabled()) {
      return FALSE;
    }
    // If webform submission defined, check the handlers conditions.
    elseif ($webform_submission && !$handler->checkConditions($webform_submission)) {
      return FALSE;
    }
    else {
      return TRUE;
    }
  }

  /**
   * Alter a webform handler when it is invoked.
   *
   * @param \Drupal\webform\Plugin\WebformHandlerInterface $handler
   *   A webform handler.
   * @param string $method_name
   *   The handler method to be invoked.
   * @param array $args
   *   Array of arguments being passed to the handler's method.
   *
   * @see hook_webform_handler_invoke_alter()
   * @see hook_webform_handler_invoke_METHOD_NAME_alter()
   */
  protected function invokeHandlerAlter(WebformHandlerInterface $handler, $method_name, array $args) {
    $method_name = WebformTextHelper::camelToSnake($method_name);
    \Drupal::moduleHandler()->alter('webform_handler_invoke', $handler, $method_name, $args);
    \Drupal::moduleHandler()->alter('webform_handler_invoke_' . $method_name, $handler, $args);
  }

  /****************************************************************************/
  // Element plugins.
  /****************************************************************************/

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

  /****************************************************************************/
  // Variant plugins.
  /****************************************************************************/

  /**
   * Returns the webform variant plugin manager.
   *
   * @return \Drupal\Component\Plugin\PluginManagerInterface
   *   The webform variant plugin manager.
   */
  protected function getWebformVariantPluginManager() {
    return \Drupal::service('plugin.manager.webform.variant');
  }

  /**
   * {@inheritdoc}
   */
  public function hasVariant($variant_id) {
    return $this->getVariants()->has($variant_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getVariant($variant_id) {
    return $this->getVariants()->get($variant_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getVariants($plugin_id = NULL, $status = NULL, $element_key = NULL) {
    if (!$this->variantsCollection) {
      $this->variantsCollection = new WebformVariantPluginCollection($this->getWebformVariantPluginManager(), $this->variants);
      /** @var \Drupal\webform\Plugin\WebformVariantBase $variant */
      foreach ($this->variantsCollection as $variant) {
        // Initialize the variant and pass in the webform.
        $variant->setWebform($this);
      }
      $this->variantsCollection->sort();
    }

    /** @var \Drupal\webform\Plugin\WebformVariantPluginCollection $variants */
    $variants = $this->variantsCollection;

    // Clone the variants if they are being filtered.
    if (isset($plugin_id) || isset($status) || isset($element_key)) {
      /** @var \Drupal\webform\Plugin\WebformVariantPluginCollection $variants */
      $variants = clone $this->variantsCollection;
    }

    // Filter the variants by plugin id.
    // This is used to limit track and enforce a variants cardinality.
    if (isset($plugin_id)) {
      foreach ($variants as $instance_id => $variant) {
        if ($variant->getPluginId() !== $plugin_id) {
          $variants->removeInstanceId($instance_id);
        }
      }
    }

    // Filter the variants by status.
    // This is used to limit track and enforce a variants cardinality.
    if (isset($status)) {
      foreach ($variants as $instance_id => $variant) {
        if ($variant->getStatus() !== $status) {
          $variants->removeInstanceId($instance_id);
        }
      }
    }

    // Filter the variants by element key.
    if (isset($element_key)) {
      foreach ($variants as $instance_id => $variant) {
        if ($variant->getElementKey() !== $element_key) {
          $variants->removeInstanceId($instance_id);
        }
      }
    }

    return $variants;
  }

  /**
   * {@inheritdoc}
   */
  public function addWebformVariant(WebformVariantInterface $variant) {
    $variant->setWebform($this);
    $variant_id = $variant->getVariantId();
    $configuration = $variant->getConfiguration();
    $this->getVariants()->addInstanceId($variant_id, $configuration);
    $this->save();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function updateWebformVariant(WebformVariantInterface $variant) {
    $variant->setWebform($this);
    $variant_id = $variant->getVariantId();
    $configuration = $variant->getConfiguration();
    $this->getVariants()->setInstanceConfiguration($variant_id, $configuration);
    $this->save();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteWebformVariant(WebformVariantInterface $variant) {
    $variant->setWebform($this);
    $this->getVariants()->removeInstanceId($variant->getVariantId());
    $this->save();
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function applyVariants(WebformSubmissionInterface $webform_submission = NULL, $variants = [], $force = FALSE) {
    // Get variants from webform submission.
    if ($webform_submission) {
      // Make sure webform submission is associated with this webform.
      if ($webform_submission->getWebform()->id() !== $this->id()) {
        $t_args = [
          '@sid' => $webform_submission->id(),
          '@webform_id' => $this->id(),
        ];
        throw new \Exception($this->t('Variants can not be applied because the #@sid submission was not created using @webform_id', $t_args));
      }
      $variants += $this->getVariantsData($webform_submission);
    }

    // Apply variants.
    $is_applied = FALSE;
    $variant_element_keys = $this->getElementsVariant();
    foreach ($variant_element_keys as $varient_element_key) {
      if (!empty($variants[$varient_element_key])) {
        $instance_id = $variants[$varient_element_key];
        if ($this->applyVariant($varient_element_key, $instance_id, $force)) {
          $is_applied = TRUE;
        }
      }
    }
    if ($is_applied) {
      $this->setOverride();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getVariantsData(WebformSubmissionInterface $webform_submission) {
    $variants = [];
    $element_keys = $this->getElementsVariant();
    foreach ($element_keys as $element_key) {
      $variants[$element_key] = $webform_submission->getElementData($element_key);
    }
    return $variants;
  }

  /**
   * Apply webform variant.
   *
   * @param string $element_key
   *   The variant element key.
   * @param string $instance_id
   *   The variant instance id.
   * @param bool $force
   *   Apply disabled variants. Defaults to FALSE.
   *
   * @return bool
   *   Return TRUE is variant was applied.
   */
  public function applyVariant($element_key, $instance_id, $force = FALSE) {
    $element = $this->getElement($element_key);
    // Check that the webform has a variant instance.
    if (!$this->getVariants()->has($instance_id)) {
      $t_args = [
        '@title' => $element['#title'],
        '@key' => $element_key,
        '@instance_id' => $instance_id,
      ];
      // Log warning for missing variant instances.
      \Drupal::logger('webform')->warning("The '@instance_id' variant id is missing for the '@title (@key)' variant type. <strong>No variant settings have been applied.</strong>", $t_args);

      // Display onscreen warning to users who can update the webform.
      if (\Drupal::currentUser()->hasPermission('edit webform variants')) {
        \Drupal::messenger()->addWarning($this->t("The '@instance_id' variant id is missing for the '@title (@key)' variant type. <strong>No variant settings have been applied.</strong>", $t_args));
      }
      return FALSE;
    }

    $variant_plugin_id = $element['#variant'];
    $variant_plugin = $this->getVariant($instance_id);

    // Check that the variant plugin id matches the element's variant plugin id.
    if ($variant_plugin_id !== $variant_plugin->getPluginId()) {
      return FALSE;
    }

    // Check that the variant plugin instance is enabled.
    if (empty($force) && $variant_plugin->isDisabled()) {
      return FALSE;
    }

    // Apply the variant.
    $variant_plugin->applyVariant();
  }

  /****************************************************************************/
  // URL.
  /****************************************************************************/

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

  /****************************************************************************/
  // Revisions.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function isDefaultRevision() {
    return TRUE;
  }

  /****************************************************************************/
  // State.
  /****************************************************************************/

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

  /****************************************************************************/
  // User data.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function getUserData($key, $default = NULL) {
    $account = \Drupal::currentUser();
    /** @var \Drupal\user\UserDataInterface $user_data */
    $user_data = \Drupal::service('user.data');
    $values = $user_data->get('webform', $account->id(), $this->id());
    return (isset($values[$key])) ? $values[$key] : $default;
  }

  /**
   * {@inheritdoc}
   */
  public function setUserData($key, $value) {
    $account = \Drupal::currentUser();
    /** @var \Drupal\user\UserDataInterface $user_data */
    $user_data = \Drupal::service('user.data');
    $values = $user_data->get('webform', $account->id(), $this->id()) ?: [];
    $values[$key] = $value;
    $user_data->set('webform', $account->id(), $this->id(), $values);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteUserData($key) {
    $account = \Drupal::currentUser();
    /** @var \Drupal\user\UserDataInterface $user_data */
    $user_data = \Drupal::service('user.data');
    $values = $user_data->get('webform', $account->id(), $this->id()) ?: [];
    unset($values[$key]);
    $user_data->set('webform', $account->id(), $this->id(), $values);
  }

  /**
   * {@inheritdoc}
   */
  public function hasUserData($key) {
    $account = \Drupal::currentUser();
    /** @var \Drupal\user\UserDataInterface $user_data */
    $user_data = \Drupal::service('user.data');
    $values = $user_data->get('webform', $account->id(), $this->id()) ?: [];
    return (isset($values[$key])) ? TRUE : FALSE;
  }

  /****************************************************************************/
  // Dependency.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    $changed = parent::onDependencyRemoval($dependencies);

    $handlers = $this->getHandlers();
    if (!empty($handlers)) {
      foreach ($handlers as $handler) {
        $plugin_definition = $handler->getPluginDefinition();
        $provider = $plugin_definition['provider'];
        if (in_array($provider, $dependencies['module'])) {
          $this->deleteWebformHandler($handler);
          $changed = TRUE;
        }
      }
    }

    $variants = $this->getVariants();
    if (!empty($variants)) {
      foreach ($variants as $variant) {
        $plugin_definition = $variant->getPluginDefinition();
        $provider = $plugin_definition['provider'];
        if (in_array($provider, $dependencies['module'])) {
          $this->deleteWebformVariant($variant);
          $changed = TRUE;
        }
      }
    }

    return $changed;
  }

  /****************************************************************************/
  // Other.
  /****************************************************************************/

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

  /**
   * Define empty to string method.
   *
   * See: Issue #2926903: Devel Tokens tab Broken when Webform Embedded in Node.
   *
   * @see https://www.drupal.org/project/webform/issues/2926903
   */
  public function __toString() {
    return '';
  }

}
