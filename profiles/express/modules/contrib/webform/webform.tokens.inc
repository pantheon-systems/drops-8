<?php

/**
 * @file
 * Builds placeholder replacement tokens for webforms and submissions.
 */

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\Markup;
use Drupal\user\Entity\User;
use Drupal\webform\Utility\WebformDateHelper;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Drupal\webform\Plugin\WebformElementEntityReferenceInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Implements hook_token_info().
 */
function webform_token_info() {

  $types = [];
  $tokens = [];

  /****************************************************************************/
  // Authenticated user.
  /****************************************************************************/

  $types['webform-authenticated-user'] = [
    'name' => t('Webform authenticated user'),
    'description' => t('Tokens related to the currently authenticated user.'),
    'type' => 'user',
  ];

  /****************************************************************************/
  // Webform submission.
  /****************************************************************************/

  $types['webform_submission'] = [
    'name' => t('Webform submissions'),
    'description' => t('Tokens related to webform submission.'),
    'needs-data' => 'webform_submission',
  ];

  $webform_submission = [];
  $webform_submission['serial'] = [
    'name' => t('Submission serial number'),
    'description' => t('The serial number of the webform submission.'),
  ];
  $webform_submission['sid'] = [
    'name' => t('Submission ID'),
    'description' => t('The ID of the webform submission.'),
  ];
  $webform_submission['uuid'] = [
    'name' => t('UUID'),
    'description' => t('The UUID of the webform submission.'),
  ];
  $webform_submission['token'] = [
    'name' => t('Token'),
    'description' => t('A secure token used to look up a submission.'),
  ];
  $webform_submission['ip-address'] = [
    'name' => t('IP address'),
    'description' => t('The IP address that was used when submitting the webform submission.'),
  ];
  $webform_submission['source-url'] = [
    'name' => t('Source URL'),
    'description' => t('The URL the user submitted the webform submission.'),
  ];
  $webform_submission['update-url'] = [
    'name' => t('Update URL'),
    'description' => t('The URL that can used to update the webform submission. The webform must be configured to allow users to update a submission using a secure token.'),
  ];
  $webform_submission['langcode'] = [
    'name' => t('Langcode'),
    'description' => t('The language code of the webform submission.'),
  ];
  $webform_submission['language'] = [
    'name' => t('Language'),
    'description' => t('The language name of the webform submission.'),
  ];
  $webform_submission['current-page'] = [
    'name' => t('Current page'),
    'description' => t('The current (wizard) page of the webform submission.'),
  ];
  $webform_submission['in-draft'] = [
    'name' => t('In draft'),
    'description' => t('Is the webform submission in draft.'),
  ];
  $webform_submission['limit:webform'] = [
    'name' => t('Total submissions limit'),
    'description' => t('The total number of submissions allowed for the webform.'),
  ];
  $webform_submission['total:webform'] = [
    'name' => t('Total submissions'),
    'description' => t('The current number of submissions for the webform.'),
  ];
  $webform_submission['limit:user'] = [
    'name' => t('Per user submission limit'),
    'description' => t('The total number of submissions allowed per user for the webform.'),
  ];
  $webform_submission['total:user'] = [
    'name' => t('Per user total submissions'),
    'description' => t('The current number of submissions for the user for the webform.'),
  ];
  $webform_submission['limit:webform:source_entity'] = [
    'name' => t('Total submissions limit per source entity'),
    'description' => t('The total number of submissions allowed for the webform source entity.'),
  ];
  $webform_submission['total:webform:source_entity'] = [
    'name' => t('Total submissions for source entity'),
    'description' => t('The current number of submissions for the webform source entity.'),
  ];
  $webform_submission['limit:user:source_entity'] = [
    'name' => t('Per user submission limit per source entity'),
    'description' => t('The total number of submissions allowed per user for the webform source entity.'),
  ];
  $webform_submission['total:user:source_entity'] = [
    'name' => t('Per user total submissions for source entity'),
    'description' => t('The current number of submissions for the user for the webform source entity.'),
  ];

  // Dynamic tokens for webform submissions.
  $webform_submission['url'] = [
    'name' => t('URL'),
    'description' => t("The URL of the webform submission. Replace the '?' with the link template. Defaults to 'canonical' which displays the submission's data."),
    'dynamic' => TRUE,
  ];
  $webform_submission['values'] = [
    'name' => t('Submission values'),
    'description' => Markup::create((string) t('Webform tokens from submitted data.') .
      t("Replace the '?' with...") . '<br />' .
      '<ul>' .
      '<li>element_key</li>' .
      '<li>element_key:format:items</li>' .
      '<li>element_key:delta</li>' .
      '<li>element_key:sub_element_key</li>' .
      '<li>element_key:delta:sub_element_key</li>' .
      '<li>element_key:sub_element_key:format</li>' .
      '<li>element_key:delta:sub_element_key:format</li>' .
      '<li>element_key:delta:format</li>' .
      '<li>element_key:delta:format:html</li>' .
      '<li>element_key:entity:*</li>' .
      '<li>element_key:entity:*</li>' .
      '<li>element_key:delta:entity:*</li>' .
      '<li>element_key:delta:entity:field_name:*</li>' .
      '<li>element_key:sub_element_key:entity:*</li>' .
      '<li>element_key:sub_element_key:entity:fiel_name:*</li>' .
      '<li>element_key:delta:sub_element_key:entity:*</li>' .
      '</ul>' .
      t("All items after the 'element_key' are optional.") . '<br />' .
      t("The 'delta' is the numeric index for specific value") . '<br />' .
      t("The 'sub_element_key' is a composite element's sub element key.") . '<br />' .
      t("The 'format' can be 'value', 'raw', or custom format specifically associated with the element") . '<br />' .
      t("The 'items' can be 'comma', 'semicolon', 'and', 'ol', 'ul', or custom delimiter") . '<br />' .
      t("The 'entity:*' applies to the referenced entity") . '<br />' .
      t("Add 'html' at the end of the token to return HTML markup instead of plain text.") . '<br />' .
      t("For example, to display the Contact webform's 'Subject' element's value you would use the [webform_submission:values:subject] token.")
    ),
    'dynamic' => TRUE,
  ];
  // Chained tokens for webform submissions.
  $webform_submission['user'] = [
    'name' => t('Submitter'),
    'description' => t('The user that submitted the webform submission.'),
    'type' => 'user',
  ];
  $webform_submission['created'] = [
    'name' => t('Date created'),
    'description' => t('The date the webform submission was created.'),
    'type' => 'date',
  ];
  $webform_submission['completed'] = [
    'name' => t('Date completed'),
    'description' => t('The date the webform submission was completed.'),
    'type' => 'date',
  ];
  $webform_submission['changed'] = [
    'name' => t('Date changed'),
    'description' => t('The date the webform submission was most recently updated.'),
    'type' => 'date',
  ];
  $webform_submission['webform'] = [
    'name' => t('Webform'),
    'description' => t('The webform that the webform submission belongs to.'),
    'type' => 'webform',
  ];
  $webform_submission['source-entity'] = [
    'name' => t('Source entity'),
    'description' => t('The source entity that the webform submission was submitted from.'),
    'type' => 'entity',
  ];
  $webform_submission['submitted-to'] = [
    'name' => t('Submitted to'),
    'description' => t('The source entity or webform that the webform submission was submitted from.'),
    'type' => 'entity',
  ];

  $tokens['webform_submission'] = $webform_submission;

  /****************************************************************************/
  // Webform.
  /****************************************************************************/

  $types['webform'] = [
    'name' => t('Webforms'),
    'description' => t('Tokens related to webforms.'),
    'needs-data' => 'webform',
  ];

  $webform = [];
  $webform['id'] = [
    'name' => t('Webform ID'),
    'description' => t('The ID of the webform.'),
  ];
  $webform['title'] = [
    'name' => t('Title'),
    'description' => t('The title of the webform.'),
  ];
  $webform['description'] = [
    'name' => t('Description'),
    'description' => t('The administrative description of the webform.'),
  ];
  $webform['url'] = [
    'name' => t('URL'),
    'description' => t('The URL of the webform.'),
  ];
  $webform['author'] = [
    'name' => t('Author'),
    'type' => 'user',
  ];
  $webform['open'] = [
    'name' => t('Open date'),
    'description' => t('The date the webform is open to new submissions.'),
    'type' => 'date',
  ];
  $webform['close'] = [
    'name' => t('Close date'),
    'description' => t('The date the webform is closed to new submissions.'),
    'type' => 'date',
  ];
  $webform['handler'] = [
    'name' => t('Handler response'),
    'description' => Markup::create((string) t('Webform handler response tokens.') . '<br/>' .
      t("Replace the '?' with...") . '<br />' .
      '<ul>' .
      '<li>handler_id:state:key</li>' .
      '<li>handler_id:state:key1:key2</li>' .
      '</ul>' .
      t("For example, to display a remote post's confirmation number you would use the [webform:handler:remote_post:completed:confirmation_number] token.")
    ),
    'dynamic' => TRUE,
  ];

  $tokens['webform'] = $webform;

  /****************************************************************************/
  // Webform role.
  /****************************************************************************/

  $roles = \Drupal::config('webform.settings')->get('mail.roles');
  if ($roles) {
    $types['webform_role'] = [
      'name' => t('Webform roles'),
      'description' => t("Tokens related to user roles that can receive email. <em>This token is only available to a Webform email handler's 'To', 'CC', and 'BCC' email recipents.</em>"),
      'needs-data' => 'webform_role',
    ];

    $webform_role = [];
    $role_names = array_map('\Drupal\Component\Utility\Html::escape', user_role_names(TRUE));
    if (!in_array('authenticated', $roles)) {
      $role_names = array_intersect_key($role_names, array_combine($roles, $roles));
    }
    foreach ($role_names as $role_name => $role_label) {
      $webform_role[$role_name] = [
        'name' => $role_label,
        'description' => t('The email addresses of all users assigned to the %title role.', ['%title' => $role_label]),
      ];
    }

    $tokens['webform_role'] = $webform_role;
  }

  /****************************************************************************/

  return ['types' => $types, 'tokens' => $tokens];
}

