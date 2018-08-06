<?php

/**
 * @file
 * Preprocessors and helper functions to make theming easier.
 */

use Drupal\Core\Link;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Render\Element\RenderElement;
use Drupal\Core\Template\Attribute;
use Drupal\image\Entity\ImageStyle;
use Drupal\webform\Element\WebformCodeMirror;
use Drupal\webform\Element\WebformHtmlEditor;
use Drupal\webform\WebformMessageManagerInterface;
use Drupal\webform\Utility\WebformYaml;
use Drupal\webform\Utility\WebformDateHelper;
use Drupal\webform\Utility\WebformDialogHelper;
use Drupal\webform\Utility\WebformElementHelper;

/**
 * Prepares variables for webform help.
 *
 * Default template: webform_help.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - title: Help title.
 *   - content: Help content.
 */
function template_preprocess_webform_help(array &$variables) {
  /** @var \Drupal\webform\WebformHelpManagerInterface $help_manager */
  $help_manager = \Drupal::service('webform.help_manager');

  $help_info = $variables['info'];

  $variables += $help_info;

  $help = [];
  if (is_array($help_info['content'])) {
    $help['content'] = $help_info['content'];
  }
  else {
    $help['content'] = [
      '#markup' => $help_info['content'],
    ];
  }

  // Build watch video link.
  // If help info load the video else this is the video info.
  $video_info = (isset($help_info['video_id'])) ? $help_manager->getVideo($help_info['video_id']) : $help_info;
  if (!empty($video_info['youtube_id'])) {
    $classes = ['button', 'button-action', 'button--small', 'button-webform-play'];

    $video_display = \Drupal::config('webform.settings')->get('ui.video_display');
    switch ($video_display) {
      case 'dialog':
        $variables['#attached']['library'][] = 'webform/webform.ajax';
        $help['link'] = [
          '#type' => 'link',
          '#title' => t('Watch video'),
          '#url' => Url::fromRoute('webform.help.video', ['id' => str_replace('_', '-', $video_info['id'])]),
          '#attributes' => WebformDialogHelper::getModalDialogAttributes(1000, $classes),
          '#prefix' => ' ',
        ];
        break;

      case 'link':
        $help['link'] = [
          '#type' => 'link',
          '#title' => t('Watch video'),
          '#url' => Url::fromUri('https://youtu.be/' . $video_info['youtube_id']),
          '#attributes' => ['class' => $classes],
          '#prefix' => ' ',
        ];
        break;

      case 'hidden':
      default:
        break;
    }
  }

  $variables['help'] = $help;
}

/**
 * Prepares variables for webform templates.
 *
 * Default template: webform.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties of the element.
 *     Properties used: #action, #method, #attributes, #webform_children.
 */
function template_preprocess_webform(array &$variables) {
  template_preprocess_form($variables);
}

/**
 * Prepares variables for webform actions templates.
 *
 * Default template: webform-actions.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties and buttons.
 *
 * @see \Drupal\webform\WebformSubmissionForm::actionsElement
 * @see \Drupal\webform\WebformSubmissionForm::actions
 */
function template_preprocess_webform_actions(array &$variables) {
  $element = $variables['element'];
  // Buttons include submit, previous, next, and draft.
  foreach (Element::children($element) as $key) {
    $variables[$key] = $element[$key];
  }
}

/**
 * Prepares variables for webform preview templates.
 *
 * Default template: webform-preview.html.twig.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - webform_submission: A webform submission.
 */
function template_preprocess_webform_preview(array &$variables) {
  /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
  $webform_submission = $variables['webform_submission'];
  $webform = $webform_submission->getWebform();

  $variables['preview'] = [
    '#theme' => 'webform_submission_html',
    '#webform_submission' => $webform_submission,
    '#excluded_elements' => $webform->getSetting('preview_excluded_elements'),
    '#exclude_empty' => $webform->getSetting('preview_exclude_empty'),
  ];

  $settings = $webform->getSettings();

  // Set attributes.
  $variables['attributes'] = new Attribute($settings['preview_attributes']);
}

/**
 * Prepares variables for webform confirmation templates.
 *
 * Default template: webform-confirmation.html.twig.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - webform: A webform.
 *   - webform_submission: A webform submission.
 *   - source_entity: A webform submission source entity.
 */
