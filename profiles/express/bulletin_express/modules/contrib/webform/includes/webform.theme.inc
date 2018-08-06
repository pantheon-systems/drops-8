<?php

/**
 * @file
 * Preprocessors and helper functions to make theming easier.
 */

use Drupal\Core\Link;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Template\Attribute;
use Drupal\Component\Utility\Xss;
use Drupal\webform\Element\WebformCodeMirror;
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
  /** @var \Drupal\webform\WebformHelpManagerInterface $help_manager */
  $help_manager = \Drupal::service('webform.help_manager');

  // If help info load the video else this is the video info.
  $video_info = (isset($help_info['video_id'])) ? $help_manager->getVideo($help_info['video_id']) : $help_info;
  if (!empty($video_info['youtube_id'])) {
    $classes = ['button', 'button-action', 'button--small', 'button-webform-play'];

    $video_display = \Drupal::config('webform.settings')->get('ui.video_display');
    switch ($video_display) {
      case 'dialog':
        $help['link'] = [
          '#type' => 'link',
          '#title' => t('Watch video'),
          '#url' => Url::fromRoute('webform.help', ['id' => str_replace('_', '-', $video_info['id'])]),
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
 * Prepares variables for webform confirmation templates.
 *
 * Default template: webform-confirmation.html.twig.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - webform_submission: A webform submission.
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
  $message_manager->setWebform($webform);
  $message_manager->setSourceEntity($source_entity);
  $message_manager->setWebformSubmission($webform_submission);

  $settings = $webform->getSettings();

  // Set progress.
  if ($webform->getPages() && $settings['wizard_complete'] && ($settings['wizard_progress_bar'] || $settings['wizard_progress_pages'] || $settings['wizard_progress_percentage'])) {
    $variables['progress'] = [
      '#theme' => 'webform_progress',
      '#webform' => $webform,
      '#current_page' => 'complete',
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

  // Apply all passed query string parameters to the 'Back to form' link.
  $query = \Drupal::request()->query->all();
  unset($query['webform_id']);
  $options = ($query) ? ['query' => $query] : [];

  // Set back_url.
  if ($source_entity && $source_entity->hasLinkTemplate('canonical')) {
    $variables['back_url'] = $source_entity->toUrl('canonical', $options)->toString();
  }
  elseif ($webform_submission) {
    $variables['back_url'] = $webform_submission->getSourceUrl()->toString();
  }
  else {
    $variables['back_url'] = $webform->toUrl('canonical', $options)->toString();
  }
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
  }
  else {
    $account = NULL;
  }

  if ($previous_submission = $webform_submission_storage->getPreviousSubmission($webform_submission, $source_entity, $account)) {
    $variables['prev_url'] = Url::fromRoute($route_name, ['webform_submission' => $previous_submission->id()] + $route_parameters)->toString();
  }
  if ($next_submission = $webform_submission_storage->getNextSubmission($webform_submission, $source_entity, $account)) {
    $variables['next_url'] = Url::fromRoute($route_name, ['webform_submission' => $next_submission->id()] + $route_parameters)->toString();
  }

  $variables['#attached']['library'][] = 'webform/webform.navigation';

  // Never cache navigation because previous and next submission will change
  // as submissions are added and deleted.
  $variables['#cache'] = ['max-age' => 0];
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
  $data = $webform_submission->getData();
  $elements = $webform->getElementsInitialized();
  $variables['data'] = $view_builder->buildElements($elements, $data);
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
  $data = $webform_submission->getData();
  $elements = $webform->getElementsInitializedFlattenedAndHasValue();

  $variables['table'] = $view_builder->buildTable($elements, $data);
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

  $variables['sid'] = $webform_submission->id();
  $variables['uuid'] = $webform_submission->uuid();
  $variables['is_draft'] = $webform_submission->isDraft() ? t('Yes') : t('No');
  $variables['current_page'] = $webform_submission->getCurrentPage();
  $variables['remote_addr'] = $webform_submission->getRemoteAddr();
  $variables['submitted_by'] = $webform_submission->getOwner()->label();
  $variables['webform'] = $webform_submission->getWebform()->label();
  $variables['created'] = WebformDateHelper::format($webform_submission->getCreatedTime());
  $variables['completed'] = WebformDateHelper::format($webform_submission->getCompletedTime());
  $variables['changed'] = WebformDateHelper::format($webform_submission->getChangedTime());

  // @see \Drupal\Core\Field\Plugin\Field\FieldFormatter\LanguageFormatter::viewValue()
  $languages = \Drupal::languageManager()->getNativeLanguages();
  $langcode = $webform_submission->get('langcode')->value;
  $variables['language'] = isset($languages[$langcode]) ? $languages[$langcode]->getName() : $langcode;

  if ($source_url = $webform_submission->getSourceUrl()) {
    $variables['uri'] = $source_url->toString();
  }

  if ($source_entity = $webform_submission->getSourceEntity()) {
    $variables['submitted_to'] = $source_entity->label();
  }

  /** @var \Drupal\webform\WebformSubmissionViewBuilderInterface $view_builder */
  $view_builder = \Drupal::entityTypeManager()->getViewBuilder('webform_submission');

  $webform = $webform_submission->getWebform();
  $data = $webform_submission->getData();
  $elements = $webform->getElementsInitialized();
  $variables['data'] = $view_builder->buildElements($elements, $data, [], 'text');
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
  $variables['yaml'] = [
    '#markup' => $yaml,
    '#allowed_tags' => Xss::getAdminTagList(),
  ];;
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
    $variables['code'] = [
      '#markup' => $variables['code'],
      '#allowed_tags' => Xss::getAdminTagList(),
    ];
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
  $variables['title'] = (WebformElementHelper::isTitleDisplayed($element)) ? $element['#title'] : NULL;
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
  template_preprocess_webform_element_base_html($variables);
}

/**
 * Prepares variables for webform container base HTML template.
 *
 * Default template: webform-container-base-html.html.twig.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - element: The webform element.
 *   - value: The content for the element.
 *   - options Associative array of options for element.
 *     - multiline: Flag to determine if value spans multiple lines.
 *     - email: Flag to determine if element is for an email.
 */
function template_preprocess_webform_container_base_html(array &$variables) {
  $element = $variables['element'];
  $variables['title'] = (isset($element['#title'])) ? $element['#title'] : NULL;
  $variables['id'] = $element['#webform_id'];
}

/**
 * Prepares variables for webform container base text template.
 *
 * Default template: webform-container-base-text.html.twig.
 *
 * @param array $variables
 *   An associative array containing the following key:
 *   - element: The webform element.
 *   - value: The content for the element.
 *   - options Associative array of options for element.
 *     - multiline: Flag to determine if value spans multiple lines.
 *     - email: Flag to determine if element is for an email.
 */
function template_preprocess_webform_container_base_text(array &$variables) {
  $element = $variables['element'];
  $variables['title'] = (isset($element['#title'])) ? $element['#title'] : NULL;
}

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
  /** @var \Drupal\webform\WebformInterface $webform */
  $webform = $variables['webform'];
  $current_page = $variables['current_page'];

  $pages = $webform->getPages();

  $page_keys = array_keys($pages);
  $page_indexes = array_flip($page_keys);
  $current_index = $page_indexes[$current_page];

  $total = count($page_keys);

  if ($webform->getSetting('wizard_progress_bar')) {
    $variables['bar'] = [
      '#theme' => 'webform_progress_bar',
      '#webform' => $variables['webform'],
      '#current_page' => $variables['current_page'],
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
  /** @var \Drupal\webform\WebformInterface $webform */
  $webform = $variables['webform'];
  $current_page = $variables['current_page'];

  $pages = $webform->getPages();

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
// Element templates
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

/******************************************************************************/
// Composite templates
/******************************************************************************/

/**
 * Prepares variables for address composite element templates.
 *
 * Default template: webform-element-address.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties of the element.
 *     Properties used: #title, #value, #options, #description, #required,
 *     #attributes.
 */
function template_preprocess_webform_composite_address(array &$variables) {
  $element = $variables['element'];
  foreach (Element::children($element) as $key) {
    if ($element[$key]['#access']) {
      $variables['content'][$key] = $element[$key];
    }
  }
  $variables['flexbox'] = (isset($element['#flexbox'])) ? $element['#flexbox'] : FALSE;
}

/**
 * Prepares variables for contact composite element templates.
 *
 * Default template: webform-element-contact.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties of the element.
 *     Properties used: #title, #value, #options, #description, #required,
 *     #attributes.
 */
function template_preprocess_webform_composite_contact(array &$variables) {
  $element = $variables['element'];
  foreach (Element::children($element) as $key) {
    if ($element[$key]['#access']) {
      $variables['content'][$key] = $element[$key];
    }
  }
  $variables['flexbox'] = (isset($element['#flexbox'])) ? $element['#flexbox'] : FALSE;
}

/**
 * Prepares variables for creditcard composite element templates.
 *
 * Default template: webform-element-creditcard.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties of the element.
 *     Properties used: #title, #value, #options, #description, #required,
 *     #attributes.
 */
function template_preprocess_webform_composite_creditcard(array &$variables) {
  $element = $variables['element'];
  foreach (Element::children($element) as $key) {
    if ($element[$key]['#access']) {
      $variables['content'][$key] = $element[$key];
    }
  }
  $variables['flexbox'] = (isset($element['#flexbox'])) ? $element['#flexbox'] : FALSE;
}

/**
 * Prepares variables for location composite element templates.
 *
 * Default template: webform-element-location.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties of the element.
 *     Properties used: #title, #value, #options, #description, #required,
 *     #attributes.
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
 *     Properties used: #title, #value, #options, #description, #required,
 *     #attributes.
 */
function template_preprocess_webform_composite_name(array &$variables) {
  $element = $variables['element'];
  foreach (Element::children($element) as $key) {
    if ($element[$key]['#access']) {
      $variables['content'][$key] = $element[$key];
    }
  }
  $variables['flexbox'] = (isset($element['#flexbox'])) ? $element['#flexbox'] : FALSE;
}

/**
 * Prepares variables for telephone composite element templates.
 *
 * Default template: webform-element-telephone.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: An associative array containing the properties of the element.
 *     Properties used: #title, #value, #options, #description, #required,
 *     #attributes.
 */
function template_preprocess_webform_composite_telephone(array &$variables) {
  $element = $variables['element'];
  foreach (Element::children($element) as $key) {
    if ($element[$key]['#access']) {
      $variables['content'][$key] = $element[$key];
    }
  }
}

/******************************************************************************/
// Element templates
/******************************************************************************/

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
 */
function template_preprocess_webform_element_image_file(array &$variables) {
  template_preprocess_webform_element_managed_file($variables);
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
