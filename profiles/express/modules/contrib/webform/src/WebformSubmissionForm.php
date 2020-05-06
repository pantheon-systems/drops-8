<?php

namespace Drupal\webform;

use Drupal\Component\Render\PlainTextOutput;
use Drupal\Component\Utility\Bytes;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\Form\WebformDialogFormTrait;
use Drupal\webform\Plugin\WebformElement\Hidden;
use Drupal\webform\Plugin\WebformElement\OptionsBase;
use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;
use Drupal\webform\Plugin\WebformElementEntityReferenceInterface;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Drupal\webform\Plugin\WebformElementOtherInterface;
use Drupal\webform\Plugin\WebformElementWizardPageInterface;
use Drupal\webform\Plugin\WebformHandlerInterface;
use Drupal\webform\Plugin\WebformSourceEntityManager;
use Drupal\webform\Utility\WebformArrayHelper;
use Drupal\webform\Utility\WebformDialogHelper;
use Drupal\webform\Utility\WebformElementHelper;
use Drupal\webform\Utility\WebformOptionsHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a webform to collect and edit submissions.
 */
class WebformSubmissionForm extends ContentEntityForm {

  use WebformDialogFormTrait;

  /**
   * The configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

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
   * @var \Drupal\webform\WebformSubmissionConditionsValidatorInterface
   */
  protected $conditionsValidator;

  /**
   * The webform entity reference manager.
   *
   * @var \Drupal\webform\WebformEntityReferenceManagerInterface
   */
  protected $webformEntityReferenceManager;

  /**
   * The webform submission generation service.
   *
   * @var \Drupal\webform\WebformSubmissionGenerateInterface
   */
  protected $generate;

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
   * States API prefix.
   *
   * @var string
   */
  protected $statesPrefix;

  /**
   * The kill switch.
   *
   * @var \Drupal\Core\PageCache\ResponsePolicy\KillSwitch
   */
  protected $killSwitch;

  /**
   * Selection Plugin Manager service.
   *
   * @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface
   */
  protected $selectionManager;

  /**
   * Stores the original submission data passed via the EntityFormBuilder.
   *
   * @var array
   *
   * @see \Drupal\webform\WebformSubmissionForm::setEntity
   */
  protected $originalData;

