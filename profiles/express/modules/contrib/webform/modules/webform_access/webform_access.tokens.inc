<?php

/**
 * @file
 * Builds placeholder replacement tokens for webform access type.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\webform_access\Entity\WebformAccessType;

/**
 * Implements hook_token_info().
 */
function webform_access_token_info() {
  $types = [];
  $types['webform_access'] = [
    'name' => t('Webform access'),
    'description' => t("Tokens related to webform access group types. <em>This token is only available to a Webform email handler's 'To', 'CC', and 'BCC' email recipents.</em>"),
    'needs-data' => 'webform_access',
  ];

  $tokens = [];
  $webform_access = [];
  $webform_access_types = WebformAccessType::loadMultiple();
  $webform_access['type'] = [
    'name' => t('All users and custom email addresses'),
    'description' => t('The email addresses of all <strong>users</strong> and <strong>custom email addresses</strong> assigned to the current webform.'),
  ];
  $webform_access['users'] = [
    'name' => t('All users'),
    'description' => t('The email addresses of all <strong>users</strong> assigned to the current webform.'),
  ];
  $webform_access['emails'] = [
    'name' => t('All custom email addresses'),
    'description' => t('The email addresses of all <strong>custom email addresses</strong> assigned to the current webform.'),
  ];
  $webform_access['admins'] = [
    'name' => t('All adminstrators'),
    'description' => t('The email addresses of all <strong>administrators</strong> assigned to the current webform.'),
  ];
  $webform_access['all'] = [
    'name' => t('All users, custom email addresses, and administrators'),
    'description' => t('The email addresses of all <strong>users</strong>, <strong>custom email addresses</strong>, and <strong>administrators</strong> assigned to the current webform.'),
  ];
  foreach ($webform_access_types as $webform_access_type_name => $webform_access_type) {
    $t_args = ['@label' => $webform_access_type->label()];
    $webform_access["type:$webform_access_type_name"] = [
      'name' => t('@label (Users and custom email addresses)', $t_args),
      'description' => t('The email addresses of <strong>users</strong> and <strong>custom email addresses</strong> assigned to the %title access type for the current webform.', ['%title' => $webform_access_type->label()]),
    ];
    $webform_access["type:$webform_access_type_name:users"] = [
      'name' => t('@label (Users)', $t_args),
      'description' => t('The email addresses of <strong>users</strong> assigned to the %title access type for the current webform.', ['%title' => $webform_access_type->label()]),
    ];
    $webform_access["type:$webform_access_type_name:emails"] = [
      'name' => t('@label (Custom email addresses)', $t_args),
      'description' => t('The email addresses of <strong>custom email addresses</strong> assigned to the %title access type for the current webform.', ['%title' => $webform_access_type->label()]),
    ];
    $webform_access["type:$webform_access_type_name:admins"] = [
      'name' => t('@label (Adminstrators)', $t_args),
      'description' => t('The email addresses of <strong>administrators</strong> assigned to the %title access type for the current webform.', ['%title' => $webform_access_type->label()]),
    ];
    $webform_access["type:$webform_access_type_name:all"] = [
      'name' => t('@label ((Users, custom email addresses, Adminstrators)', $t_args),
      'description' => t('The email addresses of <strong>users</strong>, <strong>custom email addresses</strong>, and <strong>administrators</strong> assigned to the %title access type for the current webform.', ['%title' => $webform_access_type->label()]),
    ];
  }
  $tokens['webform_access'] = $webform_access;

  /****************************************************************************/

  return ['types' => $types, 'tokens' => $tokens];
}

/**
 * Implements hook_tokens().
 */
function webform_access_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];
  if ($type == 'webform_access' && !empty($data['webform_access'])) {
    /** @var \Drupal\webform_access\WebformAccessGroupStorageInterface $webform_access_group_storage */
    $webform_access_group_storage = \Drupal::entityTypeManager()->getStorage('webform_access_group');

    /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
    $webform_submission = $data['webform_access'];
    $webform = $webform_submission->getWebform();
    $source_entity = $webform_submission->getSourceEntity();
    foreach ($tokens as $name => $original) {
      // $name => [webform_access:$type:$webform_access_type_id:$property].
      $parts = explode(':', $name);

      // Type is always defined (type, admins, users, or emails)
      $type = $parts[0];
      // Access type id is optional.
      $webform_access_type_id = (isset($parts[1])) ? $parts[1] : NULL;
      // Properties can be admins, users, or emails.
      $property = (isset($parts[2])) ? $parts[2] : NULL;

      /** @var \Drupal\webform_access\WebformAccessGroupInterface $webform_access_group */
      $webform_access_groups = $webform_access_group_storage->loadByEntities($webform, $source_entity, NULL, $webform_access_type_id);
      $webform_access_group_ids = array_keys($webform_access_groups);

      if ($webform_access_groups) {
        $emails = [];

        // Get access group administrator email addresses.
        // Administrator email addresss are only returned if explicitly
        // requested via [webform_access:type:admins] or
        // [webform_access:type:TYPE_ID:admins].
        if (in_array($type, ['admins', 'all'])
          || in_array($property, ['admins', 'all'])) {
          $emails = array_merge(
            $emails,
            _webform_access_tokens_get_access_group_emails('admin', $webform_access_group_ids)
          );
        }

        // Get access group user email addresses.
        // [webform_access:users]
        // [webform_access:type:TYPE_ID]
        // [webform_access:type:TYPE_ID:users]
        if (in_array($type, ['users', 'all'])
          || ($type === 'type' && (empty($property) || in_array($property, ['users', 'all'])))) {
          $emails = array_merge(
            $emails,
            _webform_access_tokens_get_access_group_emails('user', $webform_access_group_ids)
          );
        }

        // Get access group 'custom' email addresses.
        // [webform_access:emails]
        // [webform_access:type:TYPE_ID]
        // [webform_access:type:TYPE_ID:emails]
        if (in_array($type, ['emails', 'all'])
          || ($type === 'type' && (empty($property) || in_array($property, ['emails', 'all'])))) {
          foreach ($webform_access_groups as $webform_access_group) {
            if ($webform_access_group_emails = $webform_access_group->getEmails()) {
              $emails = array_merge($emails, $webform_access_group_emails);
            }
          }
        }

        $emails = array_unique($emails);
        $replacements[$original] = implode(',', $emails);
      }
    }
  }

  return $replacements;
}

/**
 * Get webform access group administrator or user email addresses.
 *
 * @param string $type
 *   The type of user (admin or user).
 * @param array $group_ids
 *   An array of webform access group ids.
 *
 * @return string
 *   Administrator or user email addresses.
 *
 * @internal
 */
function _webform_access_tokens_get_access_group_emails($type, array $group_ids) {
  $query = \Drupal::database()->select('webform_access_group_' . $type, 'gu');
  $query->condition('gu.group_id', $group_ids, 'IN');
  $query->join('users_field_data', 'u', 'u.uid = gu.uid');
  $query->fields('u', ['mail']);
  $query->condition('u.status', 1);
  $query->condition('u.mail', '', '<>');
  $query->orderBy('mail');
  $query->distinct();
  return $query->execute()->fetchCol();
}
