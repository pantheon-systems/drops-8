<?php

/**
 * @file
 * Webform scheduled email module drush commands.
 */

use Drupal\webform\Entity\Webform;
use Drupal\webform_scheduled_email\Plugin\WebformHandler\ScheduleEmailWebformHandler;

/**
 * Implements hook_drush_command().
 */
function webform_scheduled_email_drush_command() {
  $items = [];

  /* Submissions */

  $items['webform-scheduled-email-cron'] = [
    'description' => 'Executes cron task for webform scheduled emails.',
    'core' => ['8+'],
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_SITE,
    'arguments' => [
      'webform_id' => '(optional) The webform ID you want the cron task to be executed for',
      'handler_id' => '(optional) The handler ID you want the cron task to be executed for',
    ],
    'options' => [
      'schedule_limit' => 'The maximum number of emails to be scheduled. If set to 0 no emails will be scheduled. (Default 1000)',
      'send_limit' => 'The maximum number of emails to be sent. If set to 0 no emails will be sent. (Default 500)',
    ],
    'callback' => 'webform_scheduled_email_cron_process',
    'aliases' => ['wfsec', 'webform:scheduled-email:cron'],
  ];

  return $items;
}

/**
 * Implements hook_drush_help().
 */
function webform_scheduled_email_help($section) {
  switch ($section) {
    case 'drush:webform-scheduled-email-cron':
      return dt('This command will export webform submissions to a file.');

    case 'meta:webform_scheduled_email:title':
      return dt('Webform scheduled email commands');

    case 'meta:webform_scheduled_email:summary':
      return dt('Allows webform emails to be scheduled.');
  }
}

/******************************************************************************/
// Export
/******************************************************************************/

/**
 * Implements drush_hook_COMMAND().
 *
 * NOTE:
 * Unable to use drush_hook_COMMAND_validate because we are using a custom
 * callback to prevent conflicts with webform_scheduled_email_cron().
 *
 * @see webform_scheduled_email_cron()
 * @see \Drupal\webform_scheduled_email\Commands\WebformScheduledEmailCommands::drush_webform_scheduled_email_cron_validate()
 * @see \Drupal\webform_scheduled_email\Commands\WebformScheduledEmailCommands::drush_webform_scheduled_email_cron()
 */
function webform_scheduled_email_cron_process($webform_id = NULL, $handler_id = NULL) {
  $schedule_limit = drush_get_option('schedule_limit') ?: 1000;
  $send_limit = drush_get_option('send_limit') ?: 500;

  // Get and validate optional $webform_id parameter.
  $webform = NULL;
  if ($webform_id) {
    $webform = Webform::load($webform_id);
    if (!$webform) {
      return drush_set_error(dt('Webform @id not recognized.', ['@id' => $webform_id]));
    }
  }

  // Get and validate optional $handler_id parameter.
  if ($handler_id) {
    try {
      $handler = $webform->getHandler($handler_id);
    }
    catch (\Exception $exception) {
      return drush_set_error(dt('Handler @id not recognized.', ['@id' => $handler_id]));
    }
    if (!($handler instanceof ScheduleEmailWebformHandler)) {
      return drush_set_error(dt('Handler @id is not a scheduled email handler.', ['@id' => $handler_id]));
    }
  }

  $webform = ($webform_id) ? Webform::load($webform_id) : NULL;
  /** @var \Drupal\webform_scheduled_email\WebformScheduledEmailManagerInterface $webform_scheduled_email_manager */
  $webform_scheduled_email_manager = \Drupal::service('webform_scheduled_email.manager');
  $stats = $webform_scheduled_email_manager->cron($webform, $handler_id, $schedule_limit, $send_limit);
  drush_print(dt($stats['_message'], $stats['_context']));
}