function template_preprocess_webform_confirmation(array &$variables) {
  /** @var \Drupal\webform\WebformInterface $webform */
  $webform = $variables['webform'];
  /** @var \Drupal\Core\Entity\EntityInterface $source_entity */
  $source_entity = $variables['source_entity'];
  /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
  $webform_submission = $variables['webform_submission'];

  /** @var \Drupal\webform\WebformMessageManagerInterface $message_manager */
  $message_manager = \Drupal::service('webform.message_manager');
  $message_manager->setWebformSubmission($webform_submission);
  // Must set webform and source entity because webform submission could be
  // NULL.
  $message_manager->setWebform($webform);
  $message_manager->setSourceEntity($source_entity);

  // Assets: Add custom shared and webform specific CSS and JS.
  // @see webform_css_alter()
  // @see webform_js_alter()
  $assets = $webform->getAssets();
  foreach ($assets as $type => $value) {
    if ($value) {
      $variables['#attached']['library'][] = "webform/webform.assets.$type";
      $variables['#attached']['drupalSettings']['webform']['assets'][$type][$webform->id()] = md5($value);
    }
  }

  $settings = $webform->getSettings();

  // Set progress.
  if ($webform->getPages() && $settings['wizard_confirmation'] && ($settings['wizard_progress_bar'] || $settings['wizard_progress_pages'] || $settings['wizard_progress_percentage'])) {
    $variables['progress'] = [
      '#theme' => 'webform_progress',
      '#webform' => $webform,
      '#current_page' => 'webform_confirmation',
    ];
  }

  // Set message.
  $variables['message'] = $message_manager->build(WebformMessageManagerInterface::SUBMISSION_CONFIRMATION);

  // Set attributes.
  $variables['attributes'] = new Attribute($settings['confirmation_attributes']);

  // Set back.
  $variables['back'] = $settings['confirmation_back'];
  $variables['back_label'] = $settings['confirmation_back_label'] ?: \Drupal::config('webform.settings')->get('settings.default_confirmation_back_label');
  $variables['back_attributes'] = new Attribute($settings['confirmation_back_attributes']);

  // Get query string parameters.
  $query = \Drupal::request()->query->all();

  // Add Ajax trigger to back link.
  // @see \Drupal\webform\WebformSubmissionForm::getCustomForm
  // @see Drupal.behaviors.webformConfirmationBackAjax (js/webform.ajax.js)
  $is_ajax = (!empty($query['ajax_form'])) ? TRUE : FALSE;
  if (!empty($is_ajax)) {
    $variables['back_attributes']->addClass('js-webform-confirmation-back-link-ajax');
  }

  // Apply all passed query string parameters to the 'Back to form' link.
  unset($query['webform_id'], $query['ajax_form'], $query['_wrapper_format'], $query['token'], $query['page']);
  $options = ($query) ? ['query' => $query] : [];

  // Set back_url.
  if ($source_entity && $source_entity->hasLinkTemplate('canonical')) {
    $variables['back_url'] = $source_entity->toUrl('canonical', $options)->toString();
  }
  elseif ($webform_submission) {
    $source_url = $webform_submission->getSourceUrl();
    $query = $source_url->getOption('query');
    unset($query['webform_id'], $query['ajax_form'], $query['_wrapper_format'], $query['token'], $query['page']);
    $source_url->setOption('query', $query);
    $variables['back_url'] = $source_url->toString();
  }
  else {
    $variables['back_url'] = $webform->toUrl('canonical', $options)->toString();
  }

  $webform->invokeHandlers('preprocessConfirmation', $variables);
}

/**
 * Prepares variables for webform submission navigation templates.
 *
 * Default template: webform-submission-navigation.html.twig.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - webform_submission: A webform submission.
 *   - rel: Webform submission link template.
 *          (canonical, edit-form, resend-form, html, text, or yaml).
 */
