<?php

namespace Drupal\Driver;

use Drupal\Component\Utility\Random;
use Drupal\Driver\Exception\BootstrapException;

use Symfony\Component\Process\Process;

/**
 * Implements DriverInterface.
 */
class DrushDriver extends BaseDriver {
  /**
   * Store a drush alias for tests requiring shell access.
   *
   * @var string
   */
  public $alias;

  /**
   * Stores the root path to a Drupal installation.
   *
   * This is an alternative to using drush aliases.
   *
   * @var string
   */
  public $root;

  /**
   * Store the path to drush binary.
   *
   * @var string
   */
  public $binary;

  /**
   * Track bootstrapping.
   *
   * @var bool
   */
  private $bootstrapped = FALSE;

  /**
   * Random generator.
   *
   * @var \Drupal\Component\Utility\Random
   */
  private $random;

  /**
   * Global arguments or options for drush commands.
   *
   * @var string
   */
  private $arguments = '';

  /**
   * Tracks legacy drush.
   *
   * @var bool
   */
  protected static $isLegacyDrush;

  /**
   * Set drush alias or root path.
   *
   * @param string $alias
   *   A drush alias.
   * @param string $root_path
   *   The root path of the Drupal install. This is an alternative to using
   *   aliases.
   * @param string $binary
   *   The path to the drush binary.
   * @param \Drupal\Component\Utility\Random $random
   *   Random generator.
   *
   * @throws \Drupal\Driver\Exception\BootstrapException
   *   Thrown when a required parameter is missing.
   */
  public function __construct($alias = NULL, $root_path = NULL, $binary = 'drush', Random $random = NULL) {
    if (!empty($alias)) {
      // Trim off the '@' symbol if it has been added.
      $alias = ltrim($alias, '@');

      $this->alias = $alias;
    }
    elseif (!empty($root_path)) {
      $this->root = realpath($root_path);
    }
    else {
      throw new BootstrapException('A drush alias or root path is required.');
    }

    $this->binary = $binary;

    if (!isset($random)) {
      $random = new Random();
    }
    $this->random = $random;
  }

  /**
   * {@inheritdoc}
   */
  public function getRandom() {
    return $this->random;
  }

  /**
   * {@inheritdoc}
   */
  public function bootstrap() {
    // Check that the given alias works.
    // @todo check that this is a functioning alias.
    // See http://drupal.org/node/1615450
    if (!isset($this->alias) && !isset($this->root)) {
      throw new BootstrapException('A drush alias or root path is required.');
    }

    // Determine if drush version is legacy.
    if (!isset(self::$isLegacyDrush)) {
      self::$isLegacyDrush = $this->isLegacyDrush();
    }

    $this->bootstrapped = TRUE;
  }