  /**
   * Constructs a WebformSubmissionForm object.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
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
   * @param \Drupal\webform\WebformSubmissionConditionsValidatorInterface $conditions_validator
   *   The webform submission conditions (#states) validator.
   * @param \Drupal\webform\WebformEntityReferenceManagerInterface $webform_entity_reference_manager
   *   The webform entity reference manager.
   * @param \Drupal\webform\WebformSubmissionGenerateInterface $submission_generate
   *   The webform submission generation service.
   * @param \Drupal\Core\PageCache\ResponsePolicy\KillSwitch $killSwitch
   *   The page cache kill switch service.
   * @param \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface $selection_manager
   *   The selection plugin manager.
   */
  public function __construct(
    EntityRepositoryInterface $entity_repository,
    ConfigFactoryInterface $config_factory,
    RendererInterface $renderer,
    AliasManagerInterface $alias_manager,
    PathValidatorInterface $path_validator,
    WebformRequestInterface $request_handler,
    WebformElementManagerInterface $element_manager,
    WebformThirdPartySettingsManagerInterface $third_party_settings_manager,
    WebformMessageManagerInterface $message_manager,
    WebformTokenManagerInterface $token_manager,
    WebformSubmissionConditionsValidatorInterface $conditions_validator,
    WebformEntityReferenceManagerInterface $webform_entity_reference_manager,
    WebformSubmissionGenerateInterface $submission_generate,
    KillSwitch $killSwitch,
    SelectionPluginManagerInterface $selection_manager
  ) {
    parent::__construct($entity_repository);
    $this->configFactory = $config_factory;
    $this->renderer = $renderer;
    $this->requestHandler = $request_handler;
    $this->aliasManager = $alias_manager;
    $this->pathValidator = $path_validator;
    $this->elementManager = $element_manager;
    $this->thirdPartySettingsManager = $third_party_settings_manager;
    $this->messageManager = $message_manager;
    $this->tokenManager = $token_manager;
    $this->conditionsValidator = $conditions_validator;
    $this->webformEntityReferenceManager = $webform_entity_reference_manager;
    $this->generate = $submission_generate;
    $this->killSwitch = $killSwitch;
    $this->selectionManager = $selection_manager;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('config.factory'),
      $container->get('renderer'),
      $container->get('path.alias_manager'),
      $container->get('path.validator'),
      $container->get('webform.request'),
      $container->get('plugin.manager.webform.element'),
      $container->get('webform.third_party_settings_manager'),
      $container->get('webform.message_manager'),
      $container->get('webform.token_manager'),
      $container->get('webform_submission.conditions_validator'),
      $container->get('webform.entity_reference_manager'),
      $container->get('webform_submission.generate'),
      $container->get('page_cache_kill_switch'),
      $container->get('plugin.manager.entity_reference_selection')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFormId() {
    $base_form_id = $this->entity->getEntityTypeId();
    $base_form_id .= '_' . $this->entity->bundle();
    return $base_form_id . '_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    $form_id = $this->entity->getEntityTypeId();
    $form_id .= '_' . $this->entity->bundle();
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
  protected function getWrapperId() {
    // Get the form id with the source entity but without the operation.
    $form_id = $this->entity->getEntityTypeId();
    $form_id .= '_' . $this->entity->bundle();
    if ($source_entity = $this->entity->getSourceEntity()) {
      $form_id .= '_' . $source_entity->getEntityTypeId() . '_' . $source_entity->id();
    }
    $form_id .= '_form';
    return Html::getId($form_id . '-ajax');
  }

  /**
   * {@inheritdoc}
   *
   * This is the best place to override an entity form's default settings
   * because it is called immediately after the form object is initialized.
   *
   * @see \Drupal\Core\Entity\EntityFormBuilder::getForm
   */
  public function setEntity(EntityInterface $entity) {
    /** @var \Drupal\webform\WebformSubmissionInterface $entity */
    $webform = $entity->getWebform();

    // Initialize the webform submission entity by getting it default data
    // and storing its original data.
    if (!isset($this->originalData)) {
      // Store the original data passed via the EntityFormBuilder.
      // This allows us to reset the submission to it's original state
      // via ::reset.
      // @see \Drupal\Core\Entity\EntityFormBuilder::getForm
      // @see \Drupal\webform\Entity\Webform::getSubmissionForm
      // @see \Drupal\webform\WebformSubmissionForm::reset
      $this->originalData = $entity->getRawData();
    }

    // Get the submission data and only call WebformSubmission::setData once.
    $data = $entity->getRawData();

    // If ?_webform_test is defined for the current webform, override
    // the 'add' operation with 'test' operation.
    if ($this->operation === 'add' &&
      $this->getRequest()->query->get('_webform_test') === $webform->id() &&
      $webform->access('test')
    ) {
      $this->operation = 'test';
    }

    // Generate test data.
    if ($this->operation === 'test'
      && $webform->access('test')) {
      $webform->applyVariants($entity);
      $data = $webform->getVariantsData($entity)
        + $this->generate->getData($webform);
    }

    // Get the source entity and allow webform submission to be used as a source
    // entity.
    $source_entity = $entity->getSourceEntity() ?: $this->requestHandler->getCurrentSourceEntity(['webform']);
    if ($source_entity === $entity) {
      $source_entity = $this->requestHandler->getCurrentSourceEntity(['webform', 'webform_submission']);
    }
    // Handle paragraph sourc entity.
    if ($source_entity && $source_entity->getEntityTypeId() === 'paragraph') {
      // Disable :clear suffix to prevent webform tokens from being removed.
      $data = $this->tokenManager->replace($data, $source_entity, [], ['suffixes' => ['clear' => FALSE]]);
      $source_entity = WebformSourceEntityManager::getMainSourceEntity($source_entity);
    }
    // Set source entity.
    $this->sourceEntity = $source_entity;

    // Get account.
    $account = $this->currentUser();

    // Load entity from token or saved draft when not editing or testing
    // submission form.
    if (!in_array($this->operation, ['edit', 'edit_all', 'test'])) {
      $token = $this->getRequest()->query->get('token');
      $webform_submission_token = $this->getStorage()->loadFromToken($token, $webform, $source_entity);
      if ($webform_submission_token) {
        $entity = $webform_submission_token;
        $data = $entity->getRawData();
        $this->operation = 'edit';
      }
      elseif ($webform->getSetting('draft') != WebformInterface::DRAFT_NONE) {
        if ($webform->getSetting('draft_multiple')) {
          // Allow multiple drafts to be restored using token.
          // This allows the webform's public facing URL to be used instead of
          // the admin URL of the webform.
          $webform_submission_token = $this->getStorage()->loadFromToken($token, $webform, $source_entity, $account);
          if ($webform_submission_token && $webform_submission_token->isDraft()) {
            $entity = $webform_submission_token;
            $data = $entity->getRawData();
          }
        }
        elseif ($webform_submission_draft = $this->getStorage()->loadDraft($webform, $source_entity, $account)) {
          // Else load the most recent draft.
          $entity = $webform_submission_draft;
          $data = $entity->getRawData();
        }
      }
    }

    // Set entity before calling get last submission.
    $this->entity = $entity;

    if ($entity->isNew()) {
      $last_submission = NULL;
      if ($webform->getSetting('limit_total_unique')) {
        // Require user to have update any submission access.
        if (!$webform->access('submission_view_any')
          || !$webform->access('submission_update_any')) {
          throw new AccessDeniedHttpException();
        }
        // Get last webform/source entity submission.
        $last_submission = $this->getStorage()->getLastSubmission($webform, $source_entity, NULL, ['in_draft' => FALSE]);
      }
      elseif ($webform->getSetting('limit_user_unique')) {
        // Require user to be authenticated to access a unique submission.
        if (!$account->isAuthenticated()) {
          throw new AccessDeniedHttpException();
        }
        // Require user to have update own submission access.
        if (!$webform->access('submission_view_own')
          || !$webform->access('submission_update_own')) {
          throw new AccessDeniedHttpException();
        }
        // Get last user submission.
        $last_submission = $this->getStorage()->getLastSubmission($webform, $source_entity, $account, ['in_draft' => FALSE]);
      }

      // Set last submission and switch to the edit operation.
      if ($last_submission) {
        $entity = $last_submission;
        $data = $entity->getRawData();
        $this->operation = 'edit';
      }
    }

    // Autofill with previous submission.
    if ($this->operation === 'add'
      && $entity->isNew()
      && $webform->getSetting('autofill')) {
      if ($last_submission = $this->getLastSubmission()) {
        $excluded_elements = $webform->getSetting('autofill_excluded_elements') ?: [];
        $last_submission_data = array_diff_key($last_submission->getRawData(), $excluded_elements);
        $data = $last_submission_data + $data;
      }
    }

    // Get default data and append it to the submission's data.
    // This allows computed elements to be executed and tokens
    // to be replaced using the webform's default data.
    $default_data = $webform->getElementsDefaultData();
    $default_data = $this->tokenManager->replaceNoRenderContext($default_data, $entity);
    $data += $default_data;

    // Set data and calculate computed values.
    $entity->setData($data);

    // Override settings.
    $this->overrideSettings($entity);

    // Set the webform's current operation.
    $webform->setOperation($this->operation);

    return parent::setEntity($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webform\WebformSubmissionInterface $entity */
    $entity = parent::buildEntity($form, $form_state);

    // Override settings.
    $this->overrideSettings($entity);

    return $entity;
  }

  /**
   * Override webform settings for the webform submission.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   */
  protected function overrideSettings(WebformSubmissionInterface $webform_submission) {
    $webform = $webform_submission->getWebform();

    // Invoke override settings which resets the webform settings.
    $webform->invokeHandlers('overrideSettings', $webform_submission);

    // Look for ?_webform_dialog=1 which enables Ajax support when this form is
    // opened in dialog.
    // @see webform.dialog.js
    //
    // Must be called after WebformHandler::overrideSettings which resets all
    // overridden settings.
    // @see \Drupal\webform\Entity\Webform::invokeHandlers
    if ($this->getRequest()->query->get('_webform_dialog') && !$webform->getSetting('ajax')) {
      $webform->setSettingOverride('ajax', TRUE);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    // NOTE: We are not copying form values to the entity because
    // webform element keys can override webform submission properties.
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

    // Set in draft.
    $in_draft = $form_state->get('in_draft');
    if ($in_draft !== NULL) {
      $entity->set('in_draft', $in_draft);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $webform_submission \Drupal\webform\WebformSubmissionInterface */
    $webform_submission = $this->getEntity();
    $webform = $this->getWebform();

    // Only prepopulate data when a webform is initially loaded.
    if (!$form_state->isRebuilding()) {
      $data = $webform_submission->getData();
      $this->prepopulateData($data);
      $webform_submission->setData($data);
    }

    // Apply variants.
    $webform->applyVariants($webform_submission);

    // All anonymous submissions are tracked in the $_SESSION.
    // @see \Drupal\webform\WebformSubmissionStorage::setAnonymousSubmission
    if ($this->currentUser()->isAnonymous()) {
      $form['#cache']['contexts'][] = 'session';
    }

    // Add the webform as a cacheable dependency.
    $this->renderer->addCacheableDependency($form, $webform);

    // Kill page cache for scheduled webforms.
    // @todo Remove once bubbling of element's max-age to page cache is fixed.
    // @see https://www.drupal.org/project/webform/issues/3015760
    // @see https://www.drupal.org/project/drupal/issues/2352009
    if ($webform->isScheduled()
      && $this->currentUser()->isAnonymous()
      && $this->moduleHandler->moduleExists('page_cache')) {
      $this->killSwitch->trigger();
    }

    // Display status messages.
    $this->displayMessages($form, $form_state);

    // Build the webform.
    $form = parent::buildForm($form, $form_state);

    // Ajax: Scroll to.
    // @see \Drupal\webform\Form\WebformAjaxFormTrait::submitAjaxForm
    if ($this->isAjax()) {
      $form['#webform_ajax_scroll_top'] = $this->getWebformSetting('ajax_scroll_top');
    }

    // Alter element's form.
    if (isset($form['elements']) && is_array($form['elements'])) {
      $elements = $form['elements'];
      $this->alterElementsForm($elements, $form, $form_state);
    }

    // Add Ajax callbacks.
    $ajax_settings = [
      'effect' => $this->getWebformSetting('ajax_effect'),
      'speed' => (int) $this->getWebformSetting('ajax_speed'),
      'progress' => [
        'type' => $this->getWebformSetting('ajax_progress_type'),
        'message' => '',
      ],
    ];
    $form = $this->buildAjaxForm($form, $form_state, $ajax_settings);

    // Alter webform via webform handler.
    $this->getWebform()->invokeHandlers('alterForm', $form, $form_state, $webform_submission);

    // Call custom webform alter hook.
    $form_id = $this->getFormId();
    $this->thirdPartySettingsManager->alter('webform_submission_form', $form, $form_state, $form_id);

    // Server side #states API validation.
    $this->conditionsValidator->buildForm($form, $form_state);

    return $form;
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
    //
    // The data parameter is append to the URL after the form has
    // been submitted.
    //
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

    // Disable the default $form['#theme'] templates.
    // If the webform's id begins with an underscore the #theme
    // was automatically being set to 'webform_submission__WEBFORM_ID', this
    // causes the form to be rendered using the 'webform_submission' template.
    // @see \Drupal\Core\Form\FormBuilder::prepareForm
    // @see webform-submission-form.html.twig
    $form['#theme'] = ['webform_submission_form'];

    // Define very specific webform classes, this override the form's
    // default classes.
    // @see \Drupal\Core\Form\FormBuilder::retrieveForm
    $webform_id = Html::cleanCssIdentifier($webform->id());
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

    // Get last class, which is the most specific, as #states prefix.
    // @see \Drupal\webform\WebformSubmissionForm::addStatesPrefix
    $this->statesPrefix = '.' . end($class);

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
        '#type' => 'webform_submission_navigation',
        '#webform_submission' => $webform_submission,
        '#weight' => -20,
      ];
      $form['information'] = [
        '#type' => 'webform_submission_information',
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
        '#attached' => ['library' => ['webform/webform.confirmation.modal']],
        '#element_validate' => ['::removeConfirmationModal'],
      ];
    }

    /* Data */

    // Get and prepopulate (via query string) submission data.
    $data = $webform_submission->getData();

    /* Elements */

    // Get webform elements.
    $elements = $webform_submission->getWebform()->getElementsInitialized();

    // Populate webform elements with webform submission data.
    $this->populateElements($elements, $data);

    // Prepare webform elements.
    $this->prepareElements($elements, $form, $form_state);

    // Add wizard progress tracker and page links to the webform.
    $pages = $webform->getPages($this->operation);
    if ($pages) {
      $current_page = $this->getCurrentPage($form, $form_state);

      // Add hidden pages submit actions.
      $form['pages'] = $this->pagesElement($form, $form_state);

      // Add progress tracker.
      $display_wizard_progress = ($this->getWebformSetting('wizard_progress_bar') || $this->getWebformSetting('wizard_progress_pages') || $this->getWebformSetting('wizard_progress_percentage'));
      if ($current_page && $display_wizard_progress) {
        $form['progress'] = [
          '#theme' => 'webform_progress',
          '#webform' => $this->getWebform(),
          '#webform_submission' => $webform_submission,
          '#current_page' => $current_page,
          '#operation' => $this->operation,
          '#weight' => -20,
        ];
      }
    }

    // Required indicator.
    $current_page = $this->getCurrentPage($form, $form_state);
    if ($current_page != 'webform_preview' && $this->getWebformSetting('form_required') && $webform->hasRequired()) {
      $form['required'] = [
        '#theme' => 'webform_required',
        '#label' => $this->getWebformSetting('form_required_label'),
      ];
    }

    // Append elements to the webform.
    $form['elements'] = $elements;

    // Pages: Set current wizard or preview page.
    $this->displayCurrentPage($form, $form_state);

    // Move all $elements properties to the $form.
    $this->setFormPropertiesFromElements($form, $elements);

    // Attach libraries to the form.
    $this->attachLibraries($form, $form_state);

    // Attach behaviors to the form.
    $this->attachBehaviors($form, $form_state);

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
      return $this->getMessageManager()->append($form, WebformMessageManagerInterface::FORM_EXCEPTION_MESSAGE, 'warning');
    }

    // Exit if submission is locked.
    if ($webform_submission->isLocked()) {
      return $this->getMessageManager()->append($form, WebformMessageManagerInterface::SUBMISSION_LOCKED_MESSAGE, 'warning');
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
      $form['actions'] = ['#type' => 'actions'];
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
    if (($webform_submission->isNew() || $webform_submission->isDraft()) && $webform->isClosed()) {
      // If the current user can update any submission just display the closed
      // message and still allow them to create new submissions.
      if ($webform->isTemplate() && $webform->access('duplicate') && !$webform->isArchived()) {
        if (!$this->isDialog()) {
          $this->getMessageManager()->display(WebformMessageManagerInterface::TEMPLATE_PREVIEW, 'warning');
        }
      }
      elseif ($webform->access('submission_update_any')) {
        $form = $this->getMessageManager()->append($form, $webform->isArchived() ? WebformMessageManagerInterface::ADMIN_ARCHIVED : WebformMessageManagerInterface::ADMIN_CLOSED, 'info');
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
    if ($this->isConfidential()
      && $this->currentUser()->isAuthenticated()
      && $this->entity->isNew()
      && $this->operation === 'add') {
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
        return $this->getMessageManager()->append($form, WebformMessageManagerInterface::FORM_EXCEPTION_MESSAGE, 'warning');
      }
    }

    // Check total limit.
    if ($this->checkTotalLimit() && empty($this->getWebformSetting('limit_total_unique'))) {
      $form = $this->getMessageManager()->append($form, WebformMessageManagerInterface::LIMIT_TOTAL_MESSAGE, 'warning');
      if ($webform->access('submission_update_any')) {
        $form = $this->getMessageManager()->append($form, WebformMessageManagerInterface::ADMIN_CLOSED, 'info');
        return FALSE;
      }
      else {
        return $form;
      }
    }

    // Check user limit.
    if ($this->checkUserLimit() && empty($this->getWebformSetting('limit_user_unique'))) {
      $form = $this->getMessageManager()->append($form, WebformMessageManagerInterface::LIMIT_USER_MESSAGE, 'warning');
      if ($webform->access('submission_update_any')) {
        $form = $this->getMessageManager()->append($form, WebformMessageManagerInterface::ADMIN_CLOSED, 'info');
        return FALSE;
      }
      else {
        return $form;
      }
    }

    return FALSE;
  }

  /**
   * Display draft, previous submission, and autofill status messages for this webform submission.
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
    if ($this->isGet() && $this->operation === 'test') {
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
        $offcanvas = WebformDialogHelper::useOffCanvas();
        $build = [
          '#type' => 'link',
          '#title' => $this->t('Generate %title submissions', ['%title' => $webform->label()]),
          '#url' => Url::fromRoute('devel_generate.webform_submission', [], ['query' => $query]),
          '#attributes' => ($offcanvas) ? WebformDialogHelper::getOffCanvasDialogAttributes(400) : [],
        ];
        if ($offcanvas) {
          WebformDialogHelper::attachLibraries($form);
        }
        $this->messenger()->addWarning($this->renderer->renderPlain($build));
      }
    }

    // Display admin only message.
    if ($this->isGet()
      && $this->isRoute('webform.canonical')
      && $this->getRouteMatch()->getRawParameter('webform') === $webform->id()
      && !$this->getWebform()->getSetting('page')) {
      $this->getMessageManager()->display(WebformMessageManagerInterface::ADMIN_PAGE, 'info');
    }

    // Display loaded or saved draft message.
    if ($webform_submission->isDraft()) {
      if ($form_state->get('draft_saved')) {
        $this->getMessageManager()->display(WebformMessageManagerInterface::SUBMISSION_DRAFT_SAVED_MESSAGE);
        $form_state->set('draft_saved', FALSE);
      }
      elseif ($this->isGet() && !$webform->getSetting('draft_multiple') && !$webform->isClosed()) {
        $this->getMessageManager()->display(WebformMessageManagerInterface::SUBMISSION_DRAFT_LOADED_MESSAGE);
      }
    }

    // Display link to multiple drafts message when user is adding a new
    // submission.
    if ($this->isGet()
      && $this->getWebformSetting('draft') !== WebformInterface::DRAFT_NONE
      && $this->getWebformSetting('draft_multiple', FALSE)
      && ($this->isRoute('webform.canonical') || $this->isWebformEntityReferenceFromSourceEntity())
      && ($previous_draft_total = $this->getStorage()->getTotal($webform, $this->sourceEntity, $this->currentUser(), ['in_draft' => TRUE]))
    ) {
      if ($previous_draft_total > 1) {
        $this->getMessageManager()->display(WebformMessageManagerInterface::DRAFT_PENDING_MULTIPLE);
      }
      else {
        $draft_submission = $this->getStorage()->loadDraft($webform, $this->sourceEntity, $this->currentUser());
        if (!$draft_submission || $webform_submission->id() != $draft_submission->id()) {
          $this->getMessageManager()->display(WebformMessageManagerInterface::DRAFT_PENDING_SINGLE);
        }
      }
    }

    // Display link to previous submissions message when user is adding a new
    // submission.
    if ($this->isGet()
      && $this->getWebformSetting('form_previous_submissions', FALSE)
      && ($this->isRoute('webform.canonical') || $this->isWebformEntityReferenceFromSourceEntity())
      && ($webform->access('submission_view_own') || $this->currentUser()->hasPermission('view own webform submission'))
      && ($previous_submission_total = $this->getStorage()->getTotal($webform, $this->sourceEntity, $this->currentUser()))
    ) {
      if ($previous_submission_total > 1) {
        $this->getMessageManager()->display(WebformMessageManagerInterface::PREVIOUS_SUBMISSIONS);
      }
      else {
        $last_submission = $this->getLastSubmission(FALSE);
        if ($last_submission && $webform_submission->id() !== $last_submission->id()) {
          $this->getMessageManager()->display(WebformMessageManagerInterface::PREVIOUS_SUBMISSION);
        }
      }
    }

    // Display autofill message.
    if ($this->isGet()
      && $this->operation === 'add'
      && $webform_submission->isNew()
      && $webform->getSetting('autofill')
      && $this->getLastSubmission()) {
      $this->getMessageManager()->display(WebformMessageManagerInterface::AUTOFILL_MESSAGE);
    }
  }

  /****************************************************************************/
  // Webform libraries and behaviors.
  /****************************************************************************/

  /**
   * Attach libraries to the form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function attachLibraries(array &$form, FormStateInterface $form_state) {
    // Default: Add CSS and JS.
    // @see https://www.drupal.org/node/2274843#inline
    $form['#attached']['library'][] = 'webform/webform.form';

    // Assets: Add custom shared and webform specific CSS and JS.
    // @see webform_library_info_build()
    // @see _webform_page_attachments()
    $webform = $this->getWebform();
    $assets = $webform->getAssets();
    foreach ($assets as $type => $value) {
      if ($value) {
        $form['#attached']['library'][] = 'webform/webform.' . $type . '.' . $webform->id();
      }
    }
  }

  /**
   * Attach behaviors with libraries to the form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function attachBehaviors(array &$form, FormStateInterface $form_state) {
    // Form: Inline form errors.
    // Add #disable_inline_form_errors property to form.
    if ($this->getWebformSetting('form_disable_inline_errors')) {
      $form['#disable_inline_form_errors'] = TRUE;
    }

    // Form: Novalidate
    // Add novalidate attribute to form if client side validation disabled.
    if ($this->getWebformSetting('form_novalidate')) {
      $form['#attributes']['novalidate'] = 'novalidate';
    }

    // Form: Autocomplete.
    // Add autocomplete=off attribute to form if autocompletion is disabled.
    if ($this->getWebformSetting('form_disable_autocomplete')) {
      $form['#attributes']['autocomplete'] = 'off';
    }

    // Form: Disable back button.
    if ($this->getWebformSetting('form_disable_back')) {
      $form['#attached']['library'][] = 'webform/webform.form.disable_back';
    }

    // Form: Back button submit.
    // Move back when back button is pressed on multistep forms.
    if ($this->getWebformSetting('form_submit_back') && !$this->isAjax()) {
      $form['#attached']['library'][] = 'webform/webform.form.submit_back';
    }

    // Form; Unsaved.
    // Add unsaved message.
    if ($this->getWebformSetting('form_unsaved')) {
      $form['#attributes']['class'][] = 'js-webform-unsaved';
      // Set 'data-webform-unsaved' attribute if unsaved wizard.
      $pages = $this->getPages($form, $form_state);
      $current_page = $this->getCurrentPage($form, $form_state);
      if ($current_page && ($current_page != $this->getFirstPage($pages))) {
        $form['#attributes']['data-webform-unsaved'] = TRUE;
      }
      $form['#attached']['library'][] = 'webform/webform.form.unsaved';
    }

    // Form: Submit once.
    // Prevent duplicate submissions.
    if ($this->getWebformSetting('form_submit_once')) {
      $form['#attributes']['class'][] = 'js-webform-submit-once';
      $form['#attached']['library'][] = 'webform/webform.form.submit_once';
    }

    // Form: Autosubmit.
    // Disable webform auto submit on enter for wizard webform pages only.
    if ($this->hasPages()) {
      $form['#attributes']['class'][] = 'js-webform-disable-autosubmit';
    }

    // Element: Autofocus.
    // Add autofocus class to webform.
    if ($this->entity->isNew() && $this->getWebformSetting('form_autofocus')) {
      $form['#attributes']['class'][] = 'js-webform-autofocus';
      $form['#attached']['library'][] = 'webform/webform.form.auto_focus';
    }

    // Details: Save.
    // Attach details element save open/close library.
    // This ensures that the library will be loaded even if the webform is
    // used as a block or a node.
    if ($this->config('webform.settings')->get('ui.details_save')) {
      $form['#attached']['library'][] = 'webform/webform.element.details.save';
    }

    // Details Toggle:
    // Display collapse/expand all details link.
    if ($this->getWebformSetting('form_details_toggle')) {
      $form['#attributes']['class'][] = 'js-webform-details-toggle';
      $form['#attributes']['class'][] = 'webform-details-toggle';
      $form['#attached']['library'][] = 'webform/webform.element.details.toggle';
    }
  }

  /****************************************************************************/
  // Webform actions.
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
   * Returns the wizard page submit buttons for the current entity form.
   */
  protected function pagesElement(array $form, FormStateInterface $form_state) {
    $pages = $this->getPages($form, $form_state);
    if (!$pages) {
      return NULL;
    }

    $current_page_name = $this->getCurrentPage($form, $form_state);
    if (!$this->getWebformSetting('wizard_progress_link') && !($this->getWebformSetting('wizard_preview_link') && $current_page_name === 'webform_preview')) {
      return NULL;
    }

    $page_indexes = array_flip(array_keys($pages));
    $current_index = $page_indexes[$current_page_name] - 1;

    // Build dedicated actions element for pages links.
    $element = [
      '#type' => 'actions',
      '#weight' => -20,
      '#attributes' => [
        'class' => ['webform-wizard-pages-links', 'js-webform-wizard-pages-links'],
      ],
      // Only process the container and prevent .form-actions from being added
      // which force submit buttons to be rendered in dialogs.
      // @see \Drupal\Core\Render\Element\Actions
      // @see Drupal.behaviors.dialog.prepareDialogButtons
      '#process' => [
        ['\Drupal\Core\Render\Element\Actions', 'processContainer'],
      ],
    ];
    if ($this->getWebformSetting('wizard_progress_link')) {
      $element['#attributes']['data-wizard-progress-link'] = 'true';
    }
    if ($this->getWebformSetting('wizard_preview_link')) {
      $element['#attributes']['data-wizard-preview-link'] = 'true';
    }

    $index = 1;
    $total = count($pages);
    foreach ($pages as $page_name => $page) {
      // Always include submit button for each page but only allows access
      // to previous and visible pages.
      //
      // Developers who want to allow users to jump to any wizard page can
      // expose these buttons via a form alter hook. Beware that
      // skipped pages will not be validated.
      $access = ($page['#access'] && ($page_indexes[$page_name] <= $current_index)) ? TRUE : FALSE;
      $t_args = [
        '@label' => $page['#title'],
        '@start' => $index++,
        '@end' => $total,
      ];
      $element[$page_name] = [
        '#type' => 'submit',
        '#value' => $this->t('Edit'),
        '#page' => $page_name,
        '#validate' => ['::noValidate'],
        '#submit' => ['::gotoPage'],
        '#name' => 'webform_wizard_page-' . $page_name,
        '#attributes' => [
          'data-webform-page' => $page_name,
          'formnovalidate' => 'formnovalidate',
          'class' => ['webform-wizard-pages-link', 'js-webform-wizard-pages-link'],
          'title' => $this->t("Edit '@label' (Page @start of @end)", $t_args),
        ],
        '#access' => $access,
      ];
    }

    $element['#attached']['library'][] = 'webform/webform.wizard.pages';

    return $element;
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

      // Use next page submit callback to make sure conditional page logic
      // is executed.
      $element['submit']['#submit'] = ['::submit'];

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
            '#attributes' => [
              'formnovalidate' => 'formnovalidate',
              'class' => ['webform-button--previous'],
            ],
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
            '#attributes' => [
              'formnovalidate' => 'formnovalidate',
              'class' => ['webform-button--previous'],
            ],
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
            '#attributes' => ['class' => ['webform-button--next']],
            '#weight' => 1,
          ];
          if ($track) {
            $element['wizard_next']['#attributes']['data-webform-wizard-page'] = $track_next_page;
          }
        }
      }
      if ($track) {
        $element['#attached']['library'][] = 'webform/webform.wizard.track';
      }
    }

    // Draft.
    if ($this->draftEnabled()) {
      $element['draft'] = [
        '#type' => 'submit',
        '#value' => $this->config('webform.settings')->get('settings.default_draft_button_label'),
        '#validate' => ['::draft'],
        '#submit' => ['::submitForm', '::save', '::rebuild'],
        '#attributes' => [
          'formnovalidate' => 'formnovalidate',
          'class' => ['webform-button--draft'],
        ],
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
        '#attributes' => [
          'formnovalidate' => 'formnovalidate',
          'class' => ['webform-button--reset'],
        ],
        '#weight' => 10,
      ];
    }

    uasort($element, ['Drupal\Component\Utility\SortArray', 'sortByWeightProperty']);

    return $element;
  }

