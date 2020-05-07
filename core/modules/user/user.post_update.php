<?php

/**
 * @file
 * Post update functions for User module.
 */

/**
 * Implements hook_removed_post_updates().
 */
function user_removed_post_updates() {
  return [
    'user_post_update_enforce_order_of_permissions' => '9.0.0',
  ];
}
