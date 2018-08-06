<?php

/**
 * @file
 * Defines stubs for Drush functions and constants so tests can run.
 *
 * @see \Drupal\config_update_ui\Tests\ConfigUpdateTest
 */

if (!function_exists('drush_print')) {

  /**
   * Stub for drush_print() function for testing.
   */
  function drush_print() {
  }

}

if (!function_exists('dt')) {

  /**
   * Stub for dt() function for testing.
   */
  function dt($text) {
    return $text;
  }

}

defined('STDERR') or define('STDERR', 25);