  /**
   * Webform submission handler for the 'goto' action.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function gotoPage(array &$form, FormStateInterface $form_state) {
    $element = $form_state->getTriggeringElement();
    $form_state->set('current_page', $element['#page']);
    $this->wizardSubmit($form, $form_state);
  }

  /**
   * Webform submission handler for the 'submit' action.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submit(array &$form, FormStateInterface $form_state) {
    $this->next($form, $form_state, TRUE);
  }

  /**
   * Webform submission handler for the 'next' action.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function next(array &$form, FormStateInterface $form_state, $skip_preview = FALSE) {
    if ($form_state->getErrors()) {
      return;
    }
    $pages = $this->getPages($form, $form_state);

    // Get next page.
    $current_page = $this->getCurrentPage($form, $form_state);
    $next_page = $this->getNextPage($pages, $current_page);

    // If there is no next page jump to the confirmation page which will also
    // submit this form.
    // @see \Drupal\webform\WebformSubmissionForm::wizardSubmit
    if (empty($next_page)) {
      $next_page = 'webform_confirmation';
    }

    // Skip preview page and move to the confirmation page.
    // @see
    if ($skip_preview && $next_page === 'webform_preview') {
      $next_page = 'webform_confirmation';
    }

    // Set next page.
    $form_state->set('current_page', $next_page);

    // Submit next page.
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

    // Get previous page.
    $current_page = $this->getCurrentPage($form, $form_state);
    $previous_page = $this->getPreviousPage($pages, $current_page);

    // Set previous page.
    $form_state->set('current_page', $previous_page);

    // Submit previous page.
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
      $form_state->set('in_draft', TRUE);

      $this->submitForm($form, $form_state);
      $this->save($form, $form_state);
      $this->rebuild($form, $form_state);
    }
    else {
      $this->submitForm($form, $form_state);
      $this->rebuild($form, $form_state);
    }

    // Announce current page with progress.
    // @see template_preprocess_webform_progress()
    if ($this->isAjax()) {
      $pages = $this->getPages($form, $form_state);
      // Make sure the current page exists because the confirmation page
      // may not be included in the wizard's pages.
      if (isset($pages[$current_page])) {
        $page_keys = array_keys($pages);
        $page_indexes = array_flip($page_keys);
        $total_pages = count($page_keys);
        $current_index = $page_indexes[$current_page];

        $t_args = [
          '@title' => $this->getWebform()->label(),
          '@page' => $pages[$current_page]['#title'],
          '@start' => ($current_index + 1),
          '@end' => $total_pages,
        ];
        $this->announce($this->t('"@title: @page" loaded. (Page @start of @end)', $t_args));
      }
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
        $form_state->set('in_draft', TRUE);

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
    $form_state->set('in_draft', TRUE);
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
    $form_state->set('in_draft', FALSE);
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
   * 'Email Confirm', 'Composite Elements', 'Other Elements', and more…
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

    // Disable inline form error when performing validation via the API.
    if ($this->operation === 'api') {
      // @see \Drupal\webform\WebformSubmissionForm::submitWebformSubmission
      $form['#disable_inline_form_errors'] = TRUE;
    }

    // Build webform submission with validated and processed data.
    $this->entity = $this->buildEntity($form, $form_state);

    // Server side #states API validation.
    $this->conditionsValidator->validateForm($form, $form_state);

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
        $arguments = [&$form, &$form_state];
        call_user_func_array($form_state->prepareCallback($callback), $arguments);
      }
    }

    // Validate file (upload) limit.
    $this->validateUploadedManagedFiles($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    // Server side #states API submit.
    $this->conditionsValidator->submitForm($form, $form_state);

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

    // Get confirmation type and submission state.
    $confirmation_type = $this->getWebformSetting('confirmation_type');
    $state = $webform_submission->getState();

    // Rebuild or reset the form if reloading the current form via AJAX.
    if ($this->isAjax()) {
      // On update, rebuild and display message unless ?destination= is set.
      // @see \Drupal\webform\WebformSubmissionForm::setConfirmation
      if ($state === WebformSubmissionInterface::STATE_UPDATED) {
        if (!$this->getRequest()->get('destination')) {
          $this->reset($form, $form_state);
        }
      }
      elseif ($confirmation_type === WebformInterface::CONFIRMATION_MESSAGE || $confirmation_type === WebformInterface::CONFIRMATION_NONE) {
        $this->reset($form, $form_state);
      }
    }

    // Always rebuild or  reset the form to trigger a modal dialog.
    if ($confirmation_type === WebformInterface::CONFIRMATION_MODAL) {
      if ($state === WebformSubmissionInterface::STATE_UPDATED) {
        $this->rebuild($form, $form_state);
      }
      else {
        $this->reset($form, $form_state);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $webform = $this->getWebform();
    /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
    $webform_submission = $this->getEntity();

    // Apply variants.
    $webform->applyVariants($webform_submission);

    // Make sure the uri and remote addr are set correctly because
    // Ajax requests can cause these values to be reset.
    if ($webform_submission->isNew()) {
      if (preg_match('/\.webform\.test_form$/', $this->getRouteMatch()->getRouteName())) {
        // For test submissions use the source URL.
        $source_url = $webform_submission->set('uri', NULL)->getSourceUrl()->setAbsolute(FALSE);
        $uri = preg_replace('#^' . base_path() . '#', '/', $source_url->toString());
      }
      else {
        // For all other submissions, use the request URI.
        $uri = preg_replace('#^' . base_path() . '#', '/', $this->getRequest()->getRequestUri());
        // Remove Ajax query string parameters.
        $uri = preg_replace('/(ajax_form=1|_wrapper_format=(drupal_ajax|drupal_modal|drupal_dialog|html|ajax))(&|$)/', '', $uri);
        // Remove empty query string.
        $uri = preg_replace('/\?$/', '', $uri);
      }
      $webform_submission->set('uri', $uri);
      $webform_submission->set('remote_addr', ($this->getWebform()->hasRemoteAddr()) ? $this->getRequest()->getClientIp() : '');
      if ($this->isConfidential()) {
        $webform_submission->setOwnerId(0);
      }
    }

    // Block users from submitting templates that they can't update.
    if ($webform->isTemplate() && !$webform->access('update')) {
      return;
    }

    // Save and log webform submission.
    $webform_submission->save();

    // Invalidate cache if any limits are specified.
    if ($this->getWebformSetting('limit_total')
      || $this->getWebformSetting('user_limit_total')
      || $this->getWebformSetting('entity_limit_total')
      || $this->getWebformSetting('entity_limit_user')
      || $this->getWebformSetting('limit_total_unique')
      || $this->getWebformSetting('limit_user_unique')
    ) {
      Cache::invalidateTags(['webform:' . $this->getWebform()->id()]);
    }

    // Check limits rebuild.
    if ($this->checkTotalLimit() || $this->checkUserLimit()) {
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
    // Delete save draft.
    /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
    $webform_submission = $this->getEntity();
    if ($webform_submission->isDraft()) {
      $webform_submission->delete();
    }

    // Create new webform submission.
    /** @var \Drupal\webform\Entity\WebformSubmission $webform_submission */
    $webform_submission = $this->getEntity()->createDuplicate();
    $webform_submission->setData($this->originalData);
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
  // Validate functions.
  /****************************************************************************/