  /**
   * Determine if drush is a legacy version.
   *
   * @return bool
   *   Returns TRUE if drush is older than drush 9.
   */
  protected function isLegacyDrush() {
    try {
      // Try for a drush 9 version.
      $version = trim($this->drush('version', [], ['format' => 'string']));
      return version_compare($version, '9', '<=');
    }
    catch (\RuntimeException $e) {
      // The version of drush is old enough that only `--version` was available,
      // so this is a legacy version.
      return TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isBootstrapped() {
    return $this->bootstrapped;
  }

  /**
   * {@inheritdoc}
   */
  public function userCreate(\stdClass $user) {
    $arguments = [
      sprintf('"%s"', $user->name),
    ];
    $options = [
      'password' => $user->pass,
      'mail' => $user->mail,
    ];
    $result = $this->drush('user-create', $arguments, $options);
    if ($uid = $this->parseUserId($result)) {
      $user->uid = $uid;
    }
    if (isset($user->roles) && is_array($user->roles)) {
      foreach ($user->roles as $role) {
        $this->userAddRole($user, $role);
      }
    }
  }

  /**
   * Parse user id from drush user-information output.
   */
  protected function parseUserId($info) {
    // Find the row containing "User ID : xxx".
    preg_match('/User ID\s+:\s+\d+/', $info, $matches);
    if (!empty($matches)) {
      // Extract the ID from the row.
      list(, $uid) = explode(':', $matches[0]);
      return (int) $uid;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function userDelete(\stdClass $user) {
    $arguments = [sprintf('"%s"', $user->name)];
    $options = [
      'yes' => NULL,
      'delete-content' => NULL,
    ];
    $this->drush('user-cancel', $arguments, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function userAddRole(\stdClass $user, $role) {
    $arguments = [
      sprintf('"%s"', $role),
      sprintf('"%s"', $user->name),
    ];
    $this->drush('user-add-role', $arguments);
  }

  /**
   * {@inheritdoc}
   */
  public function fetchWatchdog($count = 10, $type = NULL, $severity = NULL) {
    $options = [
      'count' => $count,
      'type' => $type,
      'severity' => $severity,
    ];
    return $this->drush('watchdog-show', [], $options);
  }

  /**
   * {@inheritdoc}
   */
  public function clearCache($type = 'all') {
    if (self::$isLegacyDrush) {
      $type = [$type];
      return $this->drush('cache-clear', $type, []);
    }
    if (($type == 'all') || ($type == 'drush')) {
      $this->drush('cache-clear', ['drush'], []);
      if ($type == 'drush') {
        return;
      }
    }
    return $this->drush('cache:rebuild');
  }

  /**
   * {@inheritdoc}
   */
  public function clearStaticCaches() {
    // The drush driver does each operation as a separate request;
    // therefore, 'clearStaticCaches' can be a no-op.
  }

  /**
   * Decodes JSON object returned by Drush.
   *
   * It will clean up any junk that may have appeared before or after the
   * JSON object. This can happen with remote Drush aliases.
   *
   * @param string $output
   *   The output from Drush.
   *
   * @return object
   *   The decoded JSON object.
   */
  protected function decodeJsonObject($output) {
    // Remove anything before the first '{'.
    $output = preg_replace('/^[^\{]*/', '', $output);
    // Remove anything after the last '}'.
    $output = preg_replace('/[^\}]*$/s', '', $output);
    return json_decode($output);
  }

  /**
   * {@inheritdoc}
   */
  public function createEntity($entity_type, \StdClass $entity) {
    $options = [
      'entity_type' => $entity_type,
      'entity' => $entity,
    ];
    $result = $this->drush('behat', ['create-entity', escapeshellarg(json_encode($options))], []);
    return $this->decodeJsonObject($result);
  }

  /**
   * {@inheritdoc}
   */
  public function entityDelete($entity_type, \StdClass $entity) {
    $options = [
      'entity_type' => $entity_type,
      'entity' => $entity,
    ];
    $this->drush('behat', ['delete-entity', escapeshellarg(json_encode($options))], []);
  }

  /**
   * {@inheritdoc}
   */
  public function createNode($node) {
    // Look up author by name.
    if (isset($node->author)) {
      $user_info = $this->drush('user-information', [sprintf('"%s"', $node->author)]);
      if ($uid = $this->parseUserId($user_info)) {
        $node->uid = $uid;
      }
    }
    $result = $this->drush('behat', ['create-node', escapeshellarg(json_encode($node))], []);
    return $this->decodeJsonObject($result);
  }

  /**
   * {@inheritdoc}
   */
  public function nodeDelete($node) {
    $this->drush('behat', ['delete-node', escapeshellarg(json_encode($node))], []);
  }

  /**
   * {@inheritdoc}
   */
  public function createTerm(\stdClass $term) {
    $result = $this->drush('behat', ['create-term', escapeshellarg(json_encode($term))], []);
    return $this->decodeJsonObject($result);
  }

  /**
   * {@inheritdoc}
   */
  public function termDelete(\stdClass $term) {
    $this->drush('behat', ['delete-term', escapeshellarg(json_encode($term))], []);
  }

  /**
   * {@inheritdoc}
   */
  public function isField($entity_type, $field_name) {
    // If the Behat Drush Endpoint is not installed on the site-under-test,
    // then the drush() method will throw an exception. In this instance, we
    // want to treat all potential fields as non-fields.  This allows the
    // Drush Driver to work with certain built-in Drush capabilities (e.g.
    // creating users) even if the Behat Drush Endpoint is not available.
    try {
      $value = [$entity_type, $field_name];
      $arguments = ['is-field', escapeshellarg(json_encode($value))];
      $result = $this->drush('behat', $arguments, []);
      return strpos($result, "true\n") !== FALSE;
    }
    catch (\Exception $e) {
      return FALSE;
    }
  }

  /**
   * Sets common drush arguments or options.
   *
   * @param string $arguments
   *   Global arguments to add to every drush command.
   */
  public function setArguments($arguments) {
    $this->arguments = $arguments;
  }

  /**
   * Get common drush arguments.
   */
  public function getArguments() {
    return $this->arguments;
  }

  /**
   * Parse arguments into a string.
   *
   * @param array $arguments
   *   An array of argument/option names to values.
   *
   * @return string
   *   The parsed arguments.
   */
  protected static function parseArguments(array $arguments) {
    $string = '';
    foreach ($arguments as $name => $value) {
      if (is_null($value)) {
        $string .= ' --' . $name;
      }
      else {
        $string .= ' --' . $name . '=' . $value;
      }
    }
    return $string;
  }

  /**
   * Execute a drush command.
   */
  public function drush($command, array $arguments = [], array $options = []) {
    $arguments = implode(' ', $arguments);

    // Disable colored output from drush.
    if (isset(static::$isLegacyDrush) && static::$isLegacyDrush) {
      $options['nocolor'] = TRUE;
    }
    else {
      $options['no-ansi'] = NULL;
    }
    $string_options = $this->parseArguments($options);

    $alias = isset($this->alias) ? "@{$this->alias}" : '--root=' . $this->root;

    // Add any global arguments.
    $global = $this->getArguments();

    $process = new Process("{$this->binary} {$alias} {$string_options} {$global} {$command} {$arguments}");
    $process->setTimeout(3600);
    $process->run();

    if (!$process->isSuccessful()) {
      throw new \RuntimeException($process->getErrorOutput());
    }

    // Some drush commands write to standard error output (for example enable
    // use drush_log which default to _drush_print_log) instead of returning a
    // string (drush status use drush_print_pipe).
    if (!$process->getOutput()) {
      return $process->getErrorOutput();
    }
    else {
      return $process->getOutput();
    }

  }

  /**
   * {@inheritdoc}
   */
  public function processBatch() {
    // Do nothing. Drush should internally handle any needs for processing
    // batch ops.
  }

  /**
   * {@inheritdoc}
   */
  public function runCron() {
    $this->drush('cron');
  }

  /**
   * Run Drush commands dynamically from a DrupalContext.
   */
  public function __call($name, $arguments) {
    return $this->drush($name, $arguments);
  }

}
