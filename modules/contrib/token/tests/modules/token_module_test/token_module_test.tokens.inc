<?php

/**
 * Implements hook_token_info()
 */
function token_module_test_token_info() {
  $info['tokens']['node']['colons:in:name'] = [
    'name' => t('A test token with colons in the name'),
    'description' => NULL,
  ];

  return $info;
}
