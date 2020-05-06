<?php

/**
 * @file
 * Builds placeholder replacement tokens for webform group roles.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function webform_group_token_info() {
  $types = [];
  $types['webform_group'] = [
    'name' => t('Webform group'),
    'description' => t("Tokens related to webform group integration access. <em>This token is only available to a Webform email handler's 'To', 'CC', and 'BCC' email recipents.</em>"),
    'needs-data' => 'webform_group',
  ];

  $tokens = [];

  /** @var \Drupal\webform_group\WebformGroupManagerInterface $webform_group_manager */
  $webform_group_manager = \Drupal::service('webform_group.manager');

  // Role tokens.
  $webform_group = [];
  /** @var \Drupal\group\Entity\GroupTypeInterface[] $group_types */
  $group_types = \Drupal::entityTypeManager()->getStorage('group_type')->loadMultiple();
  $group_role_names = [];
  foreach ($group_types as $group_type_id => $group_type) {
    $group_roles = $group_type->getRoles();
    foreach ($group_roles as $group_role_id => $group_role) {
      if (($group_role->isInternal() && $group_role_id !== "$group_type_id-member")
        || !$group_role->inPermissionsUI()
        || $group_role->isAnonymous()) {
        continue;
      }

      if ($webform_group_manager->isGroupRoleTokenEnabled($group_role_id)) {
        $t_args = [
          '@group_type' => $group_type->label(),
          '@group_role' => $group_role->label(),
          '%group_type' => $group_type->label(),
          '%group_role' => $group_role->label(),
        ];
        $webform_group["role:$group_role_id"] = [
          'name' => t('@group_type: @group_role (Email addresses) for current group', $t_args),
          'description' => t('The email addresses of <strong>users</strong> assigned to the %group_type:%group_role group role for the current webform node.', $t_args),
        ];
      }

      $group_role_name = preg_replace("/^$group_type_id-/", "", $group_role_id);
      if ($webform_group_manager->isGroupRoleTokenEnabled($group_role_name)) {
        $group_role_names[$group_role_name] = $group_role->label();
      }
    }
  }
  foreach ($group_role_names as $group_role_name => $group_role_label) {
    $t_args = [
      '@group_role' => $group_role_label,
      '%group_role' => $group_role_label,
    ];
    $webform_group["role:$group_role_name"] = [
      'name' => t('Group role: @group_role (Email addresses) for current group', $t_args),
      'description' => t('The email addresses of <strong>users</strong> assigned to the %group_role group role (all groups) for the current webform node.', $t_args),
    ];
  }

  // Owner (Chained tokens) token.
  if ($webform_group_manager->isGroupOwnerTokenEnable()) {
    $webform_group['owner'] = [
      'name' => t('Group owner'),
      'description' => t('The user that owns  the group for current webform node.'),
      'type' => 'user',
    ];
  }

  $tokens['webform_group'] = $webform_group;

  /****************************************************************************/

  return ['types' => $types, 'tokens' => $tokens];
}

/**
 * Implements hook_tokens().
 */
function webform_group_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];
  if ($type === 'webform_group' && !empty($data['webform_group'])) {
    /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
    $webform_submission = $data['webform_group'];

    /** @var \Drupal\webform_group\WebformGroupManagerInterface $webform_group_manager */
    $webform_group_manager = \Drupal::service('webform_group.manager');
    $group_content = $webform_group_manager->getWebformSubmissionGroupContent($webform_submission);
    if (!$group_content) {
      return $replacements;
    }

    $group = $group_content->getGroup();
    $group_type = $group->getGroupType();
    $group_type_id = $group_type->id();
    foreach ($tokens as $name => $original) {
      $parts = explode(':', $name);
      $token_name = $parts[0];

      if ($token_name === 'role') {
        // Get group role id.
        $token_role_id = (isset($parts[1])) ? $parts[1] : NULL;
        if (!$token_role_id) {
          continue;
        }

        // Make sure the group token is allowed.
        if (!$webform_group_manager->isGroupRoleTokenEnabled($token_role_id)) {
          continue;
        }

        // Check if role id is just the role name and prepend the
        // current group's type id.
        $group_roles_ids = $group_type->getRoleIds();
        $group_type_role_id = "$group_type_id-$token_role_id";
        if (isset($group_roles_ids[$group_type_role_id])) {
          $token_role_id = $group_type_role_id;
        }

        // If token role id is *-member get all members.
        if ($token_role_id === "$group_type_id-member") {
          $members = $group->getMembers();
        }
        else {
          $members = $group->getMembers([$token_role_id]);
        }
        $emails = [];
        foreach ($members as $member) {
          $emails[] = $member->getUser()->getEmail();
        }
        $emails = array_unique($emails);

        $replacements[$original] = implode(',', $emails);
      }
    }

    /* Chained token relationships. */
    if ($webform_group_manager->isGroupOwnerTokenEnable()) {
      $token_service = \Drupal::token();
      if ($owner_tokens = $token_service->findWithPrefix($tokens, 'owner')) {
        $replacements += $token_service->generate('user', $owner_tokens, ['user' => $group->getOwner()], $options, $bubbleable_metadata);
      }
    }
  }
  return $replacements;
}
