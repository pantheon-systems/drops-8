<?php

namespace Drupal\webform;

use Drupal\Component\Render\PlainTextOutput;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\Form\WebformDialogFormTrait;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Drupal\webform\Plugin\WebformHandlerInterface;
use Drupal\webform\Utility\WebformArrayHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a webform to collect and edit submissions.
 */
class WebformSubmissionForm extends ContentEntityForm {

  use WebformDialogFormTrait;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The path alias manager.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * The path validator.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * The webform element (plugin) manager.
   *
   * @var \Drupal\webform\Plugin\WebformElementManagerInterface
   */
  protected $elementManager;

  /**
   * The webform submission storage.
   *
   * @var \Drupal\webform\WebformSubmissionStorageInterface
   */
  protected $storage;

  /**
   * Webform request handler.
   *
   * @var \Drupal\webform\WebformRequestInterface
   */
  protected $requestHandler;

  /**
   * The webform third party settings manager.
   *
   * @var \Drupal\webform\WebformThirdPartySettingsManagerInterface
   */
  protected $thirdPartySettingsManager;

  /**
   * The webform message manager.
   *
   * @var \Drupal\webform\WebformMessageManagerInterface
   */
  protected $messageManager;

  /**
   * The webform token manager.
   *
   * @var \Drupal\webform\WebformTokenManagerInterface
   */
  protected $tokenManager;

  /**
   * The webform submission conditions (#states) validator.
   *
   * @var \Drupal\webform\WebformSubmissionConditionsValidator
   */
  protected $conditionsValidator;

  /**
   * The webform entity reference manager.
   *
   * @var \Drupal\webform\WebformEntityReferenceManagerInterface
   */
  protected $webformEntityReferenceManager;

  /**
   * The webform settings.
   *
   * @var array
   */
  protected $settings;

  /**
   * The webform submission.
   *
   * @var \Drupal\webform\WebformSubmissionInterface
   */
  protected $entity;

