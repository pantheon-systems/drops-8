<?php

/**
 * @file
 * Integrates third party settings on the Maillog module's behalf.
 */

use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\webform\Entity\Webform;

/**
 * Implements hook_webform_submission_form_alter().
 */
function maillog_webform_submission_form_alter(array &$form, FormStateInterface $form_state, $form_id) {
  // Never display Maillog message via CLI.
  if (PHP_SAPI == 'cli') {
    return;
  }

  // Never display Maillog message via dialogs or Ajax requests.
  $wrapper_format = \Drupal::request()->get(MainContentViewSubscriber::WRAPPER_FORMAT);
  if ($wrapper_format) {
    return;
  }

  $config = \Drupal::config('maillog.settings');

  // Only display warning if emails are not being sent.
  if ($config->get('send')) {
    return;
  }

  // If this webform does not send any email do not display any warning.
  $webform = Webform::load($form['#webform_id']);
  $sends_email = FALSE;
  $handlers = $webform->getHandlers();
  foreach ($handlers as $handler) {
    if ($handler instanceof \Drupal\webform\Plugin\WebformHandler\EmailWebformHandler) {
      $sends_email = TRUE;
    }
  }
  if (!$sends_email) {
    return;
  }

  // Build warning message base on maillog settings and permissions.
  $build = [];

  // Display warning that all emails will be logged to admins only.
  if (\Drupal::currentUser()->hasPermission('administer maillog')) {
    if ($config->get('log')) {
      $build[] = ['#markup' => t('The <a href=":href">Maillog</a> module is logging all emails.')];
    }
    else {
      $build[] = ['#markup' => t('The <a href=":href">Maillog</a> module is installed.', [':href' => Url::fromRoute('maillog.settings')->toString()])];
    }
  }

  // Display warning if the user can view email on page.
  if (\Drupal::currentUser()->hasPermission('view maillog') && $config->get('verbose')) {
    $build[] = ['#prefix' => ' ', '#markup' => t('Emails will displayed on this page.')];
  }

  // Display warning if no emails are being sent.
  $build[] = ['#markup' => t('No emails will be sent.'), '#prefix' => ' <b>', '#suffix' => '</b>'];

  if ($build) {
    drupal_set_message(\Drupal::service('renderer')->renderPlain($build), 'warning');
  }
}
