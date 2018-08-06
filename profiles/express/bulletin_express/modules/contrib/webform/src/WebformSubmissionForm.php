<?php

namespace Drupal\webform;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Drupal\webform\Controller\WebformController;
use Drupal\webform\Plugin\Field\FieldType\WebformEntityReferenceItem;
use Drupal\webform\Utility\WebformArrayHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a webform to collect and edit submissions.
 */
class WebformSubmissionForm extends ContentEntityForm {

  use WebformDialogTrait;

  /**
   * Flag when set to TRUE displays all wizard pages in one single form.
   *
   * @var bool
   */
  protected $disablePages = FALSE;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The webform element (plugin) manager.
   *
   * @var \Drupal\webform\WebformElementManagerInterface
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
   * The token manager.
   *
   * @var \Drupal\webform\WebformTranslationManagerInterface
   */
  protected $tokenManager;

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
   * @param \Drupal\webform\WebformRequestInterface $request_handler
   *   The webform request handler.
   * @param \Drupal\webform\WebformElementManagerInterface $element_manager
   *   The webform element manager.
   * @param \Drupal\webform\WebformThirdPartySettingsManagerInterface $third_party_settings_manager
   *   The webform third party settings manager.
   * @param \Drupal\webform\WebformMessageManagerInterface $message_manager
   *   The webform message manager.
   * @param \Drupal\webform\WebformTokenManagerInterface $token_manager
   *   The token manager.
   */
  public function __construct(EntityManagerInterface $entity_manager, RendererInterface $renderer, WebformRequestInterface $request_handler, WebformElementManagerInterface $element_manager, WebformThirdPartySettingsManagerInterface $third_party_settings_manager, WebformMessageManagerInterface $message_manager, WebformTokenManagerInterface $token_manager) {
    parent::__construct($entity_manager);
    $this->renderer = $renderer;
    $this->requestHandler = $request_handler;
    $this->elementManager = $element_manager;
    $this->storage = $this->entityManager->getStorage('webform_submission');
    $this->thirdPartySettingsManager = $third_party_settings_manager;
    $this->messageManager = $message_manager;
    $this->tokenManager = $token_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('renderer'),
      $container->get('webform.request'),
      $container->get('plugin.manager.webform.element'),
      $container->get('webform.third_party_settings_manager'),
      $container->get('webform.message_manager'),
      $container->get('webform.token_manager')
    );
  }

  /**
   * {@inheritdoc}
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
      $form_id .= $this->operation;
    }
    return $form_id . '_form';
  }

  /**
   * {@inheritdoc}
   */
  public function setEntity(EntityInterface $entity) {
    /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
    $webform_submission = $entity;
    $webform = $entity->getWebform();

    // Get the source entity and allow webform submission to be used as a source
    // entity.
    $this->sourceEntity = $this->requestHandler->getCurrentSourceEntity(['webform']);
    if ($this->sourceEntity == $webform_submission) {
      $this->sourceEntity = $this->requestHandler->getCurrentSourceEntity(['webform', 'webform_submission']);
    }

    if ($webform->getSetting('token_update') && ($token = $this->getRequest()->query->get('token'))) {
      if ($webform_submissions_token = $this->storage->loadByProperties(['token' => $token])) {
        $entity = reset($webform_submissions_token);
      }
    }
    elseif ($webform_submission_draft = $this->storage->loadDraft($webform, $this->sourceEntity, $this->currentUser())) {
      $entity = $webform_submission_draft;
    }

    $this->messageManager->setWebform($webform);
    $this->messageManager->setWebformSubmission($entity);
    $this->messageManager->setSourceEntity($this->sourceEntity);
    return parent::setEntity($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    parent::copyFormValuesToEntity($entity, $form, $form_state);
    // Set current page.
    if ($current_page = $this->getCurrentPage($form, $form_state)) {
      $entity->setCurrentPage($current_page);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $disable_pages = FALSE) {
    $this->disablePages = $disable_pages;

    /* @var $webform_submission \Drupal\webform\WebformSubmissionInterface */
    $webform_submission = $this->getEntity();
    $webform = $this->getWebform();

    // Add this webform and the webform settings to the cache tags.
    $form['#cache']['tags'][] = 'config:webform.settings';

    // This submission webform is based on the current URL, and hence it depends
    // on the 'url' cache context.
    $form['#cache']['contexts'][] = 'url';

    // All anonymous submissions are tracked in the $_SESSION.
    // @see \Drupal\webform\WebformSubmissionStorage::setAnonymousSubmission
    if ($this->currentUser()->isAnonymous()) {
      $form['#cache']['contexts'][] = 'session';
    }

    // Add the webform as a cacheable dependency.
    \Drupal::service('renderer')->addCacheableDependency($form, $webform);

    // Display status messages.
    $this->displayMessages($form, $form_state);

    // Build the webform.
    $form = parent::buildForm($form, $form_state);

    // Alter webform via webform handler.
    $this->getWebform()->invokeHandlers('alterForm', $form, $form_state, $webform_submission);

    // Call custom webform alter hook.
    $form_id = $this->getFormId();
    $this->thirdPartySettingsManager->alter('webform_submission_form', $form, $form_state, $form_id);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    // Add a reference to the webform's id to the $form render array.
    $form['#webform_id'] = $this->getWebform()->id();

    // Check for a custom webform, track it, and return it.
    if ($custom_form = $this->getCustomForm($form, $form_state)) {
      $custom_form['#custom_form'] = TRUE;
      return $custom_form;
    }

    /* @var $webform_submission \Drupal\webform\WebformSubmissionInterface */
    $webform_submission = $this->getEntity();
    $webform = $this->getWebform();

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

    // Assets: Add custom shared and webform specific CSS and JS.
    // @see webform_css_alter()
    // @see webform_js_alter()
    $assets = $webform->getAssets();
    foreach ($assets as $type => $value) {
      if ($value) {
        $form['#attached']['library'][] = "webform/webform.assets.$type";
        $form['#attached']['drupalSettings']['webform']['assets'][$type][$webform->id()] = md5($value);
      }
    }

    // Attach disable back button.
    if ($this->getWebformSetting('form_disable_back')) {
      $form['#attached']['library'][] = 'webform/webform.form.disable_back';
    }

    // Unsaved: Add unsaved message.
    if ($this->getWebformSetting('form_unsaved')) {
      $form['#attributes']['class'][] = 'js-webform-unsaved';
      $form['#attached']['library'][] = 'webform/webform.form.unsaved';
      $current_page = $this->getCurrentPage($form, $form_state);
      if ($current_page && ($current_page != $this->getFirstPage($form, $form_state))) {
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
    if ($this->getPages($form, $form_state)) {
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
  protected function getCustomForm(array $form, FormStateInterface $form_state) {
    /* @var $webform_submission \Drupal\webform\WebformSubmissionInterface */
    $webform_submission = $this->getEntity();
    $webform = $this->getWebform();

    // Exit if elements are broken, usually occurs when elements YAML is edited
    // directly in the export config file.
    if (!$webform_submission->getWebform()->getElementsInitialized()) {
      $this->messageManager->display(WebformMessageManagerInterface::FORM_EXCEPTION, 'warning');
      return $form;
    }

    // Handle webform with managed file upload but saving of submission is disabled.
    if ($webform->hasManagedFile() && !empty($this->getWebformSetting('results_disabled'))) {
      $this->messageManager->log(WebformMessageManagerInterface::FORM_FILE_UPLOAD_EXCEPTION, 'notice');
      $this->messageManager->display(WebformMessageManagerInterface::FORM_EXCEPTION, 'warning');
      return $form;
    }

    // Display inline confirmation message with back to link which is rendered
    // via the controller.
    if ($this->getWebformSetting('confirmation_type') == 'inline' && $this->getRequest()->query->get('webform_id') == $webform->id()) {
      $webform_controller = new WebformController($this->renderer, $this->requestHandler, $this->tokenManager);
      $form['confirmation'] = $webform_controller->confirmation($this->getRequest(), $webform);
      return $form;
    }

    // Don't display webform if it is closed.
    if ($webform_submission->isNew() && $webform->isClosed()) {
      // If the current user can update any submission just display the closed
      // message and still allow them to create new submissions.
      if ($webform->isTemplate() && $webform->access('duplicate')) {
        if (!$this->isModalDialog()) {
          $this->messageManager->display(WebformMessageManagerInterface::TEMPLATE_PREVIEW, 'warning');
        }
      }
      elseif ($webform->access('submission_update_any')) {
        $this->messageManager->display(WebformMessageManagerInterface::ADMIN_ACCESS, 'warning');
      }
      else {
        if ($webform->isOpening()) {
          $form['opening'] = $this->messageManager->build(WebformMessageManagerInterface::FORM_OPEN_MESSAGE);
        }
        else {
          $form['closed'] = $this->messageManager->build(WebformMessageManagerInterface::FORM_CLOSE_MESSAGE);
        }
        return $form;
      }
    }

    // Disable this webform if confidential and user is logged in.
    if ($this->isConfidential() && $this->currentUser()->isAuthenticated() && $this->entity->isNew()) {
      $this->messageManager->display(WebformMessageManagerInterface::FORM_CONFIDENTIAL_MESSAGE, 'warning');
      return $form;
    }

    // Disable this webform if submissions are not being saved to the database or
    // passed to a WebformHandler.
    if ($this->getWebformSetting('results_disabled') && !$this->getWebformSetting('results_disabled_ignore') && !$webform->getHandlers(NULL, TRUE, WebformHandlerInterface::RESULTS_PROCESSED)->count()) {
      $this->messageManager->log(WebformMessageManagerInterface::FORM_SAVE_EXCEPTION, 'error');
      if ($this->currentUser()->hasPermission('administer webform')) {
        // Display error to admin but allow them to submit the broken webform.
        $this->messageManager->display(WebformMessageManagerInterface::FORM_SAVE_EXCEPTION, 'error');
        $this->messageManager->display(WebformMessageManagerInterface::ADMIN_ACCESS, 'warning');
      }
      else {
        // Display exception message to users.
        $this->messageManager->display(WebformMessageManagerInterface::FORM_EXCEPTION, 'warning');
        return $form;
      }
    }

    // Check total limit.
    if ($this->checkTotalLimit()) {
      $this->messageManager->display(WebformMessageManagerInterface::LIMIT_TOTAL_MESSAGE);
      if ($webform->access('submission_update_any')) {
        $this->messageManager->display(WebformMessageManagerInterface::ADMIN_ACCESS, 'warning');
      }
      else {
        return $form;
      }
    }

    // Check user limit.
    if ($this->checkUserLimit()) {
      $this->messageManager->display(WebformMessageManagerInterface::LIMIT_USER_MESSAGE, 'warning');
      if ($webform->access('submission_update_any')) {
        $this->messageManager->display(WebformMessageManagerInterface::ADMIN_ACCESS, 'warning');
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
    if ($this->isGet() && $this->isRoute('webform.test')) {
      $this->messageManager->display(WebformMessageManagerInterface::SUBMISSION_TEST, 'warning');

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

    // Display loaded or saved draft message.
    if ($webform_submission->isDraft()) {
      if ($form_state->get('draft_saved')) {
        $this->messageManager->display(WebformMessageManagerInterface::SUBMISSION_DRAFT_SAVED);
        $form_state->set('draft_saved', FALSE);
      }
      elseif ($this->isGet()) {
        $this->messageManager->display(WebformMessageManagerInterface::SUBMISSION_DRAFT_LOADED);
      }
    }

    // Display link to previous submissions message when user is adding a new
    // submission.
    if ($this->isGet()
      && $this->getWebformSetting('form_previous_submissions', FALSE)
      && ($this->isRoute('webform.canonical') || $this->isWebformEntityReferenceFromSourceEntity())
      && ($webform->access('submission_view_own') || $this->currentUser()->hasPermission('view own webform submission'))
      && ($previous_total = $this->storage->getTotal($webform, $this->sourceEntity, $this->currentUser()))
    ) {
      if ($previous_total > 1) {
        $this->messageManager->display(WebformMessageManagerInterface::SUBMISSIONS_PREVIOUS);
      }
      elseif ($webform_submission->id() != $this->storage->getLastSubmission($webform, $this->sourceEntity, $this->currentUser())->id()) {
        $this->messageManager->display(WebformMessageManagerInterface::SUBMISSION_PREVIOUS);
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
    $webform = $this->getWebform();

    $element = parent::actions($form, $form_state);

    /* @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
    $preview_mode = $this->getWebformSetting('preview');

    // Remove the delete button from the webform submission webform.
    unset($element['delete']);

    // Mark the submit action as the primary action, when it appears.
    $element['submit']['#button_type'] = 'primary';
    $element['submit']['#attributes'] = $this->getWebformSetting('form_submit_attributes');
    $element['submit']['#attributes']['class'][] = 'webform-button--submit';
    $element['submit']['#weight'] = 10;

    // Customize the submit button's label for new submissions only.
    if ($webform_submission->isNew() || $webform_submission->isDraft()) {
      $element['submit']['#value'] = $this->getWebformSetting('form_submit_label');
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
      $current_page_element = $this->getWebform()->getPage($current_page);

      $is_first_page = ($current_page == $this->getFirstPage($form, $form_state)) ? TRUE : FALSE;
      $is_last_page = (in_array($current_page, ['preview', 'complete', $this->getLastPage($form, $form_state)])) ? TRUE : FALSE;
      $is_preview_page = ($current_page == 'preview');
      $is_next_page_preview = ($this->getNextPage($form, $form_state) == 'preview') ? TRUE : FALSE;
      $is_next_page_complete = ($this->getNextPage($form, $form_state) == 'complete') ? TRUE : FALSE;
      $is_next_page_optional_preview = ($is_next_page_preview && $preview_mode != DRUPAL_REQUIRED);

      // Only show that save button if this is the last page of the wizard or
      // on preview page or right before the optional preview.
      $element['submit']['#access'] = $is_last_page || $is_preview_page || $is_next_page_optional_preview || $is_next_page_complete;

      if (!$is_first_page) {
        if ($is_preview_page) {
          $previous_attributes = $this->getWebformSetting('preview_prev_button_attributes');
          $previous_label = $this->getWebformSetting('preview_prev_button_label');
        }
        else {
          $previous_attributes = $this->getWebformSetting('wizard_prev_button_attributes');
          $previous_label = (isset($current_page_element['#prev_button_label'])) ? $current_page_element['#prev_button_label'] : $this->getWebformSetting('wizard_prev_button_label');
        }
        $previous_attributes['class'][] = 'js-webform-novalidate';
        $previous_attributes['class'][] = 'webform-button--previous';
        $element['previous'] = [
          '#type' => 'submit',
          '#value' => $previous_label,
          '#validate' => ['::noValidate'],
          '#submit' => ['::previous'],
          '#attributes' => $previous_attributes,
          '#weight' => 0,
        ];
      }

      if (!$is_last_page && !$is_next_page_complete) {
        if ($is_next_page_preview) {
          $next_attributes = $this->getWebformSetting('preview_next_button_attributes');
          $next_label = $this->getWebformSetting('preview_next_button_label');
          $next_attributes['class'][] = 'webform-button--preview';
        }
        else {
          $next_attributes = $this->getWebformSetting('wizard_next_button_attributes');
          $next_label = (isset($current_page_element['#next_button_label'])) ? $current_page_element['#next_button_label'] : $this->getWebformSetting('wizard_next_button_label');
          $next_attributes['class'][] = 'webform-button--next';
        }
        $element['next'] = [
          '#type' => 'submit',
          '#value' => $next_label,
          '#validate' => ['::validateForm'],
          '#submit' => ['::next'],
          '#attributes' => $next_attributes,
          '#weight' => 1,
        ];
      }
    }

    // Draft.
    if ($this->draftEnabled()) {
      $draft_attributes = $this->getWebformSetting('draft_button_attributes');
      $draft_attributes['class'][] = 'webform-button--draft';
      $element['draft'] = [
        '#type' => 'submit',
        '#value' => $this->getWebformSetting('draft_button_label'),
        '#validate' => ['::draft'],
        '#submit' => ['::submitForm', '::save', '::rebuild'],
        '#attributes' => $draft_attributes,
        '#weight' => -10,
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
    $form_state->set('current_page', $this->getNextPage($form, $form_state));
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
    $form_state->set('current_page', $this->getPreviousPage($form, $form_state));
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
    if ($this->draftEnabled() && $this->getWebformSetting('draft_auto_save') && !$this->entity->isCompleted()) {
      $form_state->setValue('in_draft', TRUE);

      $this->submitForm($form, $form_state);
      $this->save($form, $form_state);
    }
    else {
      $this->submitForm($form, $form_state);
    }

    $this->rebuild($form, $form_state);
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
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
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

    // Validate webform via webform handler.
    $this->getWebform()->invokeHandlers('validateForm', $form, $form_state, $this->entity);

    // Webform validate handlers (via form['#validate']) are not called when
    // #validate handlers are attached to the trigger element
    // (ie submit button), so we need to manually call $form['validate']
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
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /* @var $webform_submission \Drupal\webform\WebformSubmissionInterface */
    $webform_submission = $this->entity;
    $webform = $webform_submission->getWebform();

    // Get elements values from webform submission.
    $values = array_intersect_key(
      $form_state->getValues(),
      $webform->getElementsInitializedFlattenedAndHasValue()
    );

    // Serialize the values as YAML and merge existing data.
    $webform_submission->setData($values + $webform_submission->getData());

    parent::submitForm($form, $form_state);

    // Submit webform via webform handler.
    $this->getWebform()->invokeHandlers('submitForm', $form, $form_state, $webform_submission);
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
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $webform = $this->getWebform();
    /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
    $webform_submission = $this->getEntity();

    // Make sure the uri and remote addr are set correctly because
    // AJAX requests via 'managed_file' uploads can cause these values to be
    // reset.
    if ($webform_submission->isNew()) {
      $webform_submission->set('uri', preg_replace('#^' . base_path() . '#', '/', $this->getRequest()->getRequestUri()));
      $webform_submission->set('remote_addr', ($this->isConfidential()) ? '' : $this->getRequest()->getClientIp());
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
      $pages = $this->getWebform()->getPages($this->disablePages);
      foreach ($pages as &$page) {
        $page['#access'] = TRUE;
      }
      $form_state->set('pages', $pages);
    }

    // Get pages and check #access.
    $pages = $form_state->get('pages');
    foreach ($pages as $page_key => $page) {
      if ($page['#access'] === FALSE) {
        unset($pages[$page_key]);
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
      $pages = $this->getPages($form, $form_state);
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
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return null|string
   *   The first page's key.
   */
  protected function getFirstPage(array &$form, FormStateInterface $form_state) {
    $pages = $this->getPages($form, $form_state);
    return WebformArrayHelper::getFirstKey($pages);
  }

  /**
   * Get last page's key.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return null|string
   *   The last page's key.
   */
  protected function getLastPage(array &$form, FormStateInterface $form_state) {
    $pages = $this->getPages($form, $form_state);
    return WebformArrayHelper::getLastKey($pages);
  }

  /**
   * Get next page's key.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return null|string
   *   The next page's key. NULL if there is no next page.
   */
  protected function getNextPage(array &$form, FormStateInterface $form_state) {
    $pages = $this->getPages($form, $form_state);
    $current_page = $this->getCurrentPage($form, $form_state);
    return WebformArrayHelper::getNextKey($pages, $current_page);
  }

  /**
   * Get previous page's key.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return null|string
   *   The previous page's key. NULL if there is no previous page.
   */
  protected function getPreviousPage(array &$form, FormStateInterface $form_state) {
    $pages = $this->getPages($form, $form_state);
    $current_page = $this->getCurrentPage($form, $form_state);
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
    if ($current_page == 'preview') {
      // Hide elements.
      $form['elements']['#access'] = FALSE;

      // Display preview message.
      $this->messageManager->display(WebformMessageManagerInterface::FORM_PREVIEW_MESSAGE, 'warning');

      // Build preview.
      $form['preview'] = [
        '#theme' => 'webform_submission_html',
        '#webform_submission' => $this->entity,
      ];
    }
    else {
      // Get all pages so that we can also hide skipped pages.
      $pages = $this->getWebform()->getPages($this->disablePages);
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
    if ($query = $this->getRequest()->query->all()) {
      $route_options['query'] = $query;
    }

    // Default to displaying a confirmation message on this page.
    $state = $webform_submission->getState();
    if ($state == WebformSubmissionInterface::STATE_UPDATED) {
      $this->messageManager->display(WebformMessageManagerInterface::SUBMISSION_UPDATED);
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
      case 'page':
        $redirect_url = $this->requestHandler->getUrl($webform, $this->sourceEntity, 'webform.confirmation', $route_options);
        $form_state->setRedirectUrl($redirect_url);
        return;

      case 'url':
      case 'url_message':
        $confirmation_url = trim($this->getWebformSetting('confirmation_url', ''));
        // Remove base path from root-relative URL.
        // Only applies for Drupa; sites within a sub directory.
        $confirmation_url = preg_replace('/^' . preg_quote(base_path(), '/') . '/', '/', $confirmation_url);

        // Get system path.
        /** @var \Drupal\Core\Path\AliasManagerInterface $path_alias_manager */
        $path_alias_manager = \Drupal::service('path.alias_manager');
        $confirmation_url = $path_alias_manager->getPathByAlias($confirmation_url);

        if ($redirect_url = \Drupal::pathValidator()->getUrlIfValid($confirmation_url)) {
          if ($confirmation_type == 'url_message') {
            $this->messageManager->display(WebformMessageManagerInterface::SUBMISSION_CONFIRMATION);
          }
          $this->setTrustedRedirectUrl($form_state, $redirect_url);
          return;
        }

        // If confirmation URL is invalid display message.
        $this->messageManager->display(WebformMessageManagerInterface::SUBMISSION_CONFIRMATION);
        $route_options['query']['webform_id'] = $webform->id();
        break;

      case 'inline':
        $route_options['query']['webform_id'] = $webform->id();
        break;

      case 'message':
      default:
        if (!$this->messageManager->display(WebformMessageManagerInterface::SUBMISSION_CONFIRMATION)) {
          $this->messageManager->display(WebformMessageManagerInterface::SUBMISSION_DEFAULT_CONFIRMATION);
        }
        break;
    }

    $form_state->setRedirect($route_name, $route_parameters, $route_options);
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

      // ISSUE: Hidden elements still need to call #element_validate because
      // certain elements, including managed_file, checkboxes, password_confirm,
      // etc..., will also massage the submitted values via #element_validate.
      //
      // SOLUTION: Call #element_validate for all hidden elements but suppresses
      // #element_validate errors.
      //
      // Set hidden element #after_build handler.
      $element['#after_build'][] = [get_class($this), 'hiddenElementAfterBuild'];

      $this->hideElements($element);
    }
  }

  /**
   * Webform element #after_build callback: Wrap #element_validate so that we suppress element validation errors.
   */
  public static function hiddenElementAfterBuild(array $element, FormStateInterface $form_state) {
    if (!empty($element['#element_validate'])) {
      $element['#_element_validate'] = $element['#element_validate'];
      $element['#element_validate'] = [[get_called_class(), 'hiddenElementValidate']];
    }
    return $element;
  }

  /**
   * Webform element #element_validate callback: Execute #element_validate and suppress errors.
   */
  public static function hiddenElementValidate(array $element, FormStateInterface $form_state) {
    // Create a temp webform state that will capture and suppress all element
    // validation errors.
    $temp_form_state = clone $form_state;
    $temp_form_state->setLimitValidationErrors([]);

    // @see \Drupal\Core\Form\FormValidator::doValidateForm
    foreach ($element['#_element_validate'] as $callback) {
      $complete_form = &$form_state->getCompleteForm();
      call_user_func_array($form_state->prepareCallback($callback), [&$element, &$temp_form_state, &$complete_form]);
    }

    // Get the temp webform state's values.
    $form_state->setValues($temp_form_state->getValues());
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
    $account = $this->currentUser();
    $webform = $this->getWebform();

    // Anonymous users can't have limits.
    if ($account->isAnonymous()) {
      return FALSE;
    }

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

    /** @var WebformSubmissionInterface $webform_submission */
    $webform_submission = $this->getEntity();

    // Once a form is completed drafts are no longer applicable.
    if ($webform_submission->isCompleted()) {
      return FALSE;
    }

    switch ($this->getWebformSetting('draft')) {
      case WebformInterface::DRAFT_ENABLED_ALL:
        return TRUE;

      case WebformInterface::DRAFT_ENABLED_AUTHENTICATED:
        return $webform_submission->getOwner()->isAuthenticated();

      case WebformInterface::DRAFT_ENABLED_NONE:
      default:
        return FALSE;
    }
  }

  /**
   * Returns the webform confidential indicator.
   *
   * @return bool
   *   TRUE if the webform is confidential .
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

    $webform_field_name = WebformEntityReferenceItem::getEntityWebformFieldName($this->sourceEntity);
    if (!$webform_field_name) {
      return FALSE;
    }

    return $this->sourceEntity->$webform_field_name->target_id == $this->getWebform()->id();
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

}