  /**
   * Validate uploaded managed file limits.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see \Drupal\webform\Plugin\WebformElement\WebformManagedFileBase::validateManagedFileLimit
   */
  protected function validateUploadedManagedFiles(array $form, FormStateInterface $form_state) {
    $file_limit = $this->getWebform()->getSetting('form_file_limit')
      ?: $this->configFactory->get('webform.settings')->get('settings.default_form_file_limit')
      ?: '';
    $file_limit = Bytes::toInt($file_limit);
    if (!$file_limit) {
      return;
    }

    // Validate file upload limit.
    $fids = $this->getUploadedManagedFileIds();
    if (!$fids) {
      return;
    }
    $file_names = [];
    $total_file_size = 0;

    /** @var \Drupal\file\FileInterface[] $files */
    $files = $this->entityTypeManager->getStorage('file')->loadMultiple($fids);
    foreach ($files as $file) {
      $total_file_size += (int) $file->getSize();
      $file_names[] = $file->getFilename() . ' - ' . format_size($file->getSize(), $this->entity->language()->getId());
    }

    if ($total_file_size > $file_limit) {
      $t_args = ['%quota' => format_size($file_limit)];
      $message = [];
      $message['content'] = ['#markup' => $this->t("This form's file upload quota of %quota has been exceeded. Please remove some files.", $t_args)];
      $message['files'] = [
        '#theme' => 'item_list',
        '#items' => $file_names,
      ];
      $form_state->setErrorByName(NULL, $this->renderer->renderPlain($message));
    }
  }