/**
 * Implements hook_tokens().
 */
function webform_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $token_service = \Drupal::token();

  // Set URL options to generate absolute translated URLs.
  $url_options = ['absolute' => TRUE];
  if (isset($options['langcode'])) {
    $url_options['language'] = \Drupal::languageManager()->getLanguage($options['langcode']);
    $langcode = $options['langcode'];
  }
  else {
    $langcode = NULL;
  }

  $replacements = [];
  if ($type == 'webform-authenticated-user') {
    if (\Drupal::currentUser()->isAuthenticated()) {
      $account = User::load(\Drupal::currentUser()->id());
      $bubbleable_metadata->addCacheableDependency($account);
      $replacements += $token_service->generate('user', $tokens, ['user' => $account], $options, $bubbleable_metadata);
    }
    else {
      // For anonymous user we are going to always return empty string.
      foreach ($tokens as $name => $original) {
        $replacements[$original] = '';
      }
    }
  }
  elseif ($type == 'webform_role' && !empty($data['webform_role'])) {
    $roles = $data['webform_role'];
    $any_role = in_array('authenticated', $roles) ? TRUE : FALSE;
    foreach ($tokens as $role_name => $original) {
      if ($any_role || in_array($role_name, $roles)) {
        if ($role_name == 'authenticated') {
          // Get all active authenticated users.
          $query = \Drupal::database()->select('users_field_data', 'u');
          $query->fields('u', ['mail']);
          $query->condition('u.status', 1);
          $query->condition('u.mail', '', '<>');
          $query->orderBy('mail');
          $replacements[$original] = implode(',', $query->execute()->fetchCol());

        }
        else {
          // Get all authenticated users assigned to a specified role.
          $query = \Drupal::database()->select('user__roles', 'ur');
          $query->distinct();
          $query->join('users_field_data', 'u', 'u.uid = ur.entity_id');
          $query->fields('u', ['mail']);
          $query->condition('ur.roles_target_id', $role_name);
          $query->condition('u.status', 1);
          $query->condition('u.mail', '', '<>');
          $query->orderBy('mail');
          $replacements[$original] = implode(',', $query->execute()->fetchCol());
        }
      }
    }

  }
  elseif ($type == 'webform_submission' && !empty($data['webform_submission'])) {
    /** @var \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager */
    $element_manager = \Drupal::service('plugin.manager.webform.element');

    /** @var \Drupal\webform\WebformSubmissionStorageInterface $submission_storage */
    $submission_storage = \Drupal::entityTypeManager()->getStorage('webform_submission');

    // Adding webform submission, webform, source entity to bubbleable meta.
    // This reduces code duplication and easier to track.
    /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
    $webform_submission = $data['webform_submission'];
    $bubbleable_metadata->addCacheableDependency($webform_submission);

    $webform = $webform_submission->getWebform();
    $bubbleable_metadata->addCacheableDependency($webform);

    $source_entity = $webform_submission->getSourceEntity();
    if ($source_entity) {
      $bubbleable_metadata->addCacheableDependency($source_entity);
    }

    /** @var \Drupal\Core\Session\AccountInterface $account */
    $account = $webform_submission->getOwner() ?: User::load(0);
    $bubbleable_metadata->addCacheableDependency($account);

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'langcode':
        case 'serial':
        case 'sid':
        case 'uuid':
          $replacements[$original] = $webform_submission->{$name}->value;
          break;

        case 'ip-address':
          $replacements[$original] = $webform_submission->remote_addr->value;
          break;

        case 'in-draft':
          $replacements[$original] = $webform_submission->in_draft->value ? t('Yes') : t('No');
          break;

        case 'current-page':
          $current_page = $webform_submission->current_page->value;
          if (empty($current_page) && $pages = $webform->getPages()) {
            $page_keys = array_keys($pages);
            $current_page = reset($page_keys);
          }
          $replacements[$original] = $current_page;
          break;

        case 'language':
          $replacements[$original] = \Drupal::languageManager()->getLanguage($webform_submission->langcode->value)->getName();
          break;

        case 'source-url':
          $replacements[$original] = $webform_submission->getSourceUrl()->toString();
          break;

        case 'update-url':
          $replacements[$original] = $webform_submission->getTokenUrl()->toString();
          break;

        case 'token':
          $replacements[$original] = $webform_submission->getToken();
          break;

        /* Default values for the dynamic tokens handled below. */

        case 'url':
          if ($webform_submission->id()) {
            $replacements[$original] = $webform_submission->toUrl('canonical', $url_options)->toString();
          }
          break;

        case 'values':
          $replacements[$original] = _webform_token_get_submission_values($options, $webform_submission);
          break;

        /* Default values for the chained tokens handled below */

        case 'user':
          $replacements[$original] = $account->label();
          break;

        case 'created':
        case 'completed':
        case 'changed':
          $bubbleable_metadata->addCacheableDependency(DateFormat::load('medium'));
          $replacements[$original] = WebformDateHelper::format($webform_submission->{$name}->value, 'medium', '', NULL, $langcode);
          break;

        case 'webform':
          $replacements[$original] = $webform->label();
          break;

        case 'source-entity':
          if ($source_entity) {
            $replacements[$original] = $source_entity->label();
          }
          else {
            $replacements[$original] = '';
          }
          break;

        case 'submitted-to':
          $submitted_to = $source_entity ?: $webform;
          $replacements[$original] = $submitted_to->label();
          break;

        case 'limit:webform':
          $replacements[$original] = $webform->getSetting('limit_total') ?: t('None');
          break;

        case 'total:webform':
          $replacements[$original] = $submission_storage->getTotal($webform);
          break;

        case 'limit:user':
          $replacements[$original] = $webform->getSetting('limit_user') ?: t('None');
          break;

        case 'total:user':
          $replacements[$original] = $submission_storage->getTotal($webform, NULL, $account);
          break;

        case 'limit:webform:source_entity':
          $replacements[$original] = $webform->getSetting('entity_limit_total') ?: t('None');
          break;

        case 'total:webform:source_entity':
          $replacements[$original] = $source_entity ? $submission_storage->getTotal($webform, $source_entity) : '';
          break;

        case 'limit:user:source_entity':
          $replacements[$original] = $webform->getSetting('entity_limit_user') ?: t('None');
          break;

        case 'total:user:source_entity':
          $replacements[$original] = $source_entity ? $submission_storage->getTotal($webform, $source_entity, $account) : '';
          break;

      }
    }

    /* Dynamic tokens. */

    if (($url_tokens = $token_service->findWithPrefix($tokens, 'url')) && $webform_submission->id()) {
      foreach ($url_tokens as $key => $original) {
        if ($webform_submission->hasLinkTemplate($key)) {
          $replacements[$original] = $webform_submission->toUrl($key, $url_options)->toString();
        }
      }
    }
    if ($value_tokens = $token_service->findWithPrefix($tokens, 'values')) {
      foreach ($value_tokens as $value_token => $original) {
        $value = _webform_token_get_submission_value($value_token, $options, $webform_submission, $element_manager, $bubbleable_metadata);
        if ($value !== NULL) {
          $replacements[$original] = $value;
        }
      }
    }

    /* Chained token relationships. */

    if (($user_tokens = $token_service->findWithPrefix($tokens, 'user')) && ($user = $webform_submission->getOwner())) {
      $replacements += $token_service->generate('user', $user_tokens, ['user' => $user], $options, $bubbleable_metadata);
    }
    if (($created_tokens = $token_service->findWithPrefix($tokens, 'created')) && ($created_time = $webform_submission->getCreatedTime())) {
      $replacements += $token_service->generate('date', $created_tokens, ['date' => $created_time], $options, $bubbleable_metadata);
    }
    if (($changed_tokens = $token_service->findWithPrefix($tokens, 'changed')) && ($changed_time = $webform_submission->getChangedTime())) {
      $replacements += $token_service->generate('date', $changed_tokens, ['date' => $changed_time], $options, $bubbleable_metadata);
    }
    if (($completed_tokens = $token_service->findWithPrefix($tokens, 'completed')) && ($completed_time = $webform_submission->getCompletedTime())) {
      $replacements += $token_service->generate('date', $completed_tokens, ['date' => $completed_time], $options, $bubbleable_metadata);
    }
    if (($webform_tokens = $token_service->findWithPrefix($tokens, 'webform')) && ($webform = $webform_submission->getWebform())) {
      $replacements += $token_service->generate('webform', $webform_tokens, ['webform' => $webform], $options, $bubbleable_metadata);
    }
    if (($source_entity_tokens = $token_service->findWithPrefix($tokens, 'source-entity')) && ($source_entity = $webform_submission->getSourceEntity())) {
      $replacements += $token_service->generate($source_entity->getEntityTypeId(), $source_entity_tokens, [$source_entity->getEntityTypeId() => $source_entity], $options, $bubbleable_metadata);
    }
    if (($submitted_to_tokens = $token_service->findWithPrefix($tokens, 'submitted-to')) && ($submitted_to = $webform_submission->getSourceEntity() ?: $webform_submission->getWebform())) {
      $replacements += $token_service->generate($submitted_to->getEntityTypeId(), $submitted_to_tokens, [$submitted_to->getEntityTypeId() => $submitted_to], $options, $bubbleable_metadata);
    }

  }
  elseif ($type == 'webform' && !empty($data['webform'])) {

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $data['webform'];
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'id':
          $replacements[$original] = $webform->id();
          break;

        case 'title':
          $replacements[$original] = $webform->label();
          break;

        case 'description':
          $replacements[$original] = $webform->getDescription();
          break;

        case 'open':
        case 'close':
          $datetime = $webform->get($name);
          $replacements[$original] = $datetime ? WebformDateHelper::format(strtotime($datetime), 'medium', '', NULL, $langcode) : '';
          break;

        /* Default values for the dynamic tokens handled below. */

        case 'url':
          $replacements[$original] = $webform->toUrl('canonical', $url_options)->toString();
          break;

        /* Default values for the chained tokens handled below. */

        case 'author':
          $account = $webform->getOwner() ?: User::load(0);
          $bubbleable_metadata->addCacheableDependency($account);
          $replacements[$original] = $account->label();
          break;
      }
    }

    /* Dynamic tokens. */

    if (($handler_tokens = $token_service->findWithPrefix($tokens, 'handler'))) {
      foreach ($handler_tokens as $key => $original) {
        $webform_handler = isset($data['webform_handler']) ? $data['webform_handler'] : [];
        $parents = explode(':', $key);
        $key_exists = NULL;
        $value = NestedArray::getValue($webform_handler, $parents, $key_exists);
        $replacements[$original] = ($key_exists) ? $value : $original;
      }
    }

    if (($url_tokens = $token_service->findWithPrefix($tokens, 'url'))) {
      foreach ($url_tokens as $key => $original) {
        if ($webform->hasLinkTemplate($key)) {
          $replacements[$original] = $webform->toUrl($key, $url_options)->toString();
        }
      }
    }

    /* Chained token relationships. */

    if ($author_tokens = $token_service->findWithPrefix($tokens, 'author')) {
      $replacements += $token_service->generate('user', $author_tokens, ['user' => $webform->getOwner()], $options, $bubbleable_metadata);
    }
    if (($open_tokens = $token_service->findWithPrefix($tokens, 'open')) && ($open_time = $webform->get('open'))) {
      $replacements += $token_service->generate('date', $open_tokens, ['date' => strtotime($open_time)], $options, $bubbleable_metadata);
    }
    if (($close_tokens = $token_service->findWithPrefix($tokens, 'close')) && ($close_time = $webform->get('close'))) {
      $replacements += $token_service->generate('date', $close_tokens, ['date' => strtotime($close_time)], $options, $bubbleable_metadata);
    }
  }

  // If 'webform_clear' options is set, clear all [webform*] tokens.
  if (strpos($type, 'webform') === 0 && !empty($options['webform_clear'])) {
    foreach ($tokens as $token) {
      if (!isset($replacements[$token])) {
        $replacements[$token] = '';
      }
    }
  }

  return $replacements;
}