function template_preprocess_webform_submission_navigation(array &$variables) {
  /** @var \Drupal\webform\WebformRequestInterface $request_handler */
  $request_handler = \Drupal::service('webform.request');
  /** @var \Drupal\webform\WebformSubmissionStorageInterface $webform_submission_storage */
  $webform_submission_storage = \Drupal::entityTypeManager()->getStorage('webform_submission');

  /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
  $webform_submission = $variables['webform_submission'];

  // Get the route name, parameters, and source entity for the current page.
  // This ensures that the user stays within their current context as they are
  // paging through submission.
  $route_name = \Drupal::routeMatch()->getRouteName();
  $route_parameters = \Drupal::routeMatch()->getRawParameters()->all();
  $source_entity = $request_handler->getCurrentSourceEntity('webform_submission');

  if (strpos(\Drupal::routeMatch()->getRouteName(), 'webform.user.submission') !== FALSE) {
    $account = \Drupal::currentUser();
    $options = ['in_draft' => FALSE];
  }
  else {
    $account = NULL;
    $options = ['in_draft' => NULL];
  }

  if ($previous_submission = $webform_submission_storage->getPreviousSubmission($webform_submission, $source_entity, $account, $options)) {
    $variables['prev_url'] = Url::fromRoute($route_name, ['webform_submission' => $previous_submission->id()] + $route_parameters)->toString();
  }
  if ($next_submission = $webform_submission_storage->getNextSubmission($webform_submission, $source_entity, $account, $options)) {
    $variables['next_url'] = Url::fromRoute($route_name, ['webform_submission' => $next_submission->id()] + $route_parameters)->toString();
  }

  $variables['#attached']['library'][] = 'webform/webform.navigation';

  // Never cache navigation because previous and next submission will change
  // as submissions are added and deleted.
  $variables['#cache'] = ['max-age' => 0];
}

/**
 * Prepares variables for webform submission template.
 *
 * Default template: webform-submission.html.twig.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - webform_submission: A webform submission.
 */
function template_preprocess_webform_submission(array &$variables) {
  /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
  $webform_submission = $variables['webform_submission'];
  /** @var \Drupal\Core\Entity\EntityInterface $source_entity */
  $source_entity = $variables['source_entity'];
  $type = $variables['type'];

  // Make sure $type is supported, else use the HTML template and
  // display submission information because the submission is most likely
  // being rendered without context.
  if (in_array($type, ['html', 'table', 'text', 'yaml'])) {
    $submission_template = 'webform_submission_' . $type;
  }
  else {
    $submission_template = 'webform_submission_html';
  }

  // Information.
  $variables['information'] = [
    '#theme' => 'webform_submission_information',
    '#webform_submission' => $webform_submission,
    '#source_entity' => $source_entity,
  ];

  // Submission.
  $variables['data'] = [
    '#theme' => $submission_template,
    '#webform_submission' => $webform_submission,
    '#source_entity' => $source_entity,
  ];

  // Wrap plain text and YAML in CodeMirror view widget.
  if (in_array($type, ['text', 'yaml'])) {
    $variables['data'] = [
      '#theme' => 'webform_codemirror',
      '#code' => $variables['data'],
      '#type' => $type,
    ];
  }

  $variables['#attached']['library'][] = 'webform/webform.admin';
}

/**
 * Prepares variables for webform submission information template.
 *
 * Default template: webform-submission-information.html.twig.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - webform_submission: A webform submission.
 */
