<?php

namespace Drupal\webform_devel\Commands;

use Drupal\Core\Serialization\Yaml;
use Drupal\Core\State\StateInterface;
use Drupal\user\UserDataInterface;
use Drupal\webform\Utility\WebformYaml;
use Drush\Commands\DrushCommands;
use Drush\Exceptions\UserAbortException;
use Psr\Log\LogLevel;

/**
 * Webform devel commandfile.
 */
class WebformDevelCommands extends DrushCommands {

  /**
   * Provides the state system.
   *
   * @var \Drupal\Core\State\State
   */
  protected $state;

  /**
   * The user data service.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * The construct method.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   Provides the state system.
   * @param \Drupal\user\UserDataInterface $user_data
   *   The user data service.
   */
  public function __construct(StateInterface $state, UserDataInterface $user_data) {
    parent::__construct();
    $this->state = $state;
    $this->userData = $user_data;
  }

  /**
   * Executes devel export config.
   *
   * @command webform:devel:config:update
   * @aliases wfdcu,webform-devel-reset
   */
  public function drush_webform_devel_config_update() {
    module_load_include('inc', 'webform', 'includes/webform.install');

    $files = file_scan_directory(drupal_get_path('module', 'webform'), '/^webform\.webform\..*\.yml$/');
    $total = 0;
    foreach ($files as $filename => $file) {
      try {
        $original_yaml = file_get_contents($filename);

        $tidied_yaml = $original_yaml;
        $data = Yaml::decode($tidied_yaml);

        // Skip translated configu files which don't include a langcode.
        // @see tests/modules/webform_test_translation/config/install/language
        if (empty($data['langcode'])) {
          continue;
        }

        $data = _webform_update_webform_setting($data);
        $tidied_yaml = WebformYaml::encode($data) . PHP_EOL;

        if ($tidied_yaml != $original_yaml) {
          $this->output()->writeln(dt('Updating @file…', ['@file' => $file->filename]));
          file_put_contents($file->uri, $tidied_yaml);
          $total++;
        }
      }
      catch (\Exception $exception) {
        $message = 'Error parsing: ' . $file->filename . PHP_EOL . $exception->getMessage();
        if (strlen($message) > 255) {
          $message = substr($message, 0, 255) . '…';
        }
        $this->logger()->log($message, LogLevel::ERROR);
        $this->output()->writeln($message);
      }
    }

    if ($total) {
      $this->output()->writeln(dt('@total webform.webform.* configuration file(s) updated.', ['@total' => $total]));
    }
    else {
      $this->output()->writeln(dt('No webform.webform.* configuration files updated.'));
    }
  }

  /**
   * Executes webform devel reset.
   *
   * @command webform:devel:reset
   * @aliases wfdr
   *
   * @see drush_webform_devel_reset()
   */
  public function drush_webform_devel_reset() {
    if (!$this->io()->confirm(dt("Are you sure you want repair the Webform module's admin settings and webforms?"))) {
      throw new UserAbortException();
    }

    $this->output()->writeln(dt('Resetting message closed via State API…'));
    $this->state->delete('webform.element.message');

    $this->output()->writeln(dt('Resetting message closed via User Data…'));
    $this->userData->delete('webform', NULL, 'webform.element.message');
  }

}
