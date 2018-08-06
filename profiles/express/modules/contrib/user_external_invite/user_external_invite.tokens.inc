<?php

/**
 * @file
 * Token integration for the User External Invite module.
 */


/**
 * Implements hook_token_info().
 */
function user_external_invite_token_info() {
  $info = [];

  $info['tokens']['array']['join-path'] = [
    'name' => t('Joined path'),
    'description' => t('The array values each cleaned by Pathauto and then joined with the slash into a string that resembles an URL.'),
  ];

  return $info;
}

/**
 * Implements hook_tokens().
 */
function user_external_invite_tokens($type, $tokens, array $data, array $options) {
  $replacements = [];

  if ($type == 'array' && !empty($data['array'])) {
    $array = $data['array'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'join-path':
          $values = [];
          foreach (token_element_children($array) as $key) {
            $value = is_array($array[$key]) ? render($array[$key]) : (string) $array[$key];
            $value = \Drupal::service('pathauto.alias_cleaner')->cleanString($value, $options);
            $values[] = $value;
          }
          $replacements[$original] = implode('/', $values);
          break;
      }
    }
  }

  return $replacements;
}