function template_preprocess_webform_submission_information(array &$variables) {
  /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
  $webform_submission = $variables['webform_submission'];
  $webform = $webform_submission->getWebform();

  $variables['webform_id'] = $webform->id();
  $variables['serial'] = $webform_submission->serial();
  $variables['sid'] = $webform_submission->id();
  $variables['uuid'] = $webform_submission->uuid();
  $variables['is_draft'] = $webform_submission->isDraft() ? t('Yes') : t('No');
  $variables['current_page'] = $webform_submission->getCurrentPageTitle();
  $variables['remote_addr'] = $webform_submission->getRemoteAddr();
  $variables['submitted_by'] = $webform_submission->getOwner()->toLink();
  $variables['webform'] = $webform->toLink();
  $variables['created'] = WebformDateHelper::format($webform_submission->getCreatedTime());
  $variables['completed'] = WebformDateHelper::format($webform_submission->getCompletedTime());
  $variables['changed'] = WebformDateHelper::format($webform_submission->getChangedTime());
  $variables['sticky'] = $webform_submission->isSticky() ? t('Yes') : '';
  $variables['notes'] = $webform_submission->getNotes();

  // @see \Drupal\Core\Field\Plugin\Field\FieldFormatter\LanguageFormatter::viewValue()
  $languages = \Drupal::languageManager()->getNativeLanguages();
  $langcode = $webform_submission->get('langcode')->value;
  $variables['language'] = isset($languages[$langcode]) ? $languages[$langcode]->getName() : $langcode;

  if ($source_url = $webform_submission->getSourceUrl()) {
    $variables['uri'] = Link::fromTextAndUrl($source_url->setAbsolute(FALSE)->toString(), $source_url);;
  }

  if ($webform->getSetting('token_update')) {
    $token_url = $webform_submission->getTokenUrl();
    $variables['token_update'] = Link::fromTextAndUrl($token_url->setAbsolute(FALSE)->toString(), $token_url);
  }

  if (($source_entity = $webform_submission->getSourceEntity()) && $source_entity->hasLinkTemplate('canonical')) {
    $variables['submitted_to'] = $source_entity->toLink();
  }

  $variables['submissions_view'] = FALSE;
  if ($webform->access('submission_view_any')) {
    $variables['submissions_view'] = TRUE;
  }
  elseif ($source_entity) {
    $entity_type = $source_entity->getEntityTypeId();
    if (\Drupal::currentUser()->hasPermission("view webform node submissions any $entity_type")) {
      $variables['submissions_view'] = TRUE;
    }
    elseif (\Drupal::currentUser()->hasPermission("view webform node submissions own $entity_type")
      && method_exists($source_entity, 'getOwnerId')
      && $source_entity->getOwnerId() == \Drupal::currentUser()->id()
    ) {
      $variables['submissions_view'] = TRUE;
    }
  }
}

/**
 * Prepares variables for webform submission HTML template.
 *
 * Default template: webform-submission-html.html.twig.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - webform_submission: A webform submission.
 */
function template_preprocess_webform_submission_html(array &$variables) {
  /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
  $webform_submission = $variables['webform_submission'];

  /** @var \Drupal\webform\WebformSubmissionViewBuilderInterface $view_builder */
  $view_builder = \Drupal::entityTypeManager()->getViewBuilder('webform_submission');

  $webform = $webform_submission->getWebform();
  $elements = $webform->getElementsInitialized();
  $options = [
    'excluded_elements' => $variables['excluded_elements'],
    'exclude_empty' => $variables['exclude_empty'],
  ];
  $variables['html'] = $view_builder->buildElements($elements, $webform_submission, $options);
}

/**
 * Prepares variables for webform submission table template.
 *
 * Default template: webform-submission-table.html.twig.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - webform_submission: A webform submission.
 */
function template_preprocess_webform_submission_table(array &$variables) {
  /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
  $webform_submission = $variables['webform_submission'];

  /** @var \Drupal\webform\WebformSubmissionViewBuilderInterface $view_builder */
  $view_builder = \Drupal::entityTypeManager()->getViewBuilder('webform_submission');

  $webform = $webform_submission->getWebform();
  $elements = $webform->getElementsInitializedFlattenedAndHasValue();
  $options = [
    'excluded_elements' => $variables['excluded_elements'],
    'exclude_empty' => $variables['exclude_empty'],
  ];
  $variables['table'] = $view_builder->buildTable($elements, $webform_submission, $options);
}

/**
 * Prepares variables for webform submission plain text template.
 *
 * Default template: webform-submission-text.html.twig.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - webform_submission: A webform submission.
 */
function template_preprocess_webform_submission_text(array &$variables) {
  /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
  $webform_submission = $variables['webform_submission'];

  /** @var \Drupal\webform\WebformSubmissionViewBuilderInterface $view_builder */
  $view_builder = \Drupal::entityTypeManager()->getViewBuilder('webform_submission');

  $webform = $webform_submission->getWebform();
  $elements = $webform->getElementsInitialized();
  $options = [
    'excluded_elements' => $variables['excluded_elements'],
    'exclude_empty' => $variables['exclude_empty'],
  ];
  $variables['text'] = $view_builder->buildElements($elements, $webform_submission, $options, 'text');
}

/**
 * Prepares variables for webform submission YAML template.
 *
 * Default template: webform-submission-yaml.html.twig.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - webform_submission: A webform submission.
 */
