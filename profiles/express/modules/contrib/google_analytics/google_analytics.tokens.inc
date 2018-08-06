<?php

/**
 * @file
 * Builds placeholder replacement tokens for user-related data.
 */

use Drupal\Component\Utility\Html;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function google_analytics_token_info() {
  $user['role-names'] = [
    'name' => t('User role names'),
    'description' => t('The role names the user account is a member of as comma separated list.'),
    'needs-data' => 'user',
  ];
  $user['role-ids'] = [
    'name' => t('User role ids'),
    'description' => t('The role ids the user account is a member of as comma separated list.'),
    'needs-data' => 'user',
  ];

  return [
    'tokens' => ['user' => $user],
  ];
}

/**
 * Implements hook_tokens().
 */
function google_analytics_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $sanitize = !empty($options['sanitize']);
  $replacements = [];

  if ($type == 'user' && !empty($data['user'])) {
    $account = $data['user'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        // Basic user account information.
        case 'role-names':
          $names = implode(',', $account->getRoles());
          $replacements[$original] = $sanitize ? Html::escape($names) : $names;
          $bubbleable_metadata->addCacheableDependency($account);
          break;

        case 'role-ids':
          $ids = implode(',', array_keys($account->getRoles()));
          $replacements[$original] = $sanitize ? Html::escape($ids) : $ids;
          $bubbleable_metadata->addCacheableDependency($account);
          break;
      }
    }
  }

  return $replacements;
}