/**
 * Get webform submission token value.
 *
 * @param string $value_token
 *   A [webform_submission:value:?] token.
 * @param array $options
 *   An array of token options.
 * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
 *   A webform submission.
 * @param \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager
 *   The webform element manager.
 * @param \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata
 *   (optional) Object to collect route processors' bubbleable metadata.
 *
 * @return \Drupal\Component\Render\MarkupInterface|string
 *   Webform submission token value.
 */
function _webform_token_get_submission_value($value_token, array $options, WebformSubmissionInterface $webform_submission, WebformElementManagerInterface $element_manager, BubbleableMetadata $bubbleable_metadata) {
  $submission_data = $webform_submission->getData();

  // Formats:
  // [html]
  // [values:{element_key}:{format}]
  // [values:{element_key}:{format}:{items}]
  // [values:{element_key}:{format}:html]
  // [values:{element_key}:{format}:{items}:html]
  // [values:{element_key}:{delta}:{format}]
  // [values:{element_key}:{delta}:{sub-element}]
  $keys = explode(':', $value_token);
  $element_key = array_shift($keys);

  // Build HTML values.
  if ($element_key == 'html' && empty($keys)) {
    $options['html'] = TRUE;
    return _webform_token_get_submission_values($options, $webform_submission);
  }

  $element = $webform_submission->getWebform()->getElement($element_key, TRUE);

  // Exit if form element does not exist.
  if (!$element) {
    return NULL;
  }

  $element_plugin = $element_manager->getElementInstance($element);

  // Exit if no submission data and form element is not a container.
  if (!isset($submission_data[$element_key]) && !$element_plugin->isContainer($element)) {
    return NULL;
  }

  // Look for :html which indicates the tokens should be formatted as HTML
  // instead of plain text.
  if ($keys && in_array('html', $keys)) {
    $keys = array_diff($keys, ['html']);
    $options['html'] = TRUE;
  }

  // If multiple value element look for delta.
  if ($keys && $element_plugin->hasMultipleValues($element) && is_numeric($keys[0])) {
    $delta = array_shift($keys);
    $options['delta'] = $delta;
  }
  else {
    $delta = NULL;
  }

  // If composite element look for sub-element key.
  if ($keys && $element_plugin->isComposite() && method_exists($element_plugin, 'getInitializedCompositeElement') && $element_plugin->getInitializedCompositeElement($element, $keys[0])) {
    $composite_key = array_shift($keys);
    $options['composite_key'] = $composite_key;
  }
  else {
    $composite_key = NULL;
  }

  // Set entity reference chaining.
  if ($keys && $keys[0] == 'entity' && $element_plugin instanceof WebformElementEntityReferenceInterface) {
    // Remove entity from keys.
    array_shift($keys);

    // Get entity value, type, instance, and token.
    if ($entity = $element_plugin->getTargetEntity($element, $webform_submission, $options)) {
      $entity_type = $entity->getEntityTypeId();
      // Map entity type id to entity token name.
      $entity_token_names = [
        // Taxonomy tokens are not prefixed with 'taxonomy_'.
        // @see taxonomy_token_info()
        'taxonomy_term' => 'term',
        'taxonomy_vocabulary' => 'vocabulary',
      ];
      $entity_token_name = (isset($entity_token_names[$entity_type])) ? $entity_token_names[$entity_type] : $entity_type;
      $entity_token = implode(':', $keys);
      return \Drupal::token()->replace(
        "[$entity_token_name:$entity_token]",
        [$entity_token_name => $entity],
        $options,
        $bubbleable_metadata
      );
    }
    else {
      return '';
    }
  }

  // Set format and items format.
  if ($keys) {
    if ($composite_key) {
      // Must set '#webform_composite_elements' format.
      // @see \Drupal\webform\Plugin\WebformElement\WebformCompositeBase::initialize
      // @see \Drupal\webform\Plugin\WebformElement\WebformCompositeBase::getInitializedCompositeElement
      $element['#webform_composite_elements'][$composite_key]['#format'] = array_shift($keys);
    }
    else {
      $element['#format'] = array_shift($keys);
    }
  }
  if ($keys) {
    $element['#format_items'] = array_shift($keys);
  }

  $format_method = (empty($options['html'])) ? 'formatText' : 'formatHtml';
  $token_value = $element_manager->invokeMethod($format_method, $element, $webform_submission, $options);
  if (is_array($token_value)) {
    // Note, tokens can't include CSS and JS libraries since they will
    // can be included in an email.
    $markup = \Drupal::service('renderer')->renderPlain($token_value);
    return $markup;
  }
  elseif (isset($element['#format']) && $element['#format'] === 'raw') {
    // Raw needs return Markup.
    if ($token_value instanceof MarkupInterface) {
      return $token_value;
    }
    else {
      return Markup::create((string) $token_value);
    }
  }
  else {
    return (string) $token_value;
  }
}

/**
 * Get webform submission values.
 *
 * @param array $options
 *   An array of token options.
 * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
 *   A webform submission.
 *
 * @return \Drupal\Component\Render\MarkupInterface|string
 *   Webform submission values.
 */
function _webform_token_get_submission_values(array $options, WebformSubmissionInterface $webform_submission) {
  $submission_format = (!empty($options['html'])) ? 'html' : 'text';
  /** @var \Drupal\webform\WebformSubmissionViewBuilderInterface $view_builder */
  $view_builder = \Drupal::entityTypeManager()->getViewBuilder('webform_submission');
  $form_elements = $webform_submission->getWebform()->getElementsInitialized();
  $token_value = $view_builder->buildElements($form_elements, $webform_submission, $options, $submission_format);
  // Note, tokens can't include CSS and JS libraries since they can be
  // included in an email.
  return \Drupal::service('renderer')->renderPlain($token_value);
}