function template_preprocess_webform_submission_yaml(array &$variables) {
  /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
  $webform_submission = $variables['webform_submission'];

  $data = $webform_submission->toArray(TRUE, TRUE);
  $yaml = Yaml::encode($data);
  $yaml = WebformYaml::tidy($yaml);
  $variables['yaml'] = $yaml;
}

/**
 * Prepares variables for webform CodeMirror template.
 *
 * Default template: webform-codemirror.html.twig.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - code: The code.
 *   - type: The type of code.
 */
function template_preprocess_webform_codemirror(array &$variables) {
  $variables['mode'] = WebformCodeMirror::getMode($variables['type']);
  if (is_string($variables['code'])) {
    // Encode all HTML entities include tags.
    $variables['code'] = Markup::create(htmlentities($variables['code']));
  }
}

/**
 * Prepares variables for webform element base HTML template.
 *
 * Default template: webform-element-base-html.html.twig.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - element: The webform element.
 *   - value: The content for the element.
 *   - options Associative array of options for element.
 *     - multiline: Flag to determine if value spans multiple lines.
 *     - email: Flag to determine if element is for an email.
 */
function template_preprocess_webform_element_base_html(array &$variables) {
  $element = $variables['element'];

  // Set title.
  _template_progress_webform_set_title($variables);

  // Build form (item) element for HTML display.
  // @see form-element.html.twig
  // @see template_preprocess_form_element
  if (empty($variables['options']['email'])) {
    $type = $element['#type'];

    $variables['item'] = [
      '#type' => 'item',
      '#title' => $variables['title'],
      '#name' => $element['#webform_key'],
      // Use wrapper attributes for the id instead of #id,
      // this stops the <label> from having a 'for' attribute.
      '#wrapper_attributes' => [
        'id' => $element['#webform_id'],
        'class' => [
          'webform-element',
          'webform-element-type-' . str_replace('_', '-', $type),
        ],
      ],
    ];
    if (is_array($variables['value'])) {
      $variables['item']['value'] = $variables['value'];
    }
    else {
      $variables['item']['#markup'] = $variables['value'];
    }
  }
}

/**
 * Prepares variables for webform element base text template.
 *
 * Default template: webform-element-base-text.html.twig.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - element: The webform element.
 *   - value: The content for the element.
 *   - options Associative array of options for element.
 *     - multiline: Flag to determine if value spans multiple lines.
 *     - email: Flag to determine if element is for an email.
 */
function template_preprocess_webform_element_base_text(array &$variables) {
  // Set title.
  _template_progress_webform_set_title($variables, TRUE);
}

/**
 * Set variables title to element #title or #admin_title.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - element: The webform element.
 * @param bool $strip_tags
 *   Remove HTML tags from title.
 */
function _template_progress_webform_set_title(array &$variables, $strip_tags = FALSE) {
  $element = $variables['element'];

  // Set title.
  $variables['title'] = (WebformElementHelper::isTitleDisplayed($element)) ? $element['#title'] : NULL;
  if (empty($variables['title']) && !empty($element['#admin_title'])) {
    $variables['title'] = $element['#admin_title'];
  }

  // Strip all HTML tags from the title.
  if ($strip_tags) {
    $variables['title'] = strip_tags($variables['title']);
  }
}

/******************************************************************************/
// Progress templates.
/******************************************************************************/

/**
 * Prepares variables for webform 'wizard' progress template.
 *
 * Default template: webform-progress.html.twig.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - webform: A webform.
 *   - current_page: The current wizard page.
 */
function template_preprocess_webform_progress(array &$variables) {
  /** @var \Drupal\webform\WebformLibrariesManagerInterface $libaries_manager */
  $libaries_manager = \Drupal::service('webform.libraries_manager');

  /** @var \Drupal\webform\WebformInterface $webform */
  $webform = $variables['webform'];
  $current_page = $variables['current_page'];
  $operation = $variables['operation'];

  $pages = $webform->getPages($operation);

  $page_keys = array_keys($pages);
  $page_indexes = array_flip($page_keys);
  $current_index = $page_indexes[$current_page];

  $total = count($page_keys);

  if ($webform->getSetting('wizard_progress_bar')) {
    $variables['bar'] = [
      '#theme' => ($libaries_manager->isIncluded('progress-tracker')) ? 'webform_progress_tracker' : 'webform_progress_bar',
      '#webform' => $webform,
      '#current_page' => $current_page,
      '#operation' => $operation,
    ];
  }

  if ($webform->getSetting('wizard_progress_pages')) {
    $variables['summary'] = t('Page @start of @end', ['@start' => $current_index + 1, '@end' => $total]);
  }

  if ($webform->getSetting('wizard_progress_percentage')) {
    $variables['percentage'] = number_format(($current_index / ($total - 1)) * 100, 0) . '%';
  }
}