  /**
   * Get uploaded managed file ids.
   *
   * @return array
   *   An array of uploaded file ids.
   */
  protected function getUploadedManagedFileIds() {
    $fids = [];

    $element_keys = $this->getWebform()->getElementsManagedFiles();
    foreach ($element_keys as $element_key) {
      $data = $this->entity->getElementData($element_key);
      if (!$data) {
        continue;
      }

      $element = $this->getWebform()->getElement($element_key);
      $element_plugin = $this->elementManager->getElementInstance($element);
      $multiple = $element_plugin->hasMultipleValues($element);

      // Get fids from composite sub-elements.
      if ($element_plugin instanceof WebformCompositeBase) {
        $managed_file_keys = $element_plugin->getManagedFiles($element);
        // Convert single composite value to array of multiple composite values.
        $data = (!$multiple) ? [$data] : $data;
        foreach ($data as $item) {
          foreach ($managed_file_keys as $manage_file_key) {
            if ($item[$manage_file_key]) {
              $fids[] = $item[$manage_file_key];
            }
          }
        }
      }
      else {
        $fids = array_merge($fids, (array) $data);
      }
    }

    return $fids;
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
        $value = $this->tokenManager->replace($value, $this->getEntity());
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
   * Determine if this is a multi-step wizard form.
   *
   * @return bool
   *   TRUE if this multi-step wizard form.
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

    /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
    $webform_submission = $this->getEntity();
    return $this->conditionsValidator->buildPages($pages, $webform_submission);
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
        if ($current_page && isset($pages[$current_page]) && !$this->entity->isCompleted()) {
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
        if (isset($element['#type']) && $element['#type'] === 'webform_actions') {
          continue;
        }

        // Set #access to FALSE which will suppresses webform #required validation.
        if (Element::child($element_key) && is_array($form['elements'])) {
          WebformElementHelper::setPropertyRecursive($form['elements'][$element_key], '#access', FALSE);
        }
      }

      // Display preview message.
      $this->getMessageManager()->display(WebformMessageManagerInterface::FORM_PREVIEW_MESSAGE, 'warning');

      // Build preview.
      $preview_attributes = new Attribute($this->getWebform()->getSetting('preview_attributes'));
      $preview_attributes->addClass('webform-preview');
      $form['#title'] = PlainTextOutput::renderFromHtml($this->getWebformSetting('preview_title'));
      $form['preview'] = [
        '#type' => 'container',
        '#attributes' => $preview_attributes,
        // Progress bar is -20.
        '#weight' => -10,
        'submission' => $this->entityTypeManager
          ->getViewBuilder('webform_submission')
          ->view($this->entity, 'preview'),
      ];
    }
    else {
      // Get all pages so that we can also hide skipped pages.
      $pages = $this->getWebform()->getPages($this->operation);
      foreach ($pages as $page_key => $page) {
        if (isset($form['elements'][$page_key])) {
          $page_element =& $form['elements'][$page_key];
          $page_element_plugin = $this->elementManager->getElementInstance($page_element);
          if ($page_element_plugin instanceof WebformElementWizardPageInterface) {
            if ($page_key != $current_page) {
              $page_element_plugin->hidePage($page_element);
            }
            else {
              $page_element_plugin->showPage($page_element);
            }
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
    if (!$webform->getSetting('confirmation_exclude_query')) {
      $query = $this->getRequest()->query->all();
      // Remove Ajax parameters from query.
      unset($query['ajax_form'], $query['_wrapper_format']);
      if ($query) {
        $route_options['query'] = $query;
      }
    }

    // Default to displaying a confirmation message on this page when submission
    // is updated or locked (but not just completed).
    $state = $webform_submission->getState();
    $is_updated = ($state === WebformSubmissionInterface::STATE_UPDATED);
    $is_locked = ($state === WebformSubmissionInterface::STATE_LOCKED && $webform_submission->getChangedTime() > $webform_submission->getCompletedTime());
    $confirmation_update = $this->getWebformSetting('confirmation_update');

    if (($is_updated && !$confirmation_update) || $is_locked) {
      $this->getMessageManager()->display(WebformMessageManagerInterface::SUBMISSION_UPDATED);
      $form_state->set('current_page', NULL);
      $form_state->setRedirect($route_name, $route_parameters, $route_options);
      return;
    }

    // Add token route query options.
    if ($state === WebformSubmissionInterface::STATE_COMPLETED && !$webform->getSetting('confirmation_exclude_token')) {
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
        // Get redirect URL if internal or valid.
        if (strpos($confirmation_url, 'internal:') === 0) {
          $redirect_url = Url::fromUri($confirmation_url);
        }
        else {
          $redirect_url = $this->pathValidator->getUrlIfValid($confirmation_url);
        }
        if ($redirect_url) {
          if ($confirmation_type == WebformInterface::CONFIRMATION_URL_MESSAGE) {
            $this->getMessageManager()->display(WebformMessageManagerInterface::SUBMISSION_CONFIRMATION_MESSAGE);
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
            $this->messenger()->addWarning($this->t('Confirmation URL %url is not valid.', $t_args));
          }
          // Log warning.
          $this->getLogger('webform')->warning('@webform: Confirmation URL %url is not valid.', $t_args);
        }

        // If confirmation URL is invalid display message.
        $this->getMessageManager()->display(WebformMessageManagerInterface::SUBMISSION_CONFIRMATION_MESSAGE);
        $route_options['query']['webform_id'] = $webform->id();
        $form_state->setRedirect($route_name, $route_parameters, $route_options);
        return;

      case WebformInterface::CONFIRMATION_INLINE:
        $form_state->set('current_page', 'webform_confirmation');
        $form_state->setRebuild();
        return;

      case WebformInterface::CONFIRMATION_MESSAGE:
        $this->getMessageManager()->display(WebformMessageManagerInterface::SUBMISSION_CONFIRMATION_MESSAGE);
        return;

      case WebformInterface::CONFIRMATION_MODAL:
        $message = $this->getMessageManager()->build(WebformMessageManagerInterface::SUBMISSION_CONFIRMATION_MESSAGE);
        if ($message) {
          // Set webform confirmation modal in $form_state.
          $form_state->set('webform_confirmation_modal', [
            'title' => $this->getWebformSetting('confirmation_title', ''),
            'content' => $message,
          ]);
        }
        return;

      case WebformInterface::CONFIRMATION_NONE:
        return;

      case WebformInterface::CONFIRMATION_DEFAULT:
      default:
        $this->getMessageManager()->display(WebformMessageManagerInterface::SUBMISSION_DEFAULT_CONFIRMATION);
        return;
    }
  }

  /**
   * Hide confirmation modal during form validation.
   *
   * This prevent duplicate modal dialog from appearing.
   */
  public static function removeConfirmationModal(&$element, FormStateInterface $form_state, &$complete_form) {
    // Reset confirmation modal.
    $storage = $form_state->getStorage();
    unset($storage['webform_confirmation_modal']);
    $form_state->setStorage($storage);

    // Remove modal from form.
    unset($complete_form['webform_confirmation_modal']);
  }

  /****************************************************************************/
  // Elements functions
  /****************************************************************************/

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
      if (!WebformElementHelper::isElement($element, $key)) {
        continue;
      }

      // Build the webform element.
      $this->elementManager->buildElement($element, $form, $form_state);

      if (isset($element['#states'])) {
        $element['#states'] = $this->addStatesPrefix($element['#states']);
      }

      // Recurse and prepare nested elements.
      $this->prepareElements($element, $form, $form_state);
    }
  }

  /**
   * Alter webform elements form.
   *
   * @param array $elements
   *   An render array representing elements.
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function alterElementsForm(array &$elements, array &$form, FormStateInterface $form_state) {
    foreach ($elements as $key => &$element) {
      if (!WebformElementHelper::isElement($element, $key)) {
        continue;
      }

      $element_plugin = $this->elementManager->getElementInstance($element);
      if ($element_plugin) {
        $element_plugin->alterForm($element, $form, $form_state);
      }

      // Recurse and alter nested elements forms.
      $this->alterElementsForm($element, $form, $form_state);
    }
  }

  /**
   * Add unique class prefix to all :input #states selectors.
   *
   * @param array $array
   *   An associative array.
   *
   * @return array
   *   An associative array with unique class prefix added to all :input
   *   #states selectors.
   */
  protected function addStatesPrefix(array $array) {
    $prefixed_array = [];
    foreach ($array as $key => $value) {
      if (strpos($key, ':input') === 0) {
        $key = $this->statesPrefix . ' ' . $key;
        $prefixed_array[$key] = $value;
      }
      elseif (is_array($value)) {
        $prefixed_array[$key] = $this->addStatesPrefix($value);
      }
      else {
        $prefixed_array[$key] = $value;
      }
    }
    return $prefixed_array;
  }

  /**
   * Prepopulate element data.
   *
   * @param array $data
   *   An array of default.
   */
  protected function prepopulateData(array &$data) {
    // Get prepopulate data.
    if ($this->getWebformSetting('form_prepopulate')) {
      $prepopulate_data = $this->getRequest()->query->all();
    }
    else {
      $prepopulate_data = [];
      $elements = $this->getWebform()->getElementsPrepopulate();
      foreach ($elements as $element_key) {
        if ($this->getRequest()->query->has($element_key)) {
          $prepopulate_data[$element_key] = $this->getRequest()->query->get($element_key);
        }
      }
    }

    // Validate prepopulate data.
    foreach ($prepopulate_data as $element_key => &$value) {
      if ($this->checkPrepopulateDataValid($element_key, $value) === FALSE) {
        unset($prepopulate_data[$element_key]);
      }
    }

    // Set prepopulate data.
    $data = $prepopulate_data + $data;
  }

  /**
   * Determine if element prepopulate data is valid.
   *
   * @param string $element_key
   *   An element key.
   * @param string|array &$value
   *   A value.
   *
   * @return bool
   *   TRUE if element prepopulate data is valid.
   */
  protected function checkPrepopulateDataValid($element_key, &$value) {
    // Make sure the element exists.
    $element = $this->getWebform()->getElement($element_key);
    if (!$element) {
      return FALSE;
    }

    // Make sure the element is an input.
    $element_plugin = $this->elementManager->getElementInstance($element);
    if (!$element_plugin->isInput($element)) {
      return FALSE;
    }

    // Validate entity references.
    // @see \Drupal\Core\Entity\Element\EntityAutocomplete::validateEntityAutocomplete
    // @see \Drupal\webform\Plugin\WebformElement\WebformTermReferenceTrait
    if ($element_plugin instanceof WebformElementEntityReferenceInterface) {
      if (isset($element['#vocabulary'])) {
        $vocabulary_id = $element['#vocabulary'];
        $options = [
          'target_type' => 'taxonomy_term',
          'handler' => 'default:taxonomy_term',
          'target_bundles' => [$vocabulary_id => $vocabulary_id],
        ];
      }
      elseif (isset($element['#selection_settings'])) {
        $options = $element['#selection_settings'] + [
          'target_type' => $element['#target_type'],
          'handler' => $element['#selection_handler'],
        ];
      }
      else {
        return TRUE;
      }

      /** @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface $handler */
      $handler = $this->selectionManager->getInstance($options);
      $valid_ids = $handler->validateReferenceableEntities((array) $value);
      if (empty($valid_ids)) {
        return FALSE;
      }
      else {
        $value = $element_plugin->hasMultipleValues($element) ? $valid_ids : reset($valid_ids);
        return TRUE;
      }
    }

    // Validate options.
    $is_options_element = isset($element['#options'])
      && $element_plugin instanceof OptionsBase
      && !$element_plugin instanceof WebformElementOtherInterface;
    if ($is_options_element) {
      $option_values = WebformOptionsHelper::validateOptionValues($element['#options'], (array) $value);
      if (empty($option_values)) {
        return FALSE;
      }
      else {
        $value = $element_plugin->hasMultipleValues($element) ? $option_values : reset($option_values);
        return TRUE;
      }
    }

    return TRUE;
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
      if (!WebformElementHelper::isElement($element, $key)) {
        continue;
      }

      // If value is not set, continue to populate sub-elements.
      if (!isset($values[$key])) {
        $this->populateElements($element, $values);
        continue;
      }

      // Get the element's plugin.
      $element_plugin = $this->elementManager->getElementInstance($element);

      // If not input, populate sub-elements and continue.
      if (!$element_plugin || !$element_plugin->isInput($element)) {
        $this->populateElements($element, $values);
        continue;
      }

      // If input does not support prepopulate, populate sub-elements and continue.
      if ($this->getRequest()->query->has($key) && !$element_plugin->hasProperty('prepopulate')) {
        $this->populateElements($element, $values);
        continue;
      }

      // Determine if this is a hidden element.
      // Hidden elements use #value but need to use #default_value to
      // be populated.
      $is_hidden = ($element_plugin instanceof Hidden);

      // Populate default value or value.
      if ($element_plugin->hasProperty('default_value') || $is_hidden) {
        $element['#default_value'] = $values[$key];
      }
      elseif ($element_plugin->hasProperty('value')) {
        $element['#value'] = $values[$key];
      }

      // API values need to trigger validation.
      if ($this->operation === 'api') {
        $element['#needs_validation'] = TRUE;
      }

      // Populate sub-elements.
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

    // Get limit total to unique submission per webform/source entity.
    $limit_total_unique = $this->getWebformSetting('limit_total_unique');

    // Check per source entity total limit.
    $entity_limit_total = $this->getWebformSetting('entity_limit_total');
    $entity_limit_total_interval = $this->getWebformSetting('entity_limit_total_interval');
    if ($limit_total_unique) {
      $entity_limit_total = 1;
      $entity_limit_total_interval = NULL;
    }
    if ($entity_limit_total && ($source_entity = $this->getLimitSourceEntity())) {
      if ($this->getStorage()->getTotal($webform, $source_entity, NULL, ['interval' => $entity_limit_total_interval]) >= $entity_limit_total) {
        return TRUE;
      }
    }

    // Check total limit.
    $limit_total = $this->getWebformSetting('limit_total');
    $limit_total_interval = $this->getWebformSetting('limit_total_interval');
    if ($limit_total_unique) {
      $limit_total = 1;
      $limit_total_interval = NULL;
    }
    if ($limit_total && $this->getStorage()->getTotal($webform, NULL, NULL, ['interval' => $limit_total_interval]) >= $limit_total) {
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
    if ($webform_submission->id() && $webform_submission->isOwner($this->currentUser())) {
      return FALSE;
    }

    // Get the submission owner and not current user.
    // This takes into account when an API submission changes the owner id.
    // @see \Drupal\webform\WebformSubmissionForm::submitFormValues
    $account = $this->entity->getOwner();
    $webform = $this->getWebform();

    // Check per source entity user limit.
    $entity_limit_user = $this->getWebformSetting('entity_limit_user');
    $entity_limit_user_interval = $this->getWebformSetting('entity_limit_user_interval');
    if ($entity_limit_user && ($source_entity = $this->getLimitSourceEntity())) {
      if ($this->getStorage()->getTotal($webform, $source_entity, $account, ['interval' => $entity_limit_user_interval]) >= $entity_limit_user) {
        return TRUE;
      }
    }

    // Check user limit.
    $limit_user = $this->getWebformSetting('limit_user');
    $limit_user_interval = $this->getWebformSetting('limit_user_interval');
    if ($limit_user && $this->getStorage()->getTotal($webform, NULL, $account, ['interval' => $limit_user_interval]) >= $limit_user) {
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
   * Get the webform submission's webform.
   *
   * @return \Drupal\webform\WebformInterface
   *   A webform.
   */
  public function getWebform() {
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
   * Get the webform submission entity storage.
   *
   * @return \Drupal\Webform\WebformSubmissionStorageInterface
   *   The webform submission entity storage.
   */
  protected function getStorage() {
    return $this->entityTypeManager->getStorage('webform_submission');
  }

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
   * Get last completed webform submission for the current user.
   *
   * @param bool $completed
   *   Flag to get last completed or draft submission.
   *
   * @return \Drupal\webform\WebformSubmissionInterface|null
   *   The last completed webform submission for the current user.
   */
  protected function getLastSubmission($completed = TRUE) {
    $webform = $this->getWebform();
    $source_entity = $this->getSourceEntity();
    $account = $this->getEntity()->getOwner();
    $options = ($completed) ? ['in_draft' => FALSE] : [];
    return $this->getStorage()->getLastSubmission($webform, $source_entity, $account, $options);
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
    $value = $this->getWebform()->getSetting($name)
      ?: $this->config('webform.settings')->get('settings.default_' . $name)
      ?: NULL;

    if ($value !== NULL) {
      return $this->tokenManager->replace($value, $this->getEntity());
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
    if ($this->operation === 'api') {
      return FALSE;
    }
    return $this->getWebformSetting('ajax', FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function cancelAjaxForm(array &$form, FormStateInterface $form_state) {
    throw new \Exception('Webform submission Ajax form should never be cancelled. Only ::reset should be called.');
  }

  /**
   * {@inheritdoc}
   */
  public function validateAjaxForm(array &$form, FormStateInterface $form_state) {
    if (!$this->isCallableAjaxCallback($form, $form_state)) {
      // Invalidate cache tags to prevent any caching issues.
      // @see https://www.drupal.org/project/drupal/issues/2352009
      Cache::invalidateTags(['webform:' . $this->getWebform()->id()]);
      $this->missingAjaxCallback($form, $form_state);
    }
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
   * @return array|bool
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
   * Programmatically validate form values and submit a webform submission.
   *
   * @param array $values
   *   An array of submission form values and data.
   *
   * @return array|null
   *   An array of error messages if validation fails
   *   or NULL if there are no validation errors.
   */
  public static function validateFormValues(array $values) {
    return static::submitFormValues($values, TRUE);
  }

  /**
   * Programmatically validate form values and submit a webform submission.
   *
   * @param array $values
   *   An array of submission form values and data.
   * @param bool $validate_only
   *   Flag to trigger only webform validation. Defaults to FALSE.
   *
   * @return array|\Drupal\webform\WebformSubmissionInterface|null
   *   An array of error messages if validation fails
   *   or a webform submission (when $validate_only is FALSE)
   *   or NULL (when $validate_only is TRUE) if there are no validation errors.
   */
  public static function submitFormValues(array $values, $validate_only = FALSE) {
    $webform_submission = WebformSubmission::create($values);
    return static::submitWebformSubmission($webform_submission, $validate_only);
  }

  /**
   * Programmatically validate and submit a webform submission.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   WebformSubmission with values and data.
   *
   * @return array|null
   *   An array of error messages if validation fails or
   *   NULL if there are no validation errors.
   */
  public static function validateWebformSubmission(WebformSubmissionInterface $webform_submission) {
    return static::submitWebformSubmission($webform_submission, TRUE);
  }

  /**
   * Programmatically validate and submit a webform submission.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   WebformSubmission with values and data.
   * @param bool $validate_only
   *   Flag to trigger only webform validation. Defaults to FALSE.
   *
   * @return array|\Drupal\webform\WebformSubmissionInterface|null
   *   An array of error messages if validation fails
   *   or a webform submission (when $validate_only is FALSE)
   *   or NULL (when $validate_only is TRUE) if there are no validation errors.
   */
  public static function submitWebformSubmission(WebformSubmissionInterface $webform_submission, $validate_only = FALSE) {
    /** @var \Drupal\webform\WebformSubmissionForm $form_object */
    $form_object = \Drupal::entityTypeManager()->getFormObject('webform_submission', 'api');
    $form_object->setEntity($webform_submission);

    // Create an empty form state which will be populated when the submission
    // form is submitted.
    $form_state = new FormState();

    // Set the triggering element to an empty element to prevent
    // errors from managed files.
    // @see \Drupal\file\Element\ManagedFile::validateManagedFile
    $form_state->setTriggeringElement(['#parents' => []]);

    // Get existing error messages.
    $error_messages = \Drupal::messenger()->messagesByType(MessengerInterface::TYPE_ERROR);

    // Submit the form.
    \Drupal::formBuilder()->submitForm($form_object, $form_state);

    // Get the errors but skip drafts.
    $errors = ($webform_submission->isDraft() && !$validate_only) ? [] : $form_state->getErrors();

    // Delete all form related error messages.
    \Drupal::messenger()->deleteByType(MessengerInterface::TYPE_ERROR);

    // Restore existing error message.
    foreach ($error_messages as $error_message) {
      \Drupal::messenger()->addError($error_message);
    }

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
