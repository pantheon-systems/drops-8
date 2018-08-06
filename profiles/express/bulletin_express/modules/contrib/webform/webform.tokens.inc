<?php

/**
 * @file
 * Builds placeholder replacement tokens for webforms and submissions.
 */

use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\user\Entity\User;
use Drupal\webform\Utility\WebformDateHelper;

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
    'description' => t('The serial number of the webform submission .'),
  ];
  $webform_submission['sid'] = [
    'name' => t('Submission ID'),
    'description' => t('The ID of the webform submission .'),
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
    'description' => t('The URL that can used to update the webform submission. The webform must be configurated to allow users to update a submission using a secure token.'),
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

  // Dynamic tokens for webform submissions.
  $webform_submission['url'] = [
    'name' => t('URL'),
    'description' => t("The URL of the webform submission. Replace the '?' with the link template. Defaults to 'canonical' which displays the submission's data."),
    'dynamic' => TRUE,
  ];
  $webform_submission['values'] = [
    'name' => t('Submission values'),
    'description' => t("Webform tokens from submitted data. Replace the '?' with the 'element_key', 'element_key:format' or 'element_key:format:items'.") . ' ' .
      t("The 'format' can be 'value', 'raw', or custom format specifically associated with the element") . ' ' .
      t("The 'items' can be 'comma', 'semicolon', 'and', 'ol', 'ul', or custom delimiter") . ' ' .
      t("For example, to display the Contact webform's 'Subject' element's value you would use the [webform_submission:values:subject] token."),
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
    'name' => t('title'),
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
    'name' => t('Open date '),
    'description' => t('The date the webform is open to new submissions.'),
    'type' => 'date',
  ];
  $webform['close'] = [
    'name' => t('Close date '),
    'description' => t('The date the webform is closed to new submissions.'),
    'type' => 'date',
  ];

  $tokens['webform'] = $webform;

  /****************************************************************************/
  // Webform.
  /****************************************************************************/

  $roles = \Drupal::config('webform.settings')->get('mail.roles');
  if ($roles) {
    $types['webform_role'] = [
      'name' => t('Webform roles'),
      'description' => t("Tokens related to user roles that can receive email. <em>This token is only available to a  Webform email handler's 'To', 'CC', and 'BCC' email recipents.</em>"),
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

    /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
    $webform_submission = $data['webform_submission'];

    /** @var \Drupal\webform\WebformSubmissionViewBuilderInterface $view_builder */
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder('webform_submission');

    /** @var \Drupal\webform\WebformElementManagerInterface $element_manager */
    $element_manager = \Drupal::service('plugin.manager.webform.element');

    $form_elements = $webform_submission->getWebform()->getElementsInitializedAndFlattened();
    $submission_data = $webform_submission->getData();

    $submission_options = isset($data['webform-submission-options']) ? $data['webform-submission-options'] : [];

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
          if (empty($current_page) && $pages = $webform_submission->getWebform()->getPages()) {
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
          $submission_format = (!empty($submission_options['html'])) ? 'html' : 'text';
          $build = $view_builder->buildElements(
            $webform_submission->getWebform()->getElementsInitialized(),
            $submission_data,
            $submission_options,
            $submission_format
          );
          // Note, tokens can't include CSS and JS libraries since they can be
          // included in an email.
          $replacements[$original] = \Drupal::service('renderer')->renderPlain($build);
          break;

        /* Default values for the chained tokens handled below */

        case 'user':
          /** @var \Drupal\Core\Session\AccountInterface $account */
          $account = $webform_submission->getOwner() ?: User::load(0);
          $bubbleable_metadata->addCacheableDependency($account);
          $replacements[$original] = $account->label();
          break;

        case 'created':
        case 'completed':
        case 'changed':
          $bubbleable_metadata->addCacheableDependency(DateFormat::load('medium'));
          $replacements[$original] = WebformDateHelper::format($webform_submission->{$name}->value, 'medium', '', NULL, $langcode);
          break;

        case 'webform':
          $webform = $webform_submission->getWebform();
          $bubbleable_metadata->addCacheableDependency($webform);
          $replacements[$original] = $webform->label();
          break;

        case 'source-entity':
          $source_entity = $webform_submission->getSourceEntity();
          if ($source_entity) {
            $bubbleable_metadata->addCacheableDependency($source_entity);
            $replacements[$original] = $source_entity->label();
          }
          else {
            $replacements[$original] = '';
          }
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
        // Parse [values:{name}:{format}] token with optional format.
        $keys = explode(':', $value_token);
        $key = $keys[0];
        $format_item = (isset($keys[1])) ? $keys[1] : NULL;
        $format_items = (isset($keys[2])) ? $keys[2] : NULL;
        if (isset($submission_data[$key]) && isset($form_elements[$key])) {
          $element = $form_elements[$key];
          // Apply #format to the element.
          if (isset($format_item)) {
            $element['#format'] = $format_item;
          }
          if (isset($format_items)) {
            $element['#format_items'] = $format_items;
          }
          $format_method = (empty($submission_options['html'])) ? 'formatText' : 'formatHtml';
          $value = $element_manager->invokeMethod($format_method, $element, $submission_data[$key], $submission_options);
          // Note, tokens can't include CSS and JS libraries since they will
          // can be included in an email.
          $replacements[$original] = (is_array($value)) ? \Drupal::service('renderer')->renderPlain($value) : (string) $value;
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

  return $replacements;
}