/**
 * Prepares variables for webform 'wizard' progress bar template.
 *
 * Default template: webform-progress-bar.html.twig.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - webform: A webform.
 *   - current_page: The current wizard page.
 */
function template_preprocess_webform_progress_bar(array &$variables) {
  _template_preprocess_webform_progress($variables);
}

/**
 * Prepares variables for webform 'wizard' progress tracker template.
 *
 * Default template: webform-progress-tracker.html.twig.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - webform: A webform.
 *   - current_page: The current wizard page.
 */
function template_preprocess_webform_progress_tracker(array &$variables) {
  _template_preprocess_webform_progress($variables);
}

/**
 * Prepares variables for webform 'wizard' progress bar & tracker template.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - webform: A webform.
 *   - current_page: The current wizard page.
 */
function _template_preprocess_webform_progress(array &$variables) {
  /** @var \Drupal\webform\WebformInterface $webform */
  $webform = $variables['webform'];
  $current_page = $variables['current_page'];
  $operation = $variables['operation'];

  $pages = $webform->getPages($operation);

  $page_keys = array_keys($pages);
  $page_indexes = array_flip($page_keys);
  $current_index = $page_indexes[$current_page];
  $variables['current_index'] = $current_index;

  // Reset the pages variable.
  $variables['progress'] = [];
  foreach ($pages as $page) {
    $variables['progress'][] = (isset($page['#title'])) ? $page['#title'] : '';
  }
}

/******************************************************************************/
// Element templates.
/******************************************************************************/

/**
 * Prepares variables for Webform message templates.
 *
 * Default template: webform-message.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties of the element.
 *     Properties used: #id, #attributes, #children.
 *
 * @see template_preprocess_container()
 */
function template_preprocess_webform_message(array &$variables) {
  $variables['has_parent'] = FALSE;
  $element = $variables['element'];
  // Ensure #attributes is set.
  $element += ['#attributes' => []];

  // Special handling for webform elements.
  if (isset($element['#array_parents'])) {
    // Assign an html ID.
    if (!isset($element['#attributes']['id'])) {
      $element['#attributes']['id'] = $element['#id'];
    }
    $variables['has_parent'] = TRUE;
  }

  $variables['message'] = $element['#message'];
  $variables['attributes'] = $element['#attributes'];
  if (isset($element['#closed'])) {
    $variables['closed'] = $element['#closed'];
  }
}

/**
 * Prepares variables for Webform horizontal rule templates.
 *
 * Default template: webform-horizontal-rule.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties of the element.
 *     Properties used: #id, #attributes.
 */
function template_preprocess_webform_horizontal_rule(array &$variables) {
  $element = $variables['element'];

  if (!empty($element['#id'])) {
    $variables['attributes']['id'] = $element['#id'];
  }
}

/**
 * Prepares variables for webform section element templates.
 *
 * Default template: webform-section.html.twig.
 *
 * Copied from: template_preprocess_fieldset()
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties of the element.
 *     Properties used: #attributes, #children, #description, #id, #title,
 *     #value.
 */
