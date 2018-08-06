<?php

/**
 * @file
 * Token integration for the Pathauto module.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function pathauto_token_info() {
  $info = array();

  $info['tokens']['array']['join-path'] = array(
    'name' => t('Joined path'),
    'description' => t('The array values each cleaned by Pathauto and then joined with the slash into a string that resembles an URL.'),
  );

  return $info;
}

/**
 * Implements hook_tokens().
 */
function pathauto_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = array();

  if ($type == 'array' && !empty($data['array'])) {
    $array = $data['array'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'join-path':
          $values = array();
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
