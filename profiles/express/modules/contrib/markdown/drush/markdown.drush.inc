<?php

/**
 * @file
 *   drush integration for markdown filter.
 */

/**
 * The Markdown plugin URI.
 */
define('PHP_MARKDOWN_EXTRA_DOWNLOAD_URI', 'https://github.com/michelf/php-markdown/archive/lib.zip');
define('PHP_MARKDOWN_EXTRA_DIR_NAME', 'php-markdown-lib');

/**
 * Implementation of hook_drush_command().
 *
 * In this hook, you specify which commands your
 * drush module makes available, what it does and
 * description.
 *
 * Notice how this structure closely resembles how
 * you define menu hooks.
 *
 * See `drush topic docs-commands` for a list of recognized keys.
 *
 * @return
 *   An associative array describing your command(s).
 */
function markdown_drush_command() {
  $items = array();

  // the key in the $items array is the name of the command.
  $items['markdown-plugin'] = array(
    'callback' => 'drush_markdown_plugin',
    'description' => dt('Download and install a selection of Markdown plugins.'),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUSH, // No bootstrap.
    'arguments' => array(
      'path' => dt('Optional. A path where to install the Markdown plugin. If omitted Drush will use the default location.'),
    ),
    'aliases' => array('markdownplugin'),
  );

  return $items;
}

/**
 * Implementation of hook_drush_help().
 *
 * This function is called whenever a drush user calls
 * 'drush help <name-of-your-command>'
 *
 * @param
 *   A string with the help section (prepend with 'drush:')
 *
 * @return
 *   A string with the help text for your command.
 */
function markdown_drush_help($section) {
  switch ($section) {
    case 'drush:markdown-plugin':
      return dt('Download and install a selection of Markdown plugins, default location is the libraries directory.');
  }
}

/**
 * Command to download the Markdown plugin.
 */
function drush_markdown_plugin() {
  $args = func_get_args();
  if (!empty($args[0])) {
    $path = $args[0];
  }
  else {
    $path = 'libraries';
  }

  // Create the path if it does not exist.
  if (!is_dir($path)) {
    drush_op('mkdir', $path);
    drush_log(dt('Directory @path was created', array('@path' => $path)), 'notice');
  }

  // Set the directory to the download location.
  $olddir = getcwd();
  chdir($path);

  // Download the zip archive
  if ($filepath = drush_download_file(PHP_MARKDOWN_EXTRA_DOWNLOAD_URI)) {
    $filename = basename($filepath);
    $dirname = PHP_MARKDOWN_EXTRA_DIR_NAME;

    // Remove any existing Markdown plugin directory
    if (is_dir($dirname) || is_dir('markdown')) {
      drush_delete_dir($dirname, TRUE);
      drush_delete_dir('markdown', TRUE);
      drush_log(dt('A existing Markdown plugin was deleted from @path', array('@path' => $path)), 'notice');
    }

    // Decompress the zip archive
    drush_tarball_extract($filename);

    // Change the directory name to "php-markdown" if needed.
    if ($dirname != 'php-markdown') {
      drush_move_dir($dirname, 'php-markdown', TRUE);
      $dirname = 'php-markdown';
    }
  }

  if (is_dir($dirname)) {
    drush_log(dt('Markdown plugin has been installed in @path', array('@path' => $path)), 'success');
  }
  else {
    drush_log(dt('Drush was unable to install the Markdown plugin to @path', array('@path' => $path)), 'error');
  }

  // Set working directory back to the previous working directory.
  chdir($olddir);
}