function template_preprocess_webform_section(array &$variables) {
  $element = $variables['element'];
  Element::setAttributes($element, ['id']);
  RenderElement::setAttributes($element);
  $variables['attributes'] = isset($element['#attributes']) ? $element['#attributes'] : [];
  $variables['prefix'] = isset($element['#field_prefix']) ? $element['#field_prefix'] : NULL;
  $variables['suffix'] = isset($element['#field_suffix']) ? $element['#field_suffix'] : NULL;
  $variables['title_display'] = isset($element['#title_display']) ? $element['#title_display'] : NULL;
  $variables['title_tag'] = isset($element['#title_tag']) ? $element['#title_tag'] : 'h2';
  $variables['title_attributes'] = isset($element['#title_attributes']) ? $element['#title_attributes'] : [];
  $variables['children'] = $element['#children'];
  $variables['required'] = !empty($element['#required']) ? $element['#required'] : NULL;

  // Allow markup in title.
  if (isset($element['#title']) && $element['#title'] !== '') {
    $variables['title'] = ['#markup' => $element['#title']];
  }

  // Add 'visually-hidden' class to title attributes.
  if ($variables['title_display'] == 'invisible') {
    $variables['title_attributes']['class'][] = 'visually-hidden';
  }
  $variables['title_attributes'] = new Attribute($variables['title_attributes']);

  if (!empty($element['#description'])) {
    $description_id = $element['#attributes']['id'] . '--description';
    $description_attributes['id'] = $description_id;
    $variables['description']['attributes'] = new Attribute($description_attributes);
    $variables['description']['content'] = $element['#description'];

    // Add the description's id to the fieldset aria attributes.
    $variables['attributes']['aria-describedby'] = $description_id;
  }

  // Suppress error messages.
  $variables['errors'] = NULL;
}

/******************************************************************************/
// Composite templates
/******************************************************************************/

/**
 * Prepares variables for composite element templates.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties of the element.
 */
function _template_preprocess_webform_composite(array &$variables) {
  $element = $variables['element'];

  // Copy all accessible element children to content.
  foreach (Element::children($element) as $key) {
    if (!isset($element[$key]['#access']) || $element[$key]['#access']) {
      $variables['content'][$key] = $element[$key];
    }
  }

  // Set flexbox variable used for multi column element layout.
  $variables['flexbox'] = (isset($element['#flexbox'])) ? $element['#flexbox'] : FALSE;
}

/**
 * Prepares variables for address composite element templates.
 *
 * Default template: webform-element-address.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties of the element.
 */
function template_preprocess_webform_composite_address(array &$variables) {
  _template_preprocess_webform_composite($variables);
}

/**
 * Prepares variables for contact composite element templates.
 *
 * Default template: webform-element-contact.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties of the element.
 */
function template_preprocess_webform_composite_contact(array &$variables) {
  _template_preprocess_webform_composite($variables);
}

/**
 * Prepares variables for creditcard composite element templates.
 *
 * Default template: webform-element-creditcard.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties of the element.
 */
function template_preprocess_webform_composite_creditcard(array &$variables) {
  _template_preprocess_webform_composite($variables);
}

/**
 * Prepares variables for link composite element templates.
 *
 * Default template: webform-element-link.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties of the element.
 */
function template_preprocess_webform_composite_link(array &$variables) {
  $variables['content'] = $variables['element'];
}

/**
 * Prepares variables for location composite element templates.
 *
 * Default template: webform-element-location.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties of the element.
 */
function template_preprocess_webform_composite_location(array &$variables) {
  $variables['content'] = $variables['element'];
}

/**
 * Prepares variables for name composite element templates.
 *
 * Default template: webform-element-name.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties of the element.
 */
function template_preprocess_webform_composite_name(array &$variables) {
  _template_preprocess_webform_composite($variables);
}

/**
 * Prepares variables for telephone composite element templates.
 *
 * Default template: webform-element-telephone.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties of the element.
 */
function template_preprocess_webform_composite_telephone(array &$variables) {
  _template_preprocess_webform_composite($variables);
}

/******************************************************************************/
// Element templates
/******************************************************************************/

/**
 * Prepares variables for element help.
 *
 * Default template: webform-element-help.html.twig.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - element: The webform element.
 *   - help: The help content.
 */
function template_preprocess_webform_element_help(array &$variables) {
  $variables['help_icon'] = [
    '#type' => 'link',
    '#title' => '?',
    '#url' => Url::fromRoute('<none>', [], ['fragment' => 'help']),
    '#attributes' => [
      'title' => $variables['help'],
      // Add 'data-webform-help' attribute which support HTML markup.
      'data-webform-help' => WebformHtmlEditor::stripTags($variables['help']),
      'class' => ['webform-element-help'],
    ],
  ];
}

/**
 * Prepares variables for element more.
 *
 * Default template: webform-element-more.html.twig.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - element: The webform element.
 */
