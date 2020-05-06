<?php

/**
 * @file
 * Builds placeholder replacement tokens for webforms and submissions.
 */

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\Markup;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Drupal\webform\Element\WebformHtmlEditor;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Drupal\webform\Plugin\WebformElementEntityReferenceInterface;
use Drupal\webform\Plugin\WebformElement\WebformComputedBase;
use Drupal\webform\Plugin\WebformElement\WebformMarkupBase;
use Drupal\webform\Utility\WebformDateHelper;
use Drupal\webform\Utility\WebformHtmlHelper;
use Drupal\webform\Utility\WebformLogicHelper;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Implements hook_token_info().
 */
function webform_token_info() {
  $types = [];
  $tokens = [];

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
  $webform_submission['source-title'] = [
    'name' => t('Source URL'),
    'description' => t('The Title of the source entity or webform.'),
  ];
  $webform_submission['source-url'] = [
    'name' => t('Source URL'),
    'description' => t('The URL the user submitted the webform submission.'),
    'type' => 'url',
  ];
  $webform_submission['view-url'] = [
    'name' => t('View (token) URL'),
    'description' => t('The URL that can used to view the webform submission. The webform must be configured to allow users to view a submission using a secure token.'),
    'type' => 'url',
  ];
  $webform_submission['update-url'] = [
    'name' => t('Update (token) URL'),
    'description' => t('The URL that can used to update the webform submission. The webform must be configured to allow users to update a submission using a secure token.'),
    'type' => 'url',
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
    'description' => t('The current (last submitted) wizard page of the webform submission.'),
  ];
  $webform_submission['current-page:title'] = [
    'name' => t('Current page title'),
    'description' => t('The current (last submitted) wizard page title of the webform submission.'),
  ];
  $webform_submission['in-draft'] = [
    'name' => t('In draft'),
    'description' => t('Is the webform submission in draft.'),
  ];
  $webform_submission['state'] = [
    'name' => t('State'),
    'description' => t('The state of the webform submission. (Unsaved, Draft, Completed, Updated, Locked, or Converted)'),
  ];
  $webform_submission['state:raw'] = [
    'name' => t('State (Raw value)'),
    'description' => t('The state raw value untranslated of the webform submission. (unsaved, draft, completed, updated, locked, or converted)'),
  ];
  $webform_submission['label'] = [
    'name' => t('Label'),
    'description' => t('The label of the webform submission.'),
  ];
  // Limit: Webform.
  $webform_submission['limit:webform'] = [
    'name' => t('Total submissions limit'),
    'description' => t('The total number of submissions allowed for the webform.'),
  ];
  $webform_submission['interval:webform'] = [
    'name' => t('Total submissions limit interval'),
    'description' => t('The total submissions interval for the webform.'),
  ];
  $webform_submission['interval:webform:wait'] = [
    'name' => t('Wait time before next submission'),
    'description' => t('The amount of time before the next allowed submission for the webform.'),
  ];
  $webform_submission['total:webform'] = [
    'name' => t('Total submissions'),
    'description' => t('The current number of submissions for the webform.'),
  ];
  $webform_submission['remaining:webform'] = [
    'name' => t('Remaining number of submissions'),
    'description' => t('The remaining number of submissions for the webform.'),
  ];
  // Limit: User.
  $webform_submission['limit:user'] = [
    'name' => t('Per user submission limit'),
    'description' => t('The total number of submissions allowed per user for the webform.'),
  ];
  $webform_submission['interval:user'] = [
    'name' => t('Per user submission limit interval'),
    'description' => t('The total submissions interval per user for the webform.'),
  ];
  $webform_submission['interval:user:wait'] = [
    'name' => t('Per user wait time before next submission'),
    'description' => t('The amount of time before the next allowed submission per user for the webform.'),
  ];
  $webform_submission['total:user'] = [
    'name' => t('Per user total submissions'),
    'description' => t('The current number of submissions for the user for the webform.'),
  ];
  $webform_submission['remaining:user'] = [
    'name' => t('Per user remaining number of submissions'),
    'description' => t('The remaining number of submissions for the user for the webform.'),
  ];
  // Limit: Source entity.
  $webform_submission['limit:webform:source_entity'] = [
    'name' => t('Total submissions limit per source entity'),
    'description' => t('The total number of submissions allowed for the webform source entity.'),
  ];
  $webform_submission['interval:webform:source_entity'] = [
    'name' => t('Total submissions limit interval per source entity'),
    'description' => t('The total submissions interval for the webform source entity.'),
  ];
  $webform_submission['interval:webform:source_entity:wait'] = [
    'name' => t('Wait time before next submission for a source entity'),
    'description' => t('The amount of time before the next allowed submission for the webform source entity.'),
  ];
  $webform_submission['total:webform:source_entity'] = [
    'name' => t('Total submissions for source entity'),
    'description' => t('The current number of submissions for the webform source entity.'),
  ];
  $webform_submission['remaining:webform:source_entity'] = [
    'name' => t('Remaining number of submissions for source entity'),
    'description' => t('Remaining number of submissions for the webform source entity.'),
  ];
  // Limit: User and Source entity.
  $webform_submission['limit:user:source_entity'] = [
    'name' => t('Per user submission limit for a source entity'),
    'description' => t('The total number of submissions allowed per user for the webform source entity.'),
  ];
  $webform_submission['interval:user:source_entity'] = [
    'name' => t('Per user submission limit interval for a source entity'),
    'description' => t('The total submissions interval per user for the webform source entity.'),
  ];
  $webform_submission['interval:user:source_entity:wait'] = [
    'name' => t('Per user wait time before next submission for a source entity'),
    'description' => t('The amount of time before the next allowed submission per user for the webform source entity.'),
  ];
  $webform_submission['total:user:source_entity'] = [
    'name' => t('Per user total submissions for a source entity'),
    'description' => t('The current number of submissions for the user for the webform source entity.'),
  ];
  $webform_submission['remaining:user:source_entity'] = [
    'name' => t('Per user remaining number of submissions for a source entity'),
    'description' => t('The remaining number of submissions for the user for the webform source entity.'),
  ];

  // Dynamic tokens for webform submissions.
  $webform_submission['url'] = [
    'name' => t('URL'),
    'description' => t("The URL of the webform submission. Replace the '?' with the link template. Defaults to 'canonical' which displays the submission's data."),
    'dynamic' => TRUE,
  ];
  $webform_submission['values'] = [
    'name' => t('Submission values'),
    'description' => Markup::create(t('Webform tokens from submitted data.') .
      _webform_token_render_more(t('Learn about submission value tokens'),
        t("Omit the '?' to output all values. Output all values as HTML using [webform_submission:values:html].") . '<br />' .
        t("To output individual elements, replace the '?' with…") . '<br />' .
        '<ul>' .
        '<li>element_key</li>' .
        '<li>element_key:format</li>' .
        '<li>element_key:raw</li>' .
        '<li>element_key:format:items</li>' .
        '<li>element_key:delta</li>' .
        '<li>element_key:sub_element_key</li>' .
        '<li>element_key:delta:sub_element_key</li>' .
        '<li>element_key:sub_element_key:format</li>' .
        '<li>element_key:delta:sub_element_key:format</li>' .
        '<li>element_key:delta:format</li>' .
        '<li>element_key:delta:format:html</li>' .
        '<li>element_key:entity:*</li>' .
        '<li>element_key:delta:entity:*</li>' .
        '<li>element_key:delta:entity:field_name:*</li>' .
        '<li>element_key:sub_element_key:entity:*</li>' .
        '<li>element_key:sub_element_key:entity:field_name:*</li>' .
        '<li>element_key:delta:sub_element_key:entity:*</li>' .
        '<li>element_key:checked:option_value</li>' .
        '<li>element_key:selected:option_value</li>' .
        '</ul>' .
        t("All items after the 'element_key' are optional.") . '<br />' .
        t("The 'delta' is the numeric index for specific value") . '<br />' .
        t("The 'sub_element_key' is a composite element's sub element key.") . '<br />' .
        t("The 'checked'  or 'selected' looks to see if an 'option_value' is checked or selected for an options element (select, checkboxes, or radios)") . '<br />' .
        t("The 'option_value' is options value for an options element (select, checkboxes, or radios).") . '<br />' .
        t("The 'format' can be 'value', 'raw', or custom format specifically associated with the element") . '<br />' .
        t("The 'items' can be 'comma', 'semicolon', 'and', 'ol', 'ul', or custom delimiter") . '<br />' .
        t("The 'entity:*' applies to the referenced entity") . '<br />' .
        t("Add 'html' at the end of the token to return HTML markup instead of plain text.") . '<br />' .
        t("For example, to display the Contact webform's 'Subject' element's value you would use the [webform_submission:values:subject] token.")
      )
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
    'dynamic' => TRUE,
  ];
  $webform_submission['source-title'] = [
    'name' => t('Source title'),
    'description' => t('The source entity title that the webform submission was submitted from, defaults to the webform title when there is no source entity.'),
    'type' => 'entity',
    'dynamic' => TRUE,
  ];
  $webform_submission['submitted-to'] = [
    'name' => t('Submitted to'),
    'description' => t('The source entity or webform that the webform submission was submitted from.'),
    'type' => 'entity',
    'dynamic' => TRUE,
  ];

  // Append link to token help to source-entity and submitted-to description.
  if (\Drupal::moduleHandler()->moduleExists('token') && \Drupal::moduleHandler()->moduleExists('help')) {
    $t_args = [':href' => Url::fromRoute('help.page', ['name' => 'token'])->toString()];
    $token_help = t('For a list of the currently available source entity related tokens, please see <a href=":href">token help</a>.', $t_args);
    $webform_submission['source-entity']['description'] = Markup::create($webform_submission['source-entity']['description'] . '<br/>' . $token_help);
    $webform_submission['submitted-to']['description'] = Markup::create($webform_submission['submitted-to']['description'] . '<br/>' . $token_help);
  }

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
  $webform['element'] = [
    'name' => t('Element properties'),
    'description' => Markup::create(t('Webform element property tokens.') .
      _webform_token_render_more(t('Learn about element property tokens'),
        t("Replace the '?' with…") . '<br />' .
        '<ul>' .
        '<li>element_key:title</li>' .
        '<li>element_key:description</li>' .
        '<li>element_key:help</li>' .
        '<li>element_key:more</li>' .
        '</ul>' .
        t("For example, to display an email element's title (aka #title) you would use the [webform:element:email:title] token.")
      )
    ),
    'dynamic' => TRUE,
  ];
  $webform['handler'] = [
    'name' => t('Handler response'),
    'description' => Markup::create(t('Webform handler response tokens.') .
      _webform_token_render_more(t('Learn about handler response tokens'),
        t("Replace the '?' with…") . '<br />' .
        '<ul>' .
        '<li>handler_id:state:key</li>' .
        '<li>handler_id:state:key1:key2</li>' .
        '</ul>' .
        t("For example, to display a remote post's confirmation number you would use the [webform:handler:remote_post:completed:confirmation_number] token.")
      )
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
      'description' => t("Tokens related to user roles that can receive email. <em>This token is only available to a Webform email handler's 'To', 'CC', and 'BCC' email recipients.</em>"),
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
  if ($type == 'webform_role' && !empty($data['webform_role'])) {
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

    $source_entity = $webform_submission->getSourceEntity(TRUE);
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
          $replacements[$original] = $webform_submission->isDraft() ? t('Yes') : t('No');
          break;

        case 'state':
          $replacements[$original] = $webform_submission->getState();
          break;

        case 'state:label':
          $states = [
            WebformSubmissionInterface::STATE_DRAFT_CREATED => t('Draft created'),
            WebformSubmissionInterface::STATE_DRAFT_UPDATED => t('Draft updated'),
            WebformSubmissionInterface::STATE_COMPLETED => t('Completed'),
            WebformSubmissionInterface::STATE_CONVERTED => t('Converted'),
            WebformSubmissionInterface::STATE_UPDATED => t('Updated'),
            WebformSubmissionInterface::STATE_UNSAVED => t('Unsaved'),
            WebformSubmissionInterface::STATE_LOCKED => t('Locked'),
          ];
          $replacements[$original] = $states[$webform_submission->getState()];
          break;

        case 'current-page':
        case 'current-page:title':
          $current_page = $webform_submission->current_page->value;
          $pages = $webform->getPages();
          if (empty($current_page)) {
            $page_keys = array_keys($pages);
            $current_page = reset($page_keys);
          }
          $replacements[$original] = ($name === 'current-page:title' && isset($pages[$current_page])) ? $pages[$current_page]['#title'] : $current_page;
          break;

        case 'language':
          $replacements[$original] = \Drupal::languageManager()->getLanguage($webform_submission->langcode->value)->getName();
          break;

        case 'source-title':
          $replacements[$original] = ($source_entity) ? $source_entity->label() : $webform->label();
          break;

        case 'source-url':
          $replacements[$original] = $webform_submission->getSourceUrl()->toString();
          break;

        case 'view-url':
          $replacements[$original] = $webform_submission->getTokenUrl('view')->toString();
          break;

        case 'update-url':
          $replacements[$original] = $webform_submission->getTokenUrl('update')->toString();
          break;

        case 'token':
          $replacements[$original] = $webform_submission->getToken();
          break;

        case 'label':
          $replacements[$original] = $webform_submission->label();
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

        case 'interval:webform':
          $replacements[$original] = WebformDateHelper::getIntervalText($webform->getSetting('limit_total_interval'));
          break;

        case 'interval:webform:wait':
          $replacements[$original] = _webform_token_get_interval_wait('limit_total_interval', $bubbleable_metadata, $webform);
          break;

        case 'total:webform':
          $replacements[$original] = $submission_storage->getTotal($webform);
          break;

        case 'remaining:webform':
          $limit = $webform->getSetting('limit_total');
          $total = $submission_storage->getTotal($webform);
          if ($limit && $total !== NULL) {
            $replacements[$original] = ($limit > $total) ? $limit - $total : 0;
          }
          break;

        case 'limit:user':
          $replacements[$original] = $webform->getSetting('limit_user') ?: t('None');
          break;

        case 'interval:user':
          $replacements[$original] = WebformDateHelper::getIntervalText($webform->getSetting('limit_user_interval'));
          break;

        case 'interval:user:wait':
          $replacements[$original] = _webform_token_get_interval_wait('limit_user_interval', $bubbleable_metadata, $webform, NULL, $account);
          break;

        case 'total:user':
          $replacements[$original] = $submission_storage->getTotal($webform, NULL, $account);
          break;

        case 'remaining:user':
          $limit = $webform->getSetting('limit_user');
          $total = $submission_storage->getTotal($webform, NULL, $account);
          if ($limit && $total !== NULL) {
            $replacements[$original] = ($limit > $total) ? $limit - $total : 0;
          }
          break;

        case 'limit:webform:source_entity':
          $replacements[$original] = $webform->getSetting('entity_limit_total') ?: t('None');
          break;

        case 'interval:webform:source_entity':
          $replacements[$original] = WebformDateHelper::getIntervalText($webform->getSetting('entity_limit_total_interval'));
          break;

        case 'interval:webform:source_entity:wait':
          $replacements[$original] = $source_entity ? _webform_token_get_interval_wait('entity_limit_total_interval', $bubbleable_metadata, $webform, $source_entity) : '';
          break;

        case 'total:webform:source_entity':
          $replacements[$original] = $source_entity ? $submission_storage->getTotal($webform, $source_entity) : '';
          break;

        case 'remaining:webform:source_entity':
          $limit = $webform->getSetting('entity_limit_total');
          $total = $source_entity ? $submission_storage->getTotal($webform, $source_entity) : NULL;
          if ($limit && $total !== NULL) {
            $replacements[$original] = ($limit > $total) ? $limit - $total : 0;
          }
          break;

        case 'limit:user:source_entity':
          $replacements[$original] = $webform->getSetting('entity_limit_user') ?: t('None');
          break;

        case 'interval:user:source_entity':
          $replacements[$original] = WebformDateHelper::getIntervalText($webform->getSetting('entity_limit_user_interval'));
          break;

        case 'interval:user:source_entity:wait':
          $replacements[$original] = $source_entity ? _webform_token_get_interval_wait('entity_limit_user_interval', $bubbleable_metadata, $webform, $source_entity, $account) : '';
          break;

        case 'total:user:source_entity':
          $replacements[$original] = $source_entity ? $submission_storage->getTotal($webform, $source_entity, $account) : '';
          break;

        case 'remaining:user:source_entity':
          $limit = $webform->getSetting('entity_limit_user');
          $total = $source_entity ? $submission_storage->getTotal($webform, $source_entity, $account) : NULL;
          if ($limit && $total !== NULL) {
            $replacements[$original] = ($limit > $total) ? $limit - $total : 0;
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
    if (($source_entity_tokens = $token_service->findWithPrefix($tokens, 'source-entity')) && ($source_entity = $webform_submission->getSourceEntity(TRUE))) {
      $replacements += $token_service->generate($source_entity->getEntityTypeId(), $source_entity_tokens, [$source_entity->getEntityTypeId() => $source_entity], $options, $bubbleable_metadata);
    }
    if (($submitted_to_tokens = $token_service->findWithPrefix($tokens, 'submitted-to')) && ($submitted_to = $webform_submission->getSourceEntity(TRUE) ?: $webform_submission->getWebform())) {
      $replacements += $token_service->generate($submitted_to->getEntityTypeId(), $submitted_to_tokens, [$submitted_to->getEntityTypeId() => $submitted_to], $options, $bubbleable_metadata);
    }
    foreach (['view-url', 'update-url', 'source-url'] as $token) {
      if ($url_tokens = $token_service->findWithPrefix($tokens, $token)) {
        $url = NULL;
        switch ($token) {
          case 'view-url':
            $url = $webform_submission->getTokenUrl('view');
            break;

          case 'update-url':
            $url = $webform_submission->getTokenUrl('update');
            break;

          case 'source-url':
            $url = $webform_submission->getSourceUrl();
            break;
        }
        if ($url) {
          $replacements += $token_service->generate('url', $url_tokens, ['url' => $url], $options, $bubbleable_metadata);
        }
      }
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

    if (($element_tokens = $token_service->findWithPrefix($tokens, 'element'))) {
      foreach ($element_tokens as $key => $original) {
        if (strpos($key, ':') === FALSE) {
          $element_key = $key;
          $element_property = 'title';
        }
        else {
          list($element_key, $element_property) = explode(':', $key);
        }
        $element_property = $element_property ?: 'title';
        $element = $webform->getElement($element_key);
        if ($element && isset($element["#$element_property"]) && is_string($element["#$element_property"])) {
          $token_value = $element["#$element_property"];
          if (in_array($element_property, ['description', 'help', 'more', 'terms_content'])) {
            $token_value = WebformHtmlEditor::checkMarkup($token_value);
            $token_value = \Drupal::service('renderer')->renderPlain($token_value);
          }
          else {
            $token_value = WebformHtmlHelper::toHtmlMarkup($token_value);
          }
          $replacements[$original] = $token_value;
        }
      }
    }

    if (($handler_tokens = $token_service->findWithPrefix($tokens, 'handler'))) {
      foreach ($handler_tokens as $key => $original) {
        $webform_handler = isset($data['webform_handler']) ? $data['webform_handler'] : [];
        $parents = explode(':', $key);
        $key_exists = NULL;
        $value = NestedArray::getValue($webform_handler, $parents, $key_exists);
        // A handler response is always considered safe markup.
        $replacements[$original] = ($key_exists) ? Markup::create($value) : $original;
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
  // [values:{element_key}:{format}:urlencode]
  // [values:{element_key}:{format}:{items}:urlencode]
  // [values:{element_key}:{delta}:{format}]
  // [values:{element_key}:{delta}:{sub-element}]
  $keys = explode(':', $value_token);
  $element_key = array_shift($keys);

  // Build HTML values.
  if ($element_key == 'html' && empty($keys)) {
    $options['html'] = TRUE;
    return _webform_token_get_submission_values($options, $webform_submission);
  }

  // Set default options.
  $options += [
    'html' => FALSE,
  ];

  // Parse suffixes and set options.
  $suffixes = [
    // Indicates the tokens should be formatted as HTML instead of plain text.
    'html',
  ];
  foreach ($suffixes as $suffix) {
    if ($keys && in_array($suffix, $keys)) {
      $keys = array_diff($keys, [$suffix]);
      $options[$suffix] = TRUE;
    }
  }

  $element = $webform_submission->getWebform()->getElement($element_key, TRUE);

  // Exit if form element does not exist.
  if (!$element) {
    return NULL;
  }

  $element_plugin = $element_manager->getElementInstance($element);

  // Always get value for a computed element.
  if ($element_plugin instanceof WebformComputedBase) {
    return $element_plugin->getValue($element, $webform_submission);
  }

  // Always get rendered markup for a markup element.
  if ($element_plugin instanceof WebformMarkupBase) {
    $format_method = (empty($options['html'])) ? 'buildText' : 'buildHtml';
    $element['#display_on'] = WebformMarkupBase::DISPLAY_ON_BOTH;
    $token_value = $element_manager->invokeMethod($format_method, $element, $webform_submission, $options);
    return \Drupal::service('renderer')->renderPlain($token_value);
  }

  // Exit if no submission data and form element is not a container.
  if (!isset($submission_data[$element_key]) && !$element_plugin->isContainer($element)) {
    return NULL;
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

  /****************************************************************************/
  // Get value.
  /****************************************************************************/

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
      $token_value = Markup::create(\Drupal::token()->replace(
        "[$entity_token_name:$entity_token]",
        [$entity_token_name => $entity],
        $options,
        $bubbleable_metadata
      ));
      return $token_value;
    }
    else {
      return '';
    }
  }

  // Set checked/selected for an options elements.
  if ($keys && in_array($keys[0], ['checked', 'selected']) && $element_plugin->hasProperty('options')) {
    $token_values = (array) $element_plugin->getValue($element, $webform_submission);
    return ($token_values && in_array($keys[1], $token_values)) ? '1' : '0';
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

  $token = "[webform_submission:values:$value_token]";
  if (WebformLogicHelper::startRecursionTracking($token) === FALSE) {
    return '';
  }

  $format_method = (empty($options['html'])) ? 'formatText' : 'formatHtml';
  $token_value = $element_manager->invokeMethod($format_method, $element, $webform_submission, $options);
  if (is_array($token_value)) {
    // Note, tokens can't include CSS and JS libraries since they will
    // can be included in an email.
    $token_value = \Drupal::service('renderer')->renderPlain($token_value);
  }
  elseif (isset($element['#format']) && $element['#format'] === 'raw') {
    // Make sure raw tokens are always rendered AS-IS.
    $token_value = Markup::create((string) $token_value);
  }
  elseif (!($token_value instanceof MarkupInterface)) {
    // All strings will be escaped as HtmlEscapedText.
    // @see \Drupal\Core\Utility\Token::replace
    // @see \Drupal\Component\Render\HtmlEscapedText
    $token_value = (string) $token_value;
  }

  if (WebformLogicHelper::stopRecursionTracking($token) === FALSE) {
    return '';
  }

  return $token_value;
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
  $token = (!empty($options['html'])) ? '[webform_submission:values:html]' : '[webform_submission:values]';

  if (WebformLogicHelper::startRecursionTracking($token) === FALSE) {
    return '';
  }

  $submission_format = (!empty($options['html'])) ? 'html' : 'text';
  /** @var \Drupal\webform\WebformSubmissionViewBuilderInterface $view_builder */
  $view_builder = \Drupal::entityTypeManager()->getViewBuilder('webform_submission');
  $form_elements = $webform_submission->getWebform()->getElementsInitialized();
  $token_value = $view_builder->buildElements($form_elements, $webform_submission, $options, $submission_format);

  // Note, tokens can't include CSS and JS libraries since they can be
  // included in an email.
  $value = \Drupal::service('renderer')->renderPlain($token_value);

  if (WebformLogicHelper::stopRecursionTracking($token) === FALSE) {
    return '';
  }

  return $value;
}

/**
 * Render webform more element (slideouts) for token descriptions.
 *
 * @param string $more_title
 *   More title.
 * @param string $more
 *   More content.
 *
 * @return string
 *   Rendered webform more element.
 */
function _webform_token_render_more($more_title, $more) {
  $build = [
    '#type' => 'webform_more',
    '#more' => $more,
    '#more_title' => $more_title,
  ];

  // Token info might be called via CLI and not all modules are loaded
  // or an active theme is defined.
  //
  // Prevent the below expections:
  // - The theme implementations may not be rendered until all modules
  //   are loaded.
  // - Call to a member function setParser() on array in Twig\Parser->parse().
  //
  // @see \Drupal\Core\Theme\ThemeManager::render
  /** @var \Drupal\Core\Extension\ModuleHandlerInterface $module_handler */
  $module_handler = \Drupal::service('module_handler');
  /** @var \Drupal\webform\WebformThemeManagerInterface $theme_manager */
  $theme_manager = \Drupal::service('webform.theme_manager');
  if (!$module_handler->isLoaded() || !$theme_manager->hasActiveTheme()) {
    return '';
  }

  return (string) \Drupal::service('renderer')->renderPlain($build);
}

/**
 * Get interval wait time.
 *
 * @param string $interval_setting
 *   Interval setting name.
 * @param \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata
 *   (optional) Object to collect path processors' bubbleable metadata.
 * @param \Drupal\webform\WebformInterface|null $webform
 *   (optional) A webform. If set the total number of submissions for the
 *   Webform will be returned.
 * @param \Drupal\Core\Entity\EntityInterface|null $source_entity
 *   (optional) A webform submission source entity.
 * @param \Drupal\Core\Session\AccountInterface|null $account
 *   (optional) A user account.
 *
 * @return string
 *   Formatted interval wait time.
 */
function _webform_token_get_interval_wait($interval_setting, BubbleableMetadata $bubbleable_metadata, WebformInterface $webform = NULL, EntityInterface $source_entity = NULL, AccountInterface $account = NULL) {
  // Get last submission completed time.
  /** @var \Drupal\webform\WebformSubmissionStorageInterface $submission_storage */
  $submission_storage = \Drupal::entityTypeManager()->getStorage('webform_submission');
  $options = ['access_check' => FALSE];
  $last_submission = $submission_storage->getLastSubmission($webform, $source_entity, $account, $options);
  if (!$last_submission) {
    return '';
  }

  $completed_time = $last_submission->getCompletedTime();

  // Get interval.
  $interval = $webform->getSetting($interval_setting);

  // Get wait time.
  $wait_time = ($completed_time + $interval);

  // Get request time.
  $request_time = \Drupal::request()->server->get('REQUEST_TIME');

  // Set cache max age.
  $max_age = ($wait_time - $request_time);
  $bubbleable_metadata->setCacheMaxAge(($max_age > 0) ? $max_age : 0);

  // Format time diff until next allows submission.
  /** @var \Drupal\Core\Datetime\DateFormatterInterface $date_formatter */
  $date_formatter = \Drupal::service('date.formatter');
  return $date_formatter->formatTimeDiffUntil($wait_time);
}