  /**
   * The source entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $sourceEntity;

  /**
   * Constructs a WebformSubmissionForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   The path alias manager.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The path validator.
   * @param \Drupal\webform\WebformRequestInterface $request_handler
   *   The webform request handler.
   * @param \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager
   *   The webform element manager.
   * @param \Drupal\webform\WebformThirdPartySettingsManagerInterface $third_party_settings_manager
   *   The webform third party settings manager.
   * @param \Drupal\webform\WebformMessageManagerInterface $message_manager
   *   The webform message manager.
   * @param \Drupal\webform\WebformTokenManagerInterface $token_manager
   *   The webform token manager.
   * @param \Drupal\webform\WebformSubmissionConditionsValidator $conditions_validator
   *   The webform submission conditions (#states) validator.
   * @param \Drupal\webform\WebformEntityReferenceManagerInterface $webform_entity_reference_manager
   *   The webform entity reference manager.
   */
  public function __construct(EntityManagerInterface $entity_manager, RendererInterface $renderer, AliasManagerInterface $alias_manager, PathValidatorInterface $path_validator, WebformRequestInterface $request_handler, WebformElementManagerInterface $element_manager, WebformThirdPartySettingsManagerInterface $third_party_settings_manager, WebformMessageManagerInterface $message_manager, WebformTokenManagerInterface $token_manager, WebformSubmissionConditionsValidator $conditions_validator, WebformEntityReferenceManagerInterface $webform_entity_reference_manager) {
    parent::__construct($entity_manager);
    $this->renderer = $renderer;
    $this->requestHandler = $request_handler;
    $this->aliasManager = $alias_manager;
    $this->pathValidator = $path_validator;
    $this->elementManager = $element_manager;
    $this->storage = $this->entityManager->getStorage('webform_submission');
    $this->thirdPartySettingsManager = $third_party_settings_manager;
    $this->messageManager = $message_manager;
    $this->tokenManager = $token_manager;
    $this->conditionsValidator = $conditions_validator;
    $this->webformEntityReferenceManager = $webform_entity_reference_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('renderer'),
      $container->get('path.alias_manager'),
      $container->get('path.validator'),
      $container->get('webform.request'),
      $container->get('plugin.manager.webform.element'),
      $container->get('webform.third_party_settings_manager'),
      $container->get('webform.message_manager'),
      $container->get('webform.token_manager'),
      $container->get('webform_submission.conditions_validator'),
      $container->get('webform.entity_reference_manager')

    );
  }

  /**
   * {@inheritdoc}
   *
   * The WebformSubmissionForm trigger the below hooks...
   * - hook_form_alter()
   * - hook_form_{BASE_FORM_ID}_alter() => hook_form_webform_submission_alter()
   * - hook_form_{BASE_FORM_ID}_{WEBFORM_ID}_alter() => hook_form_webform_submission_contact_alter()
   * - hook_form_{BASE_FORM_ID}_{WEBFORM_ID}_{ENTITY_TYPE}_{ENTITY_ID}_form_alter() => hook_form_webform_submission_contact_node_1_form_alter()
   * - hook_form_{BASE_FORM_ID}_{WEBFORM_ID}_{ENTITY_TYPE}_{ENTITY_ID}_{OPERATION}_form_alter() => hook_form_webform_submission_contact_node_1_add_form_alter()
   *
   * @see hook_form_alter()
   * @see \Drupal\Core\Entity\EntityForm::getBaseFormId
   * @see webform_form_webform_submission_form_alter()
   */
  public function getFormId() {
    $form_id = $this->entity->getEntityTypeId();
    if ($this->entity->getEntityType()->hasKey('bundle')) {
      $form_id .= '_' . $this->entity->bundle();
    }
    if ($source_entity = $this->entity->getSourceEntity()) {
      $form_id .= '_' . $source_entity->getEntityTypeId() . '_' . $source_entity->id();
    }
    if ($this->operation != 'default') {
      $form_id .= '_' . $this->operation;
    }
    return $form_id . '_form';
  }

  /**
   * {@inheritdoc}
   */
  public function setEntity(EntityInterface $entity) {
    /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
    $webform_submission = $entity;
    $webform = $webform_submission->getWebform();

    // Get the source entity and allow webform submission to be used as a source
    // entity.
    $this->sourceEntity = $this->requestHandler->getCurrentSourceEntity(['webform']);
    if ($this->sourceEntity == $webform_submission) {
      $this->sourceEntity = $this->requestHandler->getCurrentSourceEntity(['webform', 'webform_submission']);
    }

    $source_entity = $this->sourceEntity;

    // Load entity from token or saved draft when not editing or testing
    // submission form.
    if (!in_array($this->operation, ['edit', 'test'])) {
      $token = $this->getRequest()->query->get('token');
      $webform_submission_token = $this->storage->loadFromToken($token, $webform, $source_entity);
      if ($webform_submission_token) {
        $entity = $webform_submission_token;
      }
      elseif ($webform->getSetting('draft') != WebformInterface::DRAFT_NONE) {
        $account = $this->currentUser();
        if ($webform->getSetting('draft_multiple')) {
          // Allow multiple drafts to be restored using token.
          // This allows the webform's public facing URL to be used instead of
          // the admin URL of the webform.
          $webform_submission_token = $this->storage->loadFromToken($token, $webform, $source_entity, $account);
          if ($webform_submission_token && $webform_submission_token->isDraft()) {
            $entity = $webform_submission_token;
          }
        }
        elseif ($webform_submission_draft = $this->storage->loadDraft($webform, $source_entity, $account)) {
          // Else load the most recent draft.
          $entity = $webform_submission_draft;
        }
      }
    }

    return parent::setEntity($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    parent::copyFormValuesToEntity($entity, $form, $form_state);

    /* @var $webform_submission \Drupal\webform\WebformSubmissionInterface */
    $webform_submission = $entity;
    $webform = $webform_submission->getWebform();

    // Get elements values from webform submission.
    $values = array_intersect_key(
      $form_state->getValues(),
      $webform->getElementsInitializedFlattenedAndHasValue()
    );

    // Serialize the values as YAML and merge existing data.
    $webform_submission->setData($values + $webform_submission->getData());

    // Set current page.
    if ($current_page = $this->getCurrentPage($form, $form_state)) {
      $entity->setCurrentPage($current_page);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $webform_submission \Drupal\webform\WebformSubmissionInterface */
    $webform_submission = $this->getEntity();
    $webform = $this->getWebform();

    // All anonymous submissions are tracked in the $_SESSION.
    // @see \Drupal\webform\WebformSubmissionStorage::setAnonymousSubmission
    if ($this->currentUser()->isAnonymous()) {
      $form['#cache']['contexts'][] = 'session';
    }

    // Add the webform as a cacheable dependency.
    $this->renderer->addCacheableDependency($form, $webform);

    // Display status messages.
    $this->displayMessages($form, $form_state);

    // Build the webform.
    $form = parent::buildForm($form, $form_state);

    // Ajax: Scroll to.
    // @see \Drupal\webform\Form\WebformAjaxFormTrait::submitAjaxForm
    if ($this->isAjax()) {
      $form['#webform_ajax_scroll_top'] = $this->getWebformSetting('ajax_scroll_top');
    }

    // Server side #states API validation.
    $this->conditionsValidator->buildForm($form, $form_state);

    // Alter webform via webform handler.
    $this->getWebform()->invokeHandlers('alterForm', $form, $form_state, $webform_submission);

    // Call custom webform alter hook.
    $form_id = $this->getFormId();
    $this->thirdPartySettingsManager->alter('webform_submission_form', $form, $form_state, $form_id);

    return $this->buildAjaxForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /* @var $webform_submission \Drupal\webform\WebformSubmissionInterface */
    $webform_submission = $this->getEntity();
    $source_entity = $webform_submission->getSourceEntity();
    $webform = $this->getWebform();

    // Add a reference to the webform's id to the $form render array.
    $form['#webform_id'] = $webform->id();

    // Track current page name or index by setting the
    // "data-webform-wizard-page"
    // attribute which is used Drupal.behaviors.webformWizardTrackPage.
    // The data parameter is append to the URL after the form has been submitted
    // @see js/webform.form.wizard.js
    $track = $this->getWebform()->getSetting('wizard_track');
    if ($track && $this->getRequest()->isMethod('POST')) {
      $current_page = $this->getCurrentPage($form, $form_state);
      if ($track == 'index') {
        $pages = $this->getWebform()->getPages($this->operation);
        $track_pages = array_flip(array_keys($pages));
        $form['#attributes']['data-webform-wizard-current-page'] = ($track_pages[$current_page] + 1);
      }
      else {
        $form['#attributes']['data-webform-wizard-current-page'] = $current_page;
      }
    }

    // Define very specific webform classes, this override the form's
    // default classes.
    // @see \Drupal\Core\Form\FormBuilder::retrieveForm
    $webform_id = $webform->id();
    $operation = $this->operation;
    $class = [];
    $class[] = "webform-submission-form";
    $class[] = "webform-submission-$operation-form";
    $class[] = "webform-submission-$webform_id-form";
    $class[] = "webform-submission-$webform_id-$operation-form";
    if ($source_entity) {
      $source_entity_type = $source_entity->getEntityTypeId();
      $source_entity_id = $source_entity->id();
      $class[] = "webform-submission-$webform_id-$source_entity_type-$source_entity_id-form";
      $class[] = "webform-submission-$webform_id-$source_entity_type-$source_entity_id-$operation-form";
    }
    array_walk($class, ['\Drupal\Component\Utility\Html', 'getClass']);
    $form['#attributes']['class'] = $class;

    // Check for a custom webform, track it, and return it.
    if ($custom_form = $this->getCustomForm($form, $form_state)) {
      $custom_form['#custom_form'] = TRUE;
      return $custom_form;
    }

    $form = parent::form($form, $form_state);

    /* Information */

    // Prepend webform submission data using the default view without the data.
    if (!$webform_submission->isNew() && !$webform_submission->isDraft()) {
      $form['navigation'] = [
        '#theme' => 'webform_submission_navigation',
        '#webform_submission' => $webform_submission,
        '#weight' => -20,
      ];
      $form['information'] = [
        '#theme' => 'webform_submission_information',
        '#webform_submission' => $webform_submission,
        '#source_entity' => $this->sourceEntity,
        '#weight' => -19,
      ];
    }

    /* Confirmation */

    // Add confirmation modal.
    if ($webform_confirmation_modal = $form_state->get('webform_confirmation_modal')) {
      $form['webform_confirmation_modal'] = [
        '#type' => 'webform_message',
        '#message_type' => 'status',
        '#message_message' => [
          'title' => [
            '#markup' => $webform_confirmation_modal['title'],
            '#prefix' => '<b class="webform-confirmation-modal--title">',
            '#suffix' => '</b><br/>',
          ],
          'content' => [
            'content' => $webform_confirmation_modal['content'],
            '#prefix' => '<div class="webform-confirmation-modal--content">',
            '#suffix' => '</div>',
          ],
        ],
        '#attributes' => ['class' => ['js-hide', 'webform-confirmation-modal', 'js-webform-confirmation-modal']],
        '#weight' => -1000,
      ];
      $form['#attached']['library'][] = 'webform/webform.confirmation.modal';
    }

    /* Data */

    // Get and prepopulate (via query string) submission data.
    $data = $webform_submission->getData();
    $this->prepopulateData($data);

    /* Elements */

    // Get webform elements.
    $elements = $webform_submission->getWebform()->getElementsInitialized();

    // Populate webform elements with webform submission data.
    $this->populateElements($elements, $data);

    // Prepare webform elements.
    $this->prepareElements($elements, $form, $form_state);

    // Add wizard progress tracker to the webform.
    $current_page = $this->getCurrentPage($form, $form_state);
    if ($current_page && $this->getWebformSetting('wizard_progress_bar') || $this->getWebformSetting('wizard_progress_pages') || $this->getWebformSetting('wizard_progress_percentage')) {
      $form['progress'] = [
        '#theme' => 'webform_progress',
        '#webform' => $this->getWebform(),
        '#current_page' => $current_page,
        '#operation' => $this->operation,
        '#weight' => -20,
      ];
    }

    // Append elements to the webform.
    $form['elements'] = $elements;

    // Pages: Set current wizard or preview page.
    $this->displayCurrentPage($form, $form_state);

    /* Webform  */

    // Move all $elements properties to the $form.
    $this->setFormPropertiesFromElements($form, $elements);

    // Default: Add CSS and JS.
    // @see https://www.drupal.org/node/2274843#inline
    $form['#attached']['library'][] = 'webform/webform.form';

    // Attach disable back button.
    if ($this->getWebformSetting('form_disable_back')) {
      $form['#attached']['library'][] = 'webform/webform.form.disable_back';
    }

    // Unsaved: Add unsaved message.
    if ($this->getWebformSetting('form_unsaved')) {
      $form['#attributes']['class'][] = 'js-webform-unsaved';
      $form['#attached']['library'][] = 'webform/webform.form.unsaved';
      $pages = $this->getPages($form, $form_state);
      $current_page = $this->getCurrentPage($form, $form_state);
      if ($current_page && ($current_page != $this->getFirstPage($pages))) {
        $form['#attributes']['data-webform-unsaved'] = TRUE;
      }
    }

    // Submit once: Prevent duplicate submissions.
    if ($this->getWebformSetting('form_submit_once')) {
      $form['#attributes']['class'][] = 'js-webform-submit-once';
      $form['#attached']['library'][] = 'webform/webform.form.submit_once';
    }

    // Autocomplete: Add autocomplete=off attribute to form if autocompletion is
    // disabled.
    if ($this->getWebformSetting('form_disable_autocomplete')) {
      $form['#attributes']['autocomplete'] = 'off';
    }

    // Novalidate: Add novalidate attribute to form if client side validation disabled.
    if ($this->getWebformSetting('form_novalidate')) {
      $form['#attributes']['novalidate'] = 'novalidate';
    }

    // Details toggle: Display collapse/expand all details link.
    if ($this->getWebformSetting('form_details_toggle')) {
      $form['#attributes']['class'][] = 'js-webform-details-toggle';
      $form['#attributes']['class'][] = 'webform-details-toggle';
      $form['#attached']['library'][] = 'webform/webform.element.details.toggle';
    }

    // Autofocus: Add autofocus class to webform.
    if ($this->entity->isNew() && $this->getWebformSetting('form_autofocus')) {
      $form['#attributes']['class'][] = 'js-webform-autofocus';
    }

    // Details save: Attach details element save open/close library.
    // This ensures that the library will be loaded even if the webform is
    // used as a block or a node.
    if ($this->config('webform.settings')->get('ui.details_save')) {
      $form['#attached']['library'][] = 'webform/webform.element.details.save';
    }

    // Pages: Disable webform auto submit on enter for wizard webform pages only.
    if ($this->hasPages()) {
      $form['#attributes']['class'][] = 'js-webform-disable-autosubmit';
    }

    // Add #after_build callbacks.
    $form['#after_build'][] = '::afterBuild';

    return $form;
  }

  /**
   * Get custom webform which is displayed instead of the webform's elements.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array|bool
   *   A custom webform or FALSE if the default webform containing the webform's
   *   elements should be built.
   */
  protected function getCustomForm(array &$form, FormStateInterface $form_state) {
    /* @var $webform_submission \Drupal\webform\WebformSubmissionInterface */
    $webform_submission = $this->getEntity();
    $webform = $this->getWebform();

    // Exit if elements are broken, usually occurs when elements YAML is edited
    // directly in the export config file.
    if (!$webform_submission->getWebform()->getElementsInitialized()) {
      return $this->getMessageManager()->append($form, WebformMessageManagerInterface::FORM_EXCEPTION, 'warning');
    }

    // Check prepopulate source entity required and type.
    if ($webform->getSetting('form_prepopulate_source_entity')) {
      if ($webform->getSetting('form_prepopulate_source_entity_required') && empty($this->getSourceEntity())) {
        $this->getMessageManager()->log(WebformMessageManagerInterface::PREPOPULATE_SOURCE_ENTITY_REQUIRED, 'notice');
        return $this->getMessageManager()->append($form, WebformMessageManagerInterface::PREPOPULATE_SOURCE_ENTITY_REQUIRED, 'warning');
      }
      $source_entity_type = $webform->getSetting('form_prepopulate_source_entity_type');
      if ($source_entity_type && $this->getSourceEntity() && $source_entity_type != $this->getSourceEntity()->getEntityTypeId()) {
        $this->getMessageManager()->log(WebformMessageManagerInterface::PREPOPULATE_SOURCE_ENTITY_TYPE, 'notice');
        return $this->getMessageManager()->append($form, WebformMessageManagerInterface::PREPOPULATE_SOURCE_ENTITY_TYPE, 'warning');
      }
    }

    // Handle webform with managed file upload but saving of submission is disabled.
    if ($webform->hasManagedFile() && !empty($this->getWebformSetting('results_disabled'))) {
      $this->getMessageManager()->log(WebformMessageManagerInterface::FORM_FILE_UPLOAD_EXCEPTION, 'notice');
      return $this->getMessageManager()->append($form, WebformMessageManagerInterface::FORM_EXCEPTION, 'warning');
    }

    // Display inline confirmation message with back to link.
    if ($form_state->get('current_page') === 'webform_confirmation') {
      $form['confirmation'] = [
        '#theme' => 'webform_confirmation',
        '#webform' => $webform,
        '#source_entity' => $webform_submission->getSourceEntity(),
        '#webform_submission' => $webform_submission,
      ];
      // Add hidden back (aka reset) button used by the Ajaxified back to link.
      // NOTE: Below code could be used to add a 'Reset' button to any webform.
      // @see Drupal.behaviors.webformConfirmationBackAjax
      $form['actions']['reset'] = [
        '#type' => 'submit',
        '#value' => $this->t('Reset'),
        // @see \Drupal\webform\WebformSubmissionForm::noValidate
        '#validate' => ['::noValidate'],
        '#submit' => ['::reset'],
        '#attributes' => [
          'style' => 'display:none',
          'class' => ['js-webform-confirmation-back-submit-ajax'],
        ],
      ];
      return $form;
    }

    // Don't display webform if it is closed.
    if ($webform_submission->isNew() && $webform->isClosed()) {
      // If the current user can update any submission just display the closed
      // message and still allow them to create new submissions.
      if ($webform->isTemplate() && $webform->access('duplicate')) {
        if (!$this->isDialog()) {
          $this->getMessageManager()->display(WebformMessageManagerInterface::TEMPLATE_PREVIEW, 'warning');
        }
      }
      elseif ($webform->access('submission_update_any')) {
        $form = $this->getMessageManager()->append($form, WebformMessageManagerInterface::ADMIN_CLOSED, 'info');
      }
      else {
        if ($webform->isOpening()) {
          return $this->getMessageManager()->append($form, WebformMessageManagerInterface::FORM_OPEN_MESSAGE);
        }
        else {
          return $this->getMessageManager()->append($form, WebformMessageManagerInterface::FORM_CLOSE_MESSAGE);
        }
      }
    }

    // Disable this webform if confidential and user is logged in.
    if ($this->isConfidential() && $this->currentUser()->isAuthenticated() && $this->entity->isNew()) {
      return $this->getMessageManager()->append($form, WebformMessageManagerInterface::FORM_CONFIDENTIAL_MESSAGE, 'warning');
    }

    // Disable this webform if submissions are not being saved to the database or
    // passed to a WebformHandler.
    if ($this->getWebformSetting('results_disabled') && !$this->getWebformSetting('results_disabled_ignore') && !$webform->getHandlers(NULL, TRUE, WebformHandlerInterface::RESULTS_PROCESSED)->count()) {
      $this->getMessageManager()->log(WebformMessageManagerInterface::FORM_SAVE_EXCEPTION, 'error');
      if ($this->currentUser()->hasPermission('administer webform')) {
        // Display error to admin but allow them to submit the broken webform.
        $form = $this->getMessageManager()->append($form, WebformMessageManagerInterface::FORM_SAVE_EXCEPTION, 'error');
        $form = $this->getMessageManager()->append($form, WebformMessageManagerInterface::ADMIN_CLOSED, 'info');
      }
      else {
        // Display exception message to users.
        return $this->getMessageManager()->append($form, WebformMessageManagerInterface::FORM_EXCEPTION, 'warning');
      }
    }

    // Check total limit.
    if ($this->checkTotalLimit()) {
      $form = $this->getMessageManager()->append($form, WebformMessageManagerInterface::LIMIT_TOTAL_MESSAGE);
      if ($webform->access('submission_update_any')) {
        $form = $this->getMessageManager()->append($form, WebformMessageManagerInterface::ADMIN_CLOSED, 'info');
      }
      else {
        return $form;
      }
    }

    // Check user limit.
    if ($this->checkUserLimit()) {
      $form = $this->getMessageManager()->append($form, WebformMessageManagerInterface::LIMIT_USER_MESSAGE, 'warning');
      if ($webform->access('submission_update_any')) {
        $form = $this->getMessageManager()->append($form, WebformMessageManagerInterface::ADMIN_CLOSED, 'info');
      }
      else {
        return $form;
      }
    }

    return FALSE;
  }

  /**
   * Display draft and previous submission status messages for this webform submission.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function displayMessages(array $form, FormStateInterface $form_state) {
    /* @var $webform_submission \Drupal\webform\WebformSubmissionInterface */
    $webform_submission = $this->getEntity();
    $webform = $this->getWebform();
    $source_entity = $this->getSourceEntity();

    // Display test message.
    if ($this->isGet() && $this->isRoute('webform.test_form')) {
      $this->getMessageManager()->display(WebformMessageManagerInterface::SUBMISSION_TEST, 'warning');

      // Display devel generate link for webform or source entity.
      if ($this->moduleHandler->moduleExists('devel_generate') && $this->currentUser()->hasPermission('administer webform')) {
        $query = ['webform_id' => $webform->id()];
        if ($source_entity) {
          $query += [
            'entity_type' => $source_entity->getEntityTypeId(),
            'entity_id' => $source_entity->id(),
          ];
        }
        $query['destination'] = $this->requestHandler->getUrl($webform, $source_entity, 'webform.results_submissions')->toString();
        $build = [
          '#type' => 'link',
          '#title' => $this->t('Generate webform submissions'),
          '#url' => Url::fromRoute('devel_generate.webform_submission', [], ['query' => $query]),
          '#attributes' => ['class' => ['button', 'button--small']],
        ];
        drupal_set_message($this->renderer->renderPlain($build), 'warning');
      }
    }

    // Display admin only message.
    if ($this->isGet() && $this->isRoute('webform.canonical') && !$this->getWebform()->getSetting('page')) {
      $this->getMessageManager()->display(WebformMessageManagerInterface::ADMIN_PAGE, 'info');
    }

    // Display loaded or saved draft message.
    if ($webform_submission->isDraft()) {
      if ($form_state->get('draft_saved')) {
        $this->getMessageManager()->display(WebformMessageManagerInterface::SUBMISSION_DRAFT_SAVED);
        $form_state->set('draft_saved', FALSE);
      }
      elseif ($this->isGet() && !$webform->getSetting('draft_multiple')) {
        $this->getMessageManager()->display(WebformMessageManagerInterface::SUBMISSION_DRAFT_LOADED);
      }
    }

    // Display link to multiple drafts message when user is adding a new
    // submission.
    if ($this->isGet()
      && $this->getWebformSetting('draft') !== WebformInterface::DRAFT_NONE
      && $this->getWebformSetting('draft_multiple', FALSE)
      && ($this->isRoute('webform.canonical') || $this->isWebformEntityReferenceFromSourceEntity())
      && ($previous_draft_total = $this->storage->getTotal($webform, $this->sourceEntity, $this->currentUser(), TRUE))
    ) {
      if ($previous_draft_total > 1) {
        $this->getMessageManager()->display(WebformMessageManagerInterface::DRAFTS_PREVIOUS);
      }
      else {
        $draft_submission = $this->storage->loadDraft($webform, $this->sourceEntity, $this->currentUser());
        if (!$draft_submission || $webform_submission->id() != $draft_submission->id()) {
          $this->getMessageManager()->display(WebformMessageManagerInterface::DRAFT_PREVIOUS);
        }
      }
    }

    // Display link to previous submissions message when user is adding a new
    // submission.
    if ($this->isGet()
      && $this->getWebformSetting('form_previous_submissions', FALSE)
      && ($this->isRoute('webform.canonical') || $this->isWebformEntityReferenceFromSourceEntity())
      && ($webform->access('submission_view_own') || $this->currentUser()->hasPermission('view own webform submission'))
      && ($previous_submission_total = $this->storage->getTotal($webform, $this->sourceEntity, $this->currentUser()))
    ) {
      if ($previous_submission_total > 1) {
        $this->getMessageManager()->display(WebformMessageManagerInterface::SUBMISSIONS_PREVIOUS);
      }
      elseif ($webform_submission->id() != $this->storage->getLastSubmission($webform, $this->sourceEntity, $this->currentUser())->id()) {
        $this->getMessageManager()->display(WebformMessageManagerInterface::SUBMISSION_PREVIOUS);
      }
    }
  }

  /****************************************************************************/
  // Webform actions
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function afterBuild(array $form, FormStateInterface $form_state) {
    // If webform has a custom #action remove Form API fields.
    // @see \Drupal\Core\Form\FormBuilder::prepareForm
    if (strpos($form['#action'], 'form_action_') === FALSE) {
      // Remove 'op' #name from all action buttons.
      foreach (Element::children($form['actions']) as $child_key) {
        unset($form['actions'][$child_key]['#name']);
      }
      unset(
        $form['form_build_id'],
        $form['form_token'],
        $form['form_id']
      );
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actionsElement(array $form, FormStateInterface $form_state) {
    // Custom webforms, which completely override the ContentEntityForm, should
    // not return the actions element (aka submit buttons).
    if (!empty($form['#custom_form'])) {
      return NULL;
    }
    $element = parent::actionsElement($form, $form_state);
    if (!empty($element)) {
      $element['#theme'] = 'webform_actions';
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    /* @var $webform_submission \Drupal\webform\WebformSubmissionInterface */
    $webform_submission = $this->entity;

    $element = parent::actions($form, $form_state);

    /* @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
    $preview_mode = $this->getWebformSetting('preview');

    // Remove the delete button from the webform submission webform.
    unset($element['delete']);

    // Mark the submit action as the primary action, when it appears.
    $element['submit']['#button_type'] = 'primary';
    $element['submit']['#attributes']['class'][] = 'webform-button--submit';
    $element['submit']['#weight'] = 10;

    // Customize the submit button's label for new submissions only.
    if ($webform_submission->isNew() || $webform_submission->isDraft()) {
      $element['submit']['#value'] = $this->config('webform.settings')->get('settings.default_submit_button_label');
    }

    // Add validate and complete handler to submit.
    $element['submit']['#validate'][] = '::validateForm';
    $element['submit']['#validate'][] = '::autosave';
    $element['submit']['#validate'][] = '::complete';

    // Add confirm(ation) handler to submit button.
    $element['submit']['#submit'][] = '::confirmForm';

    $pages = $this->getPages($form, $form_state);
    $current_page = $this->getCurrentPage($form, $form_state);
    if ($pages) {
      // Get current page element which can contain custom prev(ious) and next button
      // labels.
      $current_page_element = $this->getWebform()->getPage($this->operation, $current_page);
      $previous_page = $this->getPreviousPage($pages, $current_page);
      $next_page = $this->getNextPage($pages, $current_page);

      // Track previous and next page.
      $track = $this->getWebform()->getSetting('wizard_track');
      switch ($track) {
        case 'index':
          $track_pages = array_flip(array_keys($pages));
          $track_previous_page = ($previous_page) ? $track_pages[$previous_page] + 1 : NULL;
          $track_next_page = ($next_page) ? $track_pages[$next_page] + 1 : NULL;
          $track_last_page = ($this->getWebform()->getSetting('wizard_confirmation')) ? count($track_pages) : count($track_pages) + 1;
          break;

        default;
        case 'name':
          $track_previous_page = $previous_page;
          $track_next_page = $next_page;
          $track_last_page = 'webform_confirmation';
          break;
      }

      $is_first_page = ($current_page == $this->getFirstPage($pages)) ? TRUE : FALSE;
      $is_last_page = (in_array($current_page, ['webform_preview', 'webform_confirmation', $this->getLastPage($pages)])) ? TRUE : FALSE;
      $is_preview_page = ($current_page == 'webform_preview');
      $is_next_page_preview = ($next_page == 'webform_preview') ? TRUE : FALSE;
      $is_next_page_complete = ($next_page == 'webform_confirmation') ? TRUE : FALSE;
      $is_next_page_optional_preview = ($is_next_page_preview && $preview_mode != DRUPAL_REQUIRED);

      // Only show that save button if this is the last page of the wizard or
      // on preview page or right before the optional preview.
      $element['submit']['#access'] = $is_last_page || $is_preview_page || $is_next_page_optional_preview || $is_next_page_complete;

      if ($track) {
        $element['submit']['#attributes']['data-webform-wizard-page'] = $track_last_page;
      }

      if (!$is_first_page) {
        if ($is_preview_page) {
          $element['preview_prev'] = [
            '#type' => 'submit',
            '#value' => $this->config('webform.settings')->get('settings.default_preview_prev_button_label'),
            // @see \Drupal\webform\WebformSubmissionForm::noValidate
            '#validate' => ['::noValidate'],
            '#submit' => ['::previous'],
            '#attributes' => ['class' => ['webform-button--previous', 'js-webform-novalidate']],
            '#weight' => 0,
          ];
          if ($track) {
            $element['preview_prev']['#attributes']['data-webform-wizard-page'] = $track_previous_page;
          }
        }
        else {
          if (isset($current_page_element['#prev_button_label'])) {
            $previous_button_label = $current_page_element['#prev_button_label'];
            $previous_button_custom = TRUE;
          }
          else {
            $previous_button_label = $this->config('webform.settings')->get('settings.default_wizard_prev_button_label');
            $previous_button_custom = FALSE;
          }
          $element['wizard_prev'] = [
            '#type' => 'submit',
            '#value' => $previous_button_label,
            '#webform_actions_button_custom' => $previous_button_custom,
            // @see \Drupal\webform\WebformSubmissionForm::noValidate
            '#validate' => ['::noValidate'],
            '#submit' => ['::previous'],
            '#attributes' => ['class' => ['webform-button--previous', 'js-webform-novalidate']],
            '#weight' => 0,
          ];
          if ($track) {
            $element['wizard_prev']['#attributes']['data-webform-wizard-page'] = $track_previous_page;
          }
        }
      }

      if (!$is_last_page && !$is_next_page_complete) {
        if ($is_next_page_preview) {
          $element['preview_next'] = [
            '#type' => 'submit',
            '#value' => $this->config('webform.settings')->get('settings.default_preview_next_button_label'),
            '#validate' => ['::validateForm'],
            '#submit' => ['::next'],
            '#attributes' => ['class' => ['webform-button--preview']],
            '#weight' => 1,
          ];
          if ($track) {
            $element['preview_next']['#attributes']['data-webform-wizard-page'] = $track_next_page;
          }
        }
        else {
          if (isset($current_page_element['#next_button_label'])) {
            $next_button_label = $current_page_element['#next_button_label'];
            $next_button_custom = TRUE;
          }
          else {
            $next_button_label = $this->config('webform.settings')->get('settings.default_wizard_next_button_label');
            $next_button_custom = FALSE;
          }
          $element['wizard_next'] = [
            '#type' => 'submit',
            '#value' => $next_button_label,
            '#webform_actions_button_custom' => $next_button_custom,
            '#validate' => ['::validateForm'],
            '#submit' => ['::next'],
            '#attributes' => [
              'class' => ['webform-button--next']
            ],
            '#weight' => 1,
          ];
          if ($track) {
            $element['wizard_next']['#attributes']['data-webform-wizard-page'] = $track_next_page;
          }
        }
      }
      $element['#attached']['library'][] = 'webform/webform.form.wizard';
    }

    // Draft.
    if ($this->draftEnabled()) {
      $element['draft'] = [
        '#type' => 'submit',
        '#value' => $this->config('webform.settings')->get('settings.default_draft_button_label'),
        '#validate' => ['::draft'],
        '#submit' => ['::submitForm', '::save', '::rebuild'],
        '#attributes' => ['class' => ['webform-button--draft', 'js-webform-novalidate']],
        '#weight' => -10,
      ];
    }

    // Reset.
    if ($this->resetEnabled()) {
      $element['reset'] = [
        '#type' => 'submit',
        '#value' => $this->config('webform.settings')->get('settings.default_reset_button_label'),
        '#validate' => ['::noValidate'],
        '#submit' => ['::reset'],
        '#attributes' => ['class' => ['webform-button--reset', 'js-webform-novalidate']],
        '#weight' => 10,
      ];
    }

    uasort($element, ['Drupal\Component\Utility\SortArray', 'sortByWeightProperty']);

    return $element;
  }

  /**
   * Webform submission handler for the 'next' action.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function next(array &$form, FormStateInterface $form_state) {
    if ($form_state->getErrors()) {
      return;
    }
    $pages = $this->getPages($form, $form_state);
    $current_page = $this->getCurrentPage($form, $form_state);
    $form_state->set('current_page', $this->getNextPage($pages, $current_page));
    $this->wizardSubmit($form, $form_state);
  }

  /**
   * Webform submission handler for the 'previous' action.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function previous(array &$form, FormStateInterface $form_state) {
    $pages = $this->getPages($form, $form_state);
    $current_page = $this->getCurrentPage($form, $form_state);
    $form_state->set('current_page', $this->getPreviousPage($pages, $current_page));
    $this->wizardSubmit($form, $form_state);
  }

  /**
   * Webform submission handler for the wizard submit action.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function wizardSubmit(array &$form, FormStateInterface $form_state) {
    $current_page = $form_state->get('current_page');

    if ($current_page === 'webform_confirmation') {
      $this->complete($form, $form_state);
      $this->submitForm($form, $form_state);
      $this->save($form, $form_state);
      $this->confirmForm($form, $form_state);
    }
    elseif ($this->draftEnabled() && $this->getWebformSetting('draft_auto_save') && !$this->entity->isCompleted()) {
      $form_state->setValue('in_draft', TRUE);

      $this->submitForm($form, $form_state);
      $this->save($form, $form_state);
      $this->rebuild($form, $form_state);
    }
    else {
      $this->submitForm($form, $form_state);
      $this->rebuild($form, $form_state);
    }
  }

  /**
   * Webform submission handler to autosave when there are validation errors.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function autosave(array &$form, FormStateInterface $form_state) {
    if ($form_state->hasAnyErrors()) {
      if ($this->draftEnabled() && $this->getWebformSetting('draft_auto_save') && !$this->entity->isCompleted()) {
        $form_state->setValue('in_draft', TRUE);

        $this->submitForm($form, $form_state);
        $this->save($form, $form_state);
        $this->rebuild($form, $form_state);
      }
    }
  }

  /**
   * Webform submission handler for the 'draft' action.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function draft(array &$form, FormStateInterface $form_state) {
    $form_state->clearErrors();
    $form_state->setValue('in_draft', TRUE);
    $form_state->set('draft_saved', TRUE);
    $this->entity->validate();
  }

  /**
   * Webform submission handler for the 'complete' action.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function complete(array &$form, FormStateInterface $form_state) {
    $form_state->setValue('in_draft', FALSE);
  }

  /**
   * Webform submission validation that does nothing but clear validation errors.
   *
   * This method is used by wizard/preview previous buttons and the reset button
   * to prevent all form validation errors from being displayed while still
   * allowing an element's #validate callback to be triggered.
   *
   * This callback is being used instead of adding
   * #limit_validation_errors = [] to the submit buttons because
   * #limit_validation_errors also ignores all form values set via an element's
   * #validate callback.
   *
   * More complex (web)form elements user #validate callbacks
   * to process and alter an element's submitted value. Element's that rely on
   * #validate to alter the submitted value include 'Password Confirm',
   * 'Email Confirm', 'Composite Elements', 'Other Elements', and more...
   *
   * If the #limit_validation_errors property is used within a multi-step wizard
   * form, previously submitted values will be corrupted.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see \Drupal\Core\Form\FormValidator::handleErrorsWithLimitedValidation
   * @see \Drupal\Core\Render\Element\PasswordConfirm::validatePasswordConfirm
   * @see \Drupal\webform\Element\WebformEmailConfirm
   * @see \Drupal\webform\Element\WebformOtherBase::validateWebformOther
   */
  public function noValidate(array &$form, FormStateInterface $form_state) {
    $form_state->clearErrors();
    $this->entity->validate();
  }

  /**
   * Webform submission handler for the 'rebuild' action.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function rebuild(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Build webform submission with validated and processed data.
    $this->entity = $this->buildEntity($form, $form_state);

    // Validate webform via webform handler.
    $this->getWebform()->invokeHandlers('validateForm', $form, $form_state, $this->entity);

    // Webform validate handlers (via form['#validate']) are not called when
    // #validate handlers are attached to the trigger element
    // (i.e. submit button), so we need to manually call $form['validate']
    // handlers to support the modules that use form['#validate'] like the
    // validators.module.
    // @see \Drupal\webform\WebformSubmissionForm::actions
    // @see \Drupal\Core\Form\FormBuilder::doBuildForm
    $trigger_element = $form_state->getTriggeringElement();
    if (isset($trigger_element['#validate'])) {
      $handlers = array_filter($form['#validate'], function ($callback) {
        // Remove ::validateForm to prevent a recursion.
        return (is_array($callback) || $callback != '::validateForm');
      });
      // @see \Drupal\Core\Form\FormValidator::executeValidateHandlers
      foreach ($handlers as $callback) {
        call_user_func_array($form_state->prepareCallback($callback), [&$form, &$form_state]);
      }
    }

    // Server side #states API validation.
    $this->conditionsValidator->validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    // Submit webform via webform handler.
    $this->getWebform()->invokeHandlers('submitForm', $form, $form_state, $this->entity);
  }

  /**
   * Webform confirm(ation) handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function confirmForm(array &$form, FormStateInterface $form_state) {
    $this->setConfirmation($form_state);

    /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
    $webform_submission = $this->getEntity();

    // Confirm webform via webform handler.
    $this->getWebform()->invokeHandlers('confirmForm', $form, $form_state, $webform_submission);

    // Reset the form if reloading the current form via AJAX, and just displaying a message.
    $confirmation_type = $this->getWebformSetting('confirmation_type');
    if ($this->isAjax()) {
      $state = $webform_submission->getState();
      if ($confirmation_type == WebformInterface::CONFIRMATION_MESSAGE || $state == WebformSubmissionInterface::STATE_UPDATED) {
        static::reset($form, $form_state);
      }
    }

    // Always reset the form to trigger a modal dialog.
    if ($confirmation_type == WebformInterface::CONFIRMATION_MODAL) {
      static::reset($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $webform = $this->getWebform();
    /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
    $webform_submission = $this->getEntity();

    // Make sure the uri and remote addr are set correctly because
    // Ajax requests can cause these values to be reset.
    if ($webform_submission->isNew()) {
      if (preg_match('/\.webform\.test$/', $this->getRouteMatch()->getRouteName())) {
        // For test submissions use the source URL.
        $source_url = $webform_submission->set('uri', NULL)->getSourceUrl()->setAbsolute(FALSE);
        $uri = preg_replace('#^' . base_path() . '#', '/', $source_url->toString());
      }
      else {
        // For all other submissions, use the request URI.
        $uri = preg_replace('#^' . base_path() . '#', '/', $this->getRequest()->getRequestUri());
        // Remove Ajax query string parameters.
        $uri = preg_replace('/(ajax_form=1|_wrapper_format=drupal_ajax)(&|$)/', '', $uri);
        // Remove empty query string.
        $uri = preg_replace('/\?$/', '', $uri);
      }
      $remote_addr = ($this->isConfidential()) ? '' : $this->getRequest()->getClientIp();
      $webform_submission->set('uri', $uri);
      $webform_submission->set('remote_addr', $remote_addr);
    }

    // Block users from submitting templates that they can't update.
    if ($webform->isTemplate() && !$webform->access('update')) {
      return;
    }

    // Save and log webform submission.
    $webform_submission->save();

    // Check limits and invalidate cached and rebuild.
    if ($this->checkTotalLimit() || $this->checkUserLimit()) {
      Cache::invalidateTags(['webform:' . $this->getWebform()->id()]);
      $form_state->setRebuild();
    }
  }

  /**
   * Webform submission handler for the 'reset' action.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function reset(array &$form, FormStateInterface $form_state) {
    // Create new webform submission.
    /** @var \Drupal\webform\Entity\WebformSubmission $webform_submission */
    $webform_submission = $this->getEntity()->createDuplicate();
    $webform_submission->setData([]);
    $this->setEntity($webform_submission);

    // Reset user input but preserve form tokens.
    $form_state->setUserInput(array_intersect_key($form_state->getUserInput(), [
      'form_build_id' => 'form_build_id',
      'form_token' => 'form_token',
      'form_id' => 'form_id',
    ]));

    // Reset values.
    $form_state->setValues([]);

    // Reset current page.
    $storage = $form_state->getStorage();
    unset($storage['current_page']);
    $form_state->setStorage($storage);

    // Rebuild the form.
    $this->rebuild($form, $form_state);
  }

  /****************************************************************************/
  // Webform functions
  /****************************************************************************/

  /**
   * Set the webform properties from the elements.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param array $elements
   *   An associative array containing the elements.
   */
  protected function setFormPropertiesFromElements(array &$form, array &$elements) {
    foreach ($elements as $key => $value) {
      if (is_string($key) && $key[0] == '#') {
        if (isset($form[$key]) && is_array($form[$key]) && is_array($value)) {
          $form[$key] = NestedArray::mergeDeep($form[$key], $value);
        }
        else {
          $form[$key] = $value;
        }
        unset($elements[$key]);
      }
    }
    // Replace token in #attributes.
    if (isset($form['#attributes'])) {
      $form['#attributes'] = $this->tokenManager->replace($form['#attributes'], $this->getEntity());
    }
  }

  /****************************************************************************/
  // Wizard page functions
  /****************************************************************************/

  /**
   * Determine if this is a multistep wizard form.
   *
   * @return bool
   *   TRUE if this multistep wizard form.
   */
  protected function hasPages() {
    return $this->getWebform()->getPages($this->operation);
  }

  /**
   * Get visible wizard pages.
   *
   * Note: The array of pages is stored in the webform's state so that it can be
   * altered using hook_form_alter() and #validate callbacks.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Array of visible wizard pages.
   */
  protected function getPages(array &$form, FormStateInterface $form_state) {
    if ($form_state->get('pages') === NULL) {
      $pages = $this->getWebform()->getPages($this->operation);
      $form_state->set('pages', $pages);
    }

    // Get pages from form state.
    $pages = $form_state->get('pages');
    foreach ($pages as $page_key => &$page) {
      // Check #access which can set via form alter.
      if ($page['#access'] === FALSE) {
        unset($pages[$page_key]);
      }

      // Check #states (visible/hidden).
      elseif (!empty($page['#states'])) {
        $state = key($page['#states']);
        $conditions = $page['#states'][$state];
        $result = $this->conditionsValidator->validateConditions($conditions, $this->getEntity());
        $result = ($state === '!visible') ? !$result : $result;
        if (!$result) {
          unset($pages[$page_key]);
        }
      }
    }

    return $pages;
  }

  /**
   * Get the current page's key.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return string
   *   The current page's key.
   */
  protected function getCurrentPage(array &$form, FormStateInterface $form_state) {
    if ($form_state->get('current_page') === NULL) {
      $pages = $this->getWebform()->getPages($this->operation);
      if (empty($pages)) {
        $form_state->set('current_page', '');
      }
      else {
        $current_page = $this->entity->getCurrentPage();
        if ($current_page && isset($pages[$current_page]) && $this->draftEnabled()) {
          $form_state->set('current_page', $current_page);
        }
        else {
          $form_state->set('current_page', WebformArrayHelper::getFirstKey($pages));
        }
      }
    }
    return $form_state->get('current_page');
  }

  /**
   * Get first page's key.
   *
   * @param array $pages
   *   An associative array of visible wizard pages.
   *
   * @return null|string
   *   The first page's key.
   */
  protected function getFirstPage(array $pages) {
    return WebformArrayHelper::getFirstKey($pages);
  }

  /**
   * Get last page's key.
   *
   * @param array $pages
   *   An associative array of visible wizard pages.
   *
   * @return null|string
   *   The last page's key.
   */
  protected function getLastPage(array $pages) {
    return WebformArrayHelper::getLastKey($pages);
  }

  /**
   * Get next page's key.
   *
   * @param array $pages
   *   An associative array of visible wizard pages.
   * @param string $current_page
   *   The current page.
   *
   * @return null|string
   *   The next page's key. NULL if there is no next page.
   */
  protected function getNextPage(array $pages, $current_page) {
    return WebformArrayHelper::getNextKey($pages, $current_page);
  }

  /**
   * Get previous page's key.
   *
   * @param array $pages
   *   An associative array of visible wizard pages.
   * @param string $current_page
   *   The current page.
   *
   * @return null|string
   *   The previous page's key. NULL if there is no previous page.
   */
  protected function getPreviousPage(array $pages, $current_page) {
    return WebformArrayHelper::getPreviousKey($pages, $current_page);
  }

  /**
   * Set webform wizard current page.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function displayCurrentPage(array &$form, FormStateInterface $form_state) {
    $current_page = $this->getCurrentPage($form, $form_state);
    if ($current_page == 'webform_preview') {
      // Hide all elements except 'webform_actions'.
      foreach ($form['elements'] as $element_key => $element) {
        if (isset($element['#type']) && $element['#type'] == 'webform_actions') {
          continue;
        }
        $form['elements'][$element_key]['#access'] = FALSE;
      }

      // Display preview message.
      $this->getMessageManager()->display(WebformMessageManagerInterface::FORM_PREVIEW_MESSAGE, 'warning');

      // Build preview.
      $form['#title'] = PlainTextOutput::renderFromHtml($this->getWebformSetting('preview_title'));
      $form['preview'] = [
        '#theme' => 'webform_preview',
        '#webform_submission' => $this->entity,
        // Progress bar is -20.
        '#weight' => -10,
      ];
    }
    else {
      // Get all pages so that we can also hide skipped pages.
      $pages = $this->getWebform()->getPages($this->operation);
      foreach ($pages as $page_key => $page) {
        if (isset($form['elements'][$page_key])) {
          if ($page_key != $current_page) {
            $form['elements'][$page_key]['#access'] = FALSE;
            $this->hideElements($form['elements'][$page_key]);
          }
          else {
            $form['elements'][$page_key]['#type'] = 'container';
          }
        }
      }
    }
  }

  /****************************************************************************/
  // Webform state functions
  /****************************************************************************/

  /**
   * Set webform state to redirect to a trusted redirect response.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\Core\Url $url
   *   A URL object.
   */
  protected function setTrustedRedirectUrl(FormStateInterface $form_state, Url $url) {
    $form_state->setResponse(new TrustedRedirectResponse($url->setAbsolute()->toString()));
  }

  /**
   * Set webform state confirmation redirect and message.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function setConfirmation(FormStateInterface $form_state) {
    /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
    $webform_submission = $this->getEntity();
    $webform = $webform_submission->getWebform();

    // Get current route name, parameters, and options.
    $route_name = $this->getRouteMatch()->getRouteName();
    $route_parameters = $this->getRouteMatch()->getRawParameters()->all();
    $route_options = [];

    // Add current query to route options.
    $query = $this->getRequest()->query->all();
    // Remove Ajax parameters from query.
    unset($query['ajax_form'], $query['_wrapper_format']);
    if ($query) {
      $route_options['query'] = $query;
    }

    // Default to displaying a confirmation message on this page.
    $state = $webform_submission->getState();
    if ($state == WebformSubmissionInterface::STATE_UPDATED) {
      $this->getMessageManager()->display(WebformMessageManagerInterface::SUBMISSION_UPDATED);
      $form_state->setRedirect($route_name, $route_parameters, $route_options);
      return;
    }

    // Add token route query options.
    if ($state == WebformSubmissionInterface::STATE_COMPLETED) {
      $route_options['query']['token'] = $webform_submission->getToken();
    }

    // Handle 'page', 'url', and 'inline' confirmation types.
    $confirmation_type = $this->getWebformSetting('confirmation_type');
    switch ($confirmation_type) {
      case WebformInterface::CONFIRMATION_PAGE:
        $redirect_url = $this->requestHandler->getUrl($webform, $this->sourceEntity, 'webform.confirmation', $route_options);
        $form_state->setRedirectUrl($redirect_url);
        return;

      case WebformInterface::CONFIRMATION_URL:
      case WebformInterface::CONFIRMATION_URL_MESSAGE:
        $confirmation_url = trim($this->getWebformSetting('confirmation_url', ''));
        // Remove base path from root-relative URL.
        // Only applies for Drupal sites within a sub directory.
        $confirmation_url = preg_replace('/^' . preg_quote(base_path(), '/') . '/', '/', $confirmation_url);
        // Get system path.
        $confirmation_url = $this->aliasManager->getPathByAlias($confirmation_url);
        if ($redirect_url = $this->pathValidator->getUrlIfValid($confirmation_url)) {
          if ($confirmation_type == WebformInterface::CONFIRMATION_URL_MESSAGE) {
            $this->getMessageManager()->display(WebformMessageManagerInterface::SUBMISSION_CONFIRMATION);
          }
          $this->setTrustedRedirectUrl($form_state, $redirect_url);
          return;
        }
        else {
          $t_args = [
            '@webform' => $webform->label(),
            '%url' => $this->getWebformSetting('confirmation_url'),
          ];
          // Display warning to use who can update the webform.
          if ($webform->access('update')) {
            drupal_set_message($this->t('Confirmation URL %url is not valid.', $t_args), 'warning');
          }
          // Log warning.
          $this->getLogger('webform')->warning('@webform: Confirmation URL %url is not valid.', $t_args);
        }

        // If confirmation URL is invalid display message.
        $this->getMessageManager()->display(WebformMessageManagerInterface::SUBMISSION_CONFIRMATION);
        $route_options['query']['webform_id'] = $webform->id();
        $form_state->setRedirect($route_name, $route_parameters, $route_options);
        return;

      case WebformInterface::CONFIRMATION_INLINE:
        $form_state->set('current_page', 'webform_confirmation');
        $form_state->setRebuild();
        return;

      case WebformInterface::CONFIRMATION_MESSAGE:
        $this->getMessageManager()->display(WebformMessageManagerInterface::SUBMISSION_CONFIRMATION);
        return;

      case WebformInterface::CONFIRMATION_MODAL:
        // Set webform confirmation modal in $form_state.
        $form_state->set('webform_confirmation_modal', [
          'title' => $this->getWebformSetting('confirmation_title', ''),
          'content' => $this->getMessageManager()->build(WebformMessageManagerInterface::SUBMISSION_CONFIRMATION),
        ]);
        return;

      case WebformInterface::CONFIRMATION_DEFAULT:
      default:
        $this->getMessageManager()->display(WebformMessageManagerInterface::SUBMISSION_DEFAULT_CONFIRMATION);
        return;
    }

  }

  /****************************************************************************/
  // Elements functions
  /****************************************************************************/

  /**
   * Hide webform elements by settings their #access to FALSE.
   *
   * @param array $elements
   *   An render array representing elements.
   */
  protected function hideElements(array &$elements) {
    foreach ($elements as $key => &$element) {
      if (Element::property($key) || !is_array($element)) {
        continue;
      }

      // Set #access to FALSE which will suppresses webform #required validation.
      $element['#access'] = FALSE;

      $this->hideElements($element);
    }
  }

  /**
   * Prepare webform elements.
   *
   * @param array $elements
   *   An render array representing elements.
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function prepareElements(array &$elements, array &$form, FormStateInterface $form_state) {
    foreach ($elements as $key => &$element) {
      if (Element::property($key) || !is_array($element)) {
        continue;
      }

      // Invoke WebformElement::prepare.
      $this->elementManager->invokeMethod('prepare', $element, $this->entity);

      // Invoke WebformElement::setDefaultValue.
      $this->elementManager->invokeMethod('setDefaultValue', $element);

      // Invoke WebformElement::finalize.
      $this->elementManager->invokeMethod('finalize', $element, $this->entity);

      // Allow modules to alter the webform element.
      // @see \Drupal\Core\Field\WidgetBase::formSingleElement()
      $hooks = ['webform_element'];
      if (!empty($element['#type'])) {
        $hooks[] = 'webform_element_' . $element['#type'];
      }
      $context = ['webform' => $form];
      $this->moduleHandler->alter($hooks, $element, $form_state, $context);

      // Recurse and prepare nested elements.
      $this->prepareElements($element, $form, $form_state);
    }
  }

  /**
   * Prepopulate element data.
   *
   * @param array $data
   *   An array of default.
   */
  protected function prepopulateData(array &$data) {
    if ($this->getWebformSetting('form_prepopulate')) {
      $data += $this->getRequest()->query->all();
    }
  }

  /**
   * Populate webform elements.
   *
   * @param array $elements
   *   An render array representing elements.
   * @param array $values
   *   An array of values used to populate the elements.
   */
  protected function populateElements(array &$elements, array $values) {
    foreach ($elements as $key => &$element) {
      if (Element::property($key) || !is_array($element)) {
        continue;
      }

      // Populate element if value exists.
      if (isset($element['#type']) && isset($values[$key])) {
        $element['#default_value'] = $values[$key];
        if ($this->operation == 'api') {
          $element['#needs_validation'] = TRUE;
        }
      }

      $this->populateElements($element, $values);
    }
  }

  /****************************************************************************/
  // Account related functions
  /****************************************************************************/

  /**
   * Check webform submission total limits.
   *
   * @return bool
   *   TRUE if webform submission total limit have been met.
   */
  protected function checkTotalLimit() {
    $webform = $this->getWebform();

    // Check per source entity total limit.
    $entity_limit_total = $this->getWebformSetting('entity_limit_total');
    if ($entity_limit_total && ($source_entity = $this->getLimitSourceEntity())) {
      if ($this->storage->getTotal($webform, $source_entity) >= $entity_limit_total) {
        return TRUE;
      }
    }

    // Check total limit.
    $limit_total = $this->getWebformSetting('limit_total');
    if ($limit_total && $this->storage->getTotal($webform) >= $limit_total) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Check webform submission user limit.
   *
   * @return bool
   *   TRUE if webform submission user limit have been met.
   */
  protected function checkUserLimit() {
    // Allow anonymous and authenticated users edit own submission.
    /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
    $webform_submission = $this->getEntity();
    if ($webform_submission->id()) {
      if ($this->currentUser()->isAnonymous()) {
        if (!empty($_SESSION['webform_submissions']) && in_array($webform_submission->id(), $_SESSION['webform_submissions'])) {
          return FALSE;
        }
      }
      else  {
        if ($webform_submission->getOwnerId() === $this->currentUser()->id()) {
          return FALSE;
        }
      }
    }

    // Get the submission owner and not current user.
    // This takes into account when an API submission changes the owner id.
    // @see \Drupal\webform\WebformSubmissionForm::submitValues
    $account = $this->entity->getOwner();
    $webform = $this->getWebform();


    // Check per source entity user limit.
    $entity_limit_user = $this->getWebformSetting('entity_limit_user');
    if ($entity_limit_user && ($source_entity = $this->getLimitSourceEntity())) {
      if ($this->storage->getTotal($webform, $source_entity, $account) >= $entity_limit_user) {
        return TRUE;
      }
    }

    // Check user limit.
    $limit_user = $this->getWebformSetting('limit_user');
    if ($limit_user && $this->storage->getTotal($webform, NULL, $account) >= $limit_user) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Determine if drafts are enabled.
   *
   * @return bool
   *   TRUE if drafts are enabled.
   */
  protected function draftEnabled() {
    // Can't saved drafts when saving results is disabled.
    if ($this->getWebformSetting('results_disabled')) {
      return FALSE;
    }

    /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
    $webform_submission = $this->getEntity();

    // Once a form is completed drafts are no longer applicable.
    if ($webform_submission->isCompleted()) {
      return FALSE;
    }

    switch ($this->getWebformSetting('draft')) {
      case WebformInterface::DRAFT_ALL:
        return TRUE;

      case WebformInterface::DRAFT_AUTHENTICATED:
        return $webform_submission->getOwner()->isAuthenticated();

      case WebformInterface::DRAFT_NONE:
      default:
        return FALSE;
    }
  }

  /**
   * Determine if reset is enabled.
   *
   * @return bool
   *   TRUE if reset is enabled.
   */
  protected function resetEnabled() {
    return $this->getWebformSetting('form_reset', FALSE);
  }

  /**
   * Returns the webform confidential indicator.
   *
   * @return bool
   *   TRUE if the webform is confidential.
   */
  protected function isConfidential() {
    return $this->getWebformSetting('form_confidential', FALSE);
  }

  /**
   * Is client side validation disabled (using the webform novalidate attribute).
   *
   * @return bool
   *   TRUE if the client side validation disabled.
   */
  protected function isFormNoValidate() {
    return $this->getWebformSetting('form_novalidate', FALSE);
  }

  /**
   * Is the webform being initially loaded via GET method.
   *
   * @return bool
   *   TRUE if the webform is being initially loaded via GET method.
   */
  protected function isGet() {
    return ($this->getRequest()->getMethod() == 'GET') ? TRUE : FALSE;
  }

  /**
   * Determine if the current request is a specific route (name).
   *
   * @param string $route_name
   *   A route name.
   *
   * @return bool
   *   TRUE if the current request is a specific route (name).
   */
  protected function isRoute($route_name) {
    return ($this->requestHandler->getRouteName($this->getEntity(), $this->getSourceEntity(), $route_name) == $this->getRouteMatch()->getRouteName()) ? TRUE : FALSE;
  }

  /**
   * Is the current webform an entity reference from the source entity.
   *
   * @return bool
   *   TRUE is the current webform an entity reference from the source entity.
   */
  protected function isWebformEntityReferenceFromSourceEntity() {
    if (!$this->sourceEntity) {
      return FALSE;
    }

    $webform = $this->webformEntityReferenceManager->getWebform($this->sourceEntity);
    if (!$webform) {
      return FALSE;
    }

    return ($webform->id() == $this->getWebform()->id()) ? TRUE : FALSE;
  }

  /****************************************************************************/
  // Helper functions
  /****************************************************************************/

  /**
   * Get the message manager.
   *
   * We need to wrap the message manager service because the webform submission
   * entity is being continuous cloned and updated during form processing.
   *
   * @see \Drupal\Core\Entity\EntityForm::buildEntity
   */
  protected function getMessageManager() {
    $this->messageManager->setWebformSubmission($this->getEntity());
    return $this->messageManager;
  }

  /**
   * Get the webform submission's webform.
   *
   * @return \Drupal\webform\WebformInterface
   *   A webform.
   */
  protected function getWebform() {
    /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
    $webform_submission = $this->getEntity();
    return $webform_submission->getWebform();
  }

  /**
   * Get the webform submission's source entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The webform submission's source entity.
   */
  protected function getSourceEntity() {
    return $this->sourceEntity;
  }

  /**
   * Get source entity for use with entity limit total and user submissions.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The webform submission's source entity.
   */
  protected function getLimitSourceEntity() {
    /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
    $webform_submission = $this->getEntity();

    $source_entity = $webform_submission->getSourceEntity();
    if ($source_entity && $source_entity->getEntityTypeId() != 'webform') {
      return $source_entity;
    }
    return NULL;
  }

  /**
   * Get a webform submission's webform setting.
   *
   * @param string $name
   *   Setting name.
   * @param null|mixed $default_value
   *   Default value.
   *
   * @return mixed
   *   A webform setting.
   */
  protected function getWebformSetting($name, $default_value = NULL) {
    // Get webform settings with default values.
    if (empty($this->settings)) {
      $this->settings = $this->getWebform()->getSettings();
      $default_settings = $this->config('webform.settings')->get('settings');
      foreach ($default_settings as $key => $value) {
        $key = str_replace('default_', '', $key);
        if (empty($this->settings[$key])) {
          $this->settings[$key] = $value;
        }
      }
    }

    if (isset($this->settings[$name])) {
      return $this->tokenManager->replace($this->settings[$name], $this->getEntity());
    }
    else {
      return $default_value;
    }
  }

  /****************************************************************************/
  // Ajax functions.
  // @see \Drupal\webform\Form\WebformAjaxFormTrait
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  protected function isAjax() {
    return $this->getWebformSetting('ajax', FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function cancelAjaxForm(array &$form, FormStateInterface $form_state) {
    throw new \Exception('Webform submission Ajax form should never be cancelled. Only ::reset should be called.');
  }

  /****************************************************************************/
  // API helper functions.
  /****************************************************************************/

  /**
   * Programmatically check that a webform is open to new submissions.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   *
   * @return array|boolean
   *   Return TRUE if the webform is open to new submissions else returns
   *   an error message.
   *
   * @see \Drupal\webform\WebformSubmissionForm::getCustomForm
   */
  public static function isOpen(WebformInterface $webform) {
    $webform_submission = WebformSubmission::create(['webform_id' => $webform->id()]);

    /** @var \Drupal\webform\WebformSubmissionForm $form_object */
    $form_object = \Drupal::entityTypeManager()->getFormObject('webform_submission', 'add');
    $form_object->setEntity($webform_submission);

    /** @var \Drupal\webform\WebformMessageManagerInterface $message_manager */
    $message_manager = \Drupal::service('webform.message_manager');
    $message_manager->setWebformSubmission($webform_submission);

    // Check form is open.
    if ($webform->isClosed()) {
      if ($webform->isOpening()) {
        return $message_manager->get(WebformMessageManagerInterface::FORM_OPEN_MESSAGE);
      }
      else {
        return $message_manager->get(WebformMessageManagerInterface::FORM_CLOSE_MESSAGE);
      }
    }

    // Check total limit.
    if ($form_object->checkTotalLimit()) {
      return $message_manager->get(WebformMessageManagerInterface::LIMIT_TOTAL_MESSAGE);
    }

    // Check user limit.
    if ($form_object->checkUserLimit()) {
      return $message_manager->get(WebformMessageManagerInterface::LIMIT_USER_MESSAGE);
    }

    return TRUE;
  }

  /**
   * Programmatically validate values and submit a webform submission.
   *
   * @param array $values
   *   An array of submission values and data.
   *
   * @return array|\Drupal\Core\Entity\EntityInterface|null
   *   An array of error messages if validation fails or
   *   A webform submission is there are no validation errors.
   */
  public static function validateValues(array $values) {
    return static::submitValues($values, TRUE);
  }

  /**
   * Programmatically validate values and submit a webform submission.
   *
   * @param array $values
   *   An array of submission values and data.
   * @param bool $validate_only
   *   Flag to trigger only webform validation.
   *
   * @return array|\Drupal\Core\Entity\EntityInterface|null
   *   An array of error messages if validation fails or
   *   A webform submission is there are no validation errors.
   */
  public static function submitValues(array $values, $validate_only = FALSE) {
    $webform_submission = WebformSubmission::create($values);

    /** @var \Drupal\webform\WebformSubmissionForm $form_object */
    $form_object = \Drupal::entityTypeManager()->getFormObject('webform_submission', 'api');
    $form_object->setEntity($webform_submission);

    // Create an empty form state which will be populated when the submission
    // form is submitted.
    $form_state = new FormState();

    // Submit the form.
    \Drupal::formBuilder()->submitForm($form_object, $form_state);

    // Get the errors but skip drafts.
    $errors = ($webform_submission->isDraft() && !$validate_only) ? [] : $form_state->getErrors();

    if ($errors) {
      return $errors;
    }
    elseif ($validate_only) {
      return NULL;
    }
    else {
      $webform_submission->save();
      return $webform_submission;
    }
  }

}