function template_preprocess_webform_element_more(array &$variables) {
  if (empty($variables['more_title'])) {
    $variables['more_title'] = \Drupal::config('webform.settings')->get('element.default_more_title');
  }

  if (!is_array($variables['more'])) {
    $variables['more'] = [
      '#markup' => $variables['more'],
      '#allowed_tags' => WebformHtmlEditor::getAllowedTags(),
    ];
  }

  $variables['attributes'] = new Attribute($variables['attributes']);
}

/**
 * Prepares variables for a managed file element.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - element: The webform element.
 *   - value: The content for the element.
 *   - options Associative array of options for element.
 *   - file: The element's File object.
 */
function template_preprocess_webform_element_managed_file(array &$variables) {
  if (!empty($variables['file'])) {
    /** @var \Drupal\file\FileInterface $file */
    $file = $variables['file'];
    $variables['uri'] = file_create_url($file->getFileUri());
    $variables['extension'] = strtolower(pathinfo($variables['uri'], PATHINFO_EXTENSION));
    $variables['type'] = \Drupal::service('file.mime_type.guesser')->guess($variables['uri']);
    $variables['file_link'] = [
      '#theme' => 'file_link',
      '#file' => $file,
    ];
  }
}

/**
 * Prepares variables for an audio file element.
 *
 * Default template: webform-element-audio-file.html.twig.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - element: The webform element.
 *   - value: The content for the element.
 *   - options Associative array of options for element.
 *   - file: The element's File object.
 */
function template_preprocess_webform_element_audio_file(array &$variables) {
  template_preprocess_webform_element_managed_file($variables);
}

/**
 * Prepares variables for a document file element.
 *
 * Default template: webform-element-document-file.html.twig.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - element: The webform element.
 *   - value: The content for the element.
 *   - options Associative array of options for element.
 *   - file: The element's File object.
 */
function template_preprocess_webform_element_document_file(array &$variables) {
  template_preprocess_webform_element_managed_file($variables);
}

/**
 * Prepares variables for an image file element.
 *
 * Default template: webform-element-image-file.html.twig.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - element: The webform element.
 *   - value: The content for the element.
 *   - options Associative array of options for element.
 *   - file: The element's File object.
 *   - style_name: An image style name.
 *   - format: Image formatting (link or modal)
 */
function template_preprocess_webform_element_image_file(array &$variables) {
  if (!empty($variables['file'])) {
    /** @var \Drupal\file\FileInterface $file */
    $file = $variables['file'];

    $style_name = $variables['style_name'];
    $format = $variables['format'];

    $uri = $file->getFileUri();
    $url = Url::fromUri(file_create_url($uri));

    // Build image.
    if (\Drupal::moduleHandler()->moduleExists('image') && $style_name && ImageStyle::load($style_name)) {
      $variables['image'] = [
        '#theme' => 'image_style',
        '#style_name' => $variables['style_name'],
      ];
    }
    else {
      // Note: The 'image' template uses root-relative paths.
      // The 'image' is preprocessed to use absolute URLs.
      // @see webform_preprocess_image().
      $variables['image'] = [
        '#theme' => 'image',
      ];
    }
    $variables['image'] += [
      '#uri' => $uri,
      '#attributes' => ['class' => ['webform-image-file'], 'alt' => $file->getFilename()],
    ];

    // Wrap 'image' in a link/modal.
    if ($format && $format != 'image') {
      $variables['image'] = [
        '#type' => 'link',
        '#title' => $variables['image'],
        '#url' => $url,
      ];
      switch ($format) {
        case 'modal':
          $variables['image'] += [
            '#attributes' => ['class' => ['js-webform-image-file-modal', 'webform-image-file-modal']],
            '#attached' => ['library' => ['webform/webform.element.image_file.modal']],
          ];
          break;

        case 'link':
          $variables['image'] += ['#attributes' => ['class' => ['webform-image-file-link']]];
          break;
      }
    }

  }
}

/**
 * Prepares variables for a video file element.
 *
 * Default template: webform-element-video-file.html.twig.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - element: The webform element.
 *   - value: The content for the element.
 *   - options Associative array of options for element.
 *   - file: The element's File object.
 */
function template_preprocess_webform_element_video_file(array &$variables) {
  template_preprocess_webform_element_managed_file($variables);
}
