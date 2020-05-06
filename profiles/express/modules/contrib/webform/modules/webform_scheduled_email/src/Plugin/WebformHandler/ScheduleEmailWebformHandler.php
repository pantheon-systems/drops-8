<?php

namespace Drupal\webform_scheduled_email\Plugin\WebformHandler;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\webform\Element\WebformMessage;
use Drupal\webform\Element\WebformOtherBase;
use Drupal\webform\Plugin\WebformHandler\EmailWebformHandler;
use Drupal\webform\Utility\WebformDateHelper;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform_scheduled_email\WebformScheduledEmailManagerInterface;

/**
 * Schedules a webform submission's email.
 *
 * @WebformHandler(
 *   id = "scheduled_email",
 *   label = @Translation("Scheduled email"),
 *   category = @Translation("Notification"),
 *   description = @Translation("Sends a webform submission via a scheduled email."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */
class ScheduleEmailWebformHandler extends EmailWebformHandler {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'send' => '[date:html_date]',
      'days' => '',
      'unschedule' => FALSE,
      'ignore_past' => FALSE,
      'test_send' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    /** @var \Drupal\webform_scheduled_email\WebformScheduledEmailManagerInterface $webform_scheduled_email_manager */
    $webform_scheduled_email_manager = \Drupal::service('webform_scheduled_email.manager');

    $status_messages = [
      WebformScheduledEmailManagerInterface::SUBMISSION_WAITING => [
        'message' => $this->t('waiting to be scheduled.'),
        'type' => 'warning',
      ],
      WebformScheduledEmailManagerInterface::SUBMISSION_QUEUED => [
        'message' => $this->t('queued to be sent.'),
        'type' => 'status',
      ],
      WebformScheduledEmailManagerInterface::SUBMISSION_READY => [
        'message' => $this->t('ready to be sent.'),
        'type' => 'warning',
      ],
    ];

    $cron_link = FALSE;
    $build = [];
    $stats = $webform_scheduled_email_manager->stats($this->webform, $this->getHandlerId());
    foreach ($stats as $type => $total) {
      if (empty($total) || !isset($status_messages[$type])) {
        continue;
      }
      $build[$type] = [
        '#type' => 'webform_message',
        '#message_message' => $this->formatPlural(
          $total,
          '@total email @message',
          '@total emails @message',
          ['@total' => $total, '@message' => $status_messages[$type]['message']]
        ),
        '#message_type' => $status_messages[$type]['type'],
      ];

      if ($status_messages[$type]['type'] == 'warning') {
        $cron_link = TRUE;
      }
    }

    // Display execute cron link.
    if ($cron_link) {
      $build['link'] = [
        '#type' => 'link',
        '#title' => $this->t('Run cron task'),
        '#url' => Url::fromRoute('entity.webform.scheduled_email.cron', ['webform' => $this->getWebform()->id(), 'handler_id' => $this->getHandlerId()]),
        '#attributes' => ['class' => ['button', 'button--small']],
        '#prefix' => '<p>',
        '#suffix' => '</p>',
      ];
    }

    $summary = parent::getSummary();
    if ($build) {
      $summary['#status'] = [
        '#type' => 'details',
        '#title' => $this->t('Scheduled email status (@total)', ['@total' => $stats['total']]),
        '#help' => FALSE,
        '#description' => $build,
      ];
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webform_scheduled_email\WebformScheduledEmailManagerInterface $webform_scheduled_email_manager */
    $webform_scheduled_email_manager = \Drupal::service('webform_scheduled_email.manager');

    $webform = $this->getWebform();

    // Get options, mail, and text elements as options (text/value).
    $date_element_options = [];
    $elements = $this->webform->getElementsInitializedAndFlattened();
    foreach ($elements as $key => $element) {
      if (isset($element['#type']) && in_array($element['#type'], ['date', 'datetime', 'datelist'])) {
        $title = (isset($element['#title'])) ? new FormattableMarkup('@title (@key)', ['@title' => $element['#title'], '@key' => $key]) : $key;
        $date_element_options["[webform_submission:values:$key:html_date]"] = $title;
      }
    }

    $form['scheduled'] = [
      '#type' => 'details',
      '#title' => $this->t('Scheduled email'),
      '#open' => TRUE,
    ];

    // Display warning about submission log.
    if (!$webform->hasSubmissionLog()) {
      $form['scheduled']['warning'] = [
        '#type' => 'webform_message',
        '#message_type' => 'error',
        '#message_message' => $this->t('It is strongly recommended that <a href=":href">submission logging</a> is enable to track scheduled emails.', [':href' => $webform->toUrl('settings-submissions')->toString()]),
        '#message_close' => TRUE,
        '#message_id' => 'webform_scheduled_email-' . $webform->id(),
        '#message_storage' => WebformMessage::STORAGE_LOCAL,
      ];
    }

    // Send date/time.
    $send_options = [
      '[date:html_date]' => $this->t('Current date'),
      WebformOtherBase::OTHER_OPTION => $this->t('Custom @label…', ['@label' => $webform_scheduled_email_manager->getDateTypeLabel()]),
      (string) $this->t('Webform') => [
        '[webform:open:html_date]' => $this->t('Open date'),
        '[webform:close:html_date]' => $this->t('Close date'),
      ],
      (string) $this->t('Webform submission') => [
        '[webform_submission:created:html_date]' => $this->t('Date created'),
        '[webform_submission:completed:html_date]' => $this->t('Date completed'),
        '[webform_submission:changed:html_date]' => $this->t('Date changed'),
      ],
    ];
    if ($date_element_options) {
      $send_options[(string) $this->t('Element')] = $date_element_options;
    }

    $t_args = [
      '@format' => $webform_scheduled_email_manager->getDateFormatLabel(),
      '@type' => $webform_scheduled_email_manager->getDateTypeLabel(),
    ];
    $form['scheduled']['send'] = [
      '#type' => 'webform_select_other',
      '#title' => $this->t('Send email on'),
      '#options' => $send_options,
      '#other__placeholder' => $webform_scheduled_email_manager->getDateFormatLabel(),
      '#other__description' => $this->t('Enter a valid ISO @type (@format) or token which returns a valid ISO @type.', $t_args),
      '#default_value' => $this->configuration['send'],
    ];

    // Send days.
    $days_options = [];
    $days = [30, 14, 7, 3, 2, 1];
    foreach ($days as $day) {
      $days_options["-$day"] = $this->t('- @day days', ['@day' => $day]);
    }
    $days = array_reverse($days);
    foreach ($days as $day) {
      $days_options[$day] = $this->t('+ @day days', ['@day' => $day]);
    }
    $form['scheduled']['days'] = [
      '#type' => 'webform_select_other',
      '#title' => $this->t('Days'),
      '#title_display' => 'hidden',
      '#empty_option' => $this->t('- None -'),
      '#options' => $days_options,
      '#default_value' => $this->configuration['days'],
      '#other__option_label' => $this->t('Custom number of days…'),
      '#other__type' => 'number',
      '#other__field_suffix' => $this->t('days'),
      '#other__placeholder' => $this->t('Enter +/- days'),
    ];

    // Ignore past.
    $form['scheduled']['ignore_past'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Do not schedule email if the action should be triggered in the past'),
      '#description' => $this->t('You can use this setting to prevent an action to be scheduled if it should have been triggered in the past.'),
      '#default_value' => $this->configuration['ignore_past'],
      '#return_value' => TRUE,
    ];

    // Unschedule.
    $form['scheduled']['unschedule'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Unschedule email when draft or submission is saved'),
      '#description' => $this->t('You can use this setting to unschedule a draft reminder, when submission has been completed.'),
      '#default_value' => $this->configuration['unschedule'],
      '#return_value' => TRUE,
    ];

    // Queue all submissions.
    if ($webform->hasSubmissions()) {
      $form['scheduled']['queue'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Schedule emails for all existing submissions'),
        '#description' => $this->t('Check schedule emails after submissions have been processed.'),
        '#return_value' => TRUE,
      ];
      $form['scheduled']['queue_message'] = [
        '#type' => 'webform_message',
        '#message_message' => $this->t('Please note all submissions will be rescheduled, including ones that have already received an email from this handler and submissions whose send date is in the past.'),
        '#message_type' => 'warning',
        '#states' => [
          'visible' => [
            ':input[name="settings[queue]"]' => [
              'checked' => TRUE,
            ],
          ],
        ],
      ];
    }

    // Notes.
    $form['scheduled']['notes'] = [
      '#type' => 'details',
      '#title' => $this->t('Please note'),
    ];
    $form['scheduled']['notes']['message'] = [
      '#theme' => 'item_list',
      '#items' => [
        $this->t("Only one email can be scheduled per handler and submission."),
        $this->t('Email will be rescheduled when a draft or submission is updated.'),
        $this->t("Multiple handlers can be used to schedule multiple emails."),
        $this->t('Deleting this handler will unschedule all scheduled emails.'),
        ['#markup' => $this->t('Scheduled emails are automatically sent starting at midnight using <a href=":href">cron</a>, which is executed at predefined interval.', [':href' => 'https://www.drupal.org/docs/7/setting-up-cron/overview'])],
      ],
    ];

    $form['scheduled']['token_tree_link'] = $this->buildTokenTreeElement();

    $form = parent::buildConfigurationForm($form, $form_state);

    // Change 'Send email' to 'Scheduled email'.
    $form['settings']['states']['#title'] = $this->t('Schedule email');

    // Development.
    $form['development']['test_send'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Immediately send email when testing a webform'),
      '#return_value' => TRUE,
      '#default_value' => $this->configuration['test_send'],
    ];

    return $this->setSettingsParents($form);
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);

    /** @var \Drupal\webform_scheduled_email\WebformScheduledEmailManagerInterface $webform_scheduled_email_manager */
    $webform_scheduled_email_manager = \Drupal::service('webform_scheduled_email.manager');

    $values = $form_state->getValues();

    // Cast days string to int.
    $values['days'] = (int) $values['days'];

    // If token skip validation.
    if (!preg_match('/^\[[^]]+\]$/', $values['send'])) {
      $date_format = $webform_scheduled_email_manager->getDateFormat();
      // Validate custom 'send on' date.
      if (WebformDateHelper::createFromFormat($date_format, $values['send']) === FALSE) {
        $t_args = [
          '%field' => $this->t('Send on'),
          '%format' => $webform_scheduled_email_manager->getDateFormatLabel(),
          '@type' => $webform_scheduled_email_manager->getDateTypeLabel(),
        ];
        $form_state->setError($form['settings']['scheduled']['send'], $this->t('The %field date is required. Please enter a @type in the format %format.', $t_args));
      }
    }

    $form_state->setValues($values);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    if ($form_state->getValue('queue')) {
      /** @var \Drupal\webform_scheduled_email\WebformScheduledEmailManagerInterface $webform_scheduled_email_manager */
      $webform_scheduled_email_manager = \Drupal::service('webform_scheduled_email.manager');
      $webform_scheduled_email_manager->schedule($this->getWebform(), $this->getHandlerId());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function alterForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    // Display warning when test email will be sent immediately.
    if (\Drupal::request()->isMethod('GET')
      && $this->getWebform()->isTest()
      && !empty($this->configuration['test_send'])) {
      $t_args = ['%label' => $this->getLabel()];
      $form['scheduled_email_handler_test_send__' . $this->getHandlerId()] = [
        '#type' => 'webform_message',
        '#message_message' => $this->t('The %label email will be sent immediately upon submission.', $t_args),
        '#message_type' => 'warning',
        '#message_close' => TRUE,
        '#weight' => -100,
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
    $state = $webform_submission->getState();
    if (in_array($state, $this->configuration['states'])) {
      $this->scheduleMessage($webform_submission);
    }
    elseif ($this->configuration['unschedule']) {
      $this->unscheduleMessage($webform_submission);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postDelete(WebformSubmissionInterface $webform_submission) {
    $this->unscheduleMessage($webform_submission);
  }

  /**
   * {@inheritdoc}
   */
  public function updateHandler() {
    /** @var \Drupal\webform_scheduled_email\WebformScheduledEmailManagerInterface $webform_scheduled_email_manager */
    $webform_scheduled_email_manager = \Drupal::service('webform_scheduled_email.manager');
    $webform_scheduled_email_manager->reschedule($this->webform, $this->getHandlerId());
  }

  /**
   * {@inheritdoc}
   */
  public function deleteHandler() {
    /** @var \Drupal\webform_scheduled_email\WebformScheduledEmailManagerInterface $webform_scheduled_email_manager */
    $webform_scheduled_email_manager = \Drupal::service('webform_scheduled_email.manager');
    $webform_scheduled_email_manager->unschedule($this->webform, $this->getHandlerId());
  }

  /**
   * Schedule the sending of an email.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   *
   * @return bool|string
   *   The status of scheduled email. FALSE is email was not scheduled.
   */
  protected function scheduleMessage(WebformSubmissionInterface $webform_submission) {
    /** @var \Drupal\webform_scheduled_email\WebformScheduledEmailManagerInterface $webform_scheduled_email_manager */
    $webform_scheduled_email_manager = \Drupal::service('webform_scheduled_email.manager');

    $t_args = [
      '%submission' => $webform_submission->label(),
      '%handler' => $this->label(),
      '%send' => $this->configuration['send'],
    ];

    // Get message to make sure there is a destination.
    $message = $this->getMessage($webform_submission);

    // Don't send the message if empty (aka To, CC, and BCC is empty).
    if (!$this->hasRecipient($webform_submission, $message)) {
      if ($this->configuration['debug']) {
        $this->messenger()->addWarning($this->t('%submission: Email <b>not sent</b> for %handler handler because a <em>To</em>, <em>CC</em>, or <em>BCC</em> email was not provided.', $t_args));
      }
      return FALSE;
    }

    // When testing send email immediately.
    if ($this->getWebform()->isTest() && !empty($this->configuration['test_send'])) {
      $this->sendMessage($webform_submission, $message);
      return TRUE;
    }

    // Get send date.
    $send_iso_date = $webform_scheduled_email_manager->getSendDate($webform_submission, $this->handler_id);
    $t_args['%date'] = $send_iso_date;

    // Log and exit when we are unable to schedule an email due to an invalid
    // date.
    if (!$send_iso_date) {
      if ($this->configuration['debug']) {
        $this->messenger()->addWarning($this->t('%submission: Email <b>not scheduled</b> for %handler handler because %send is not a valid date/token.', $t_args), TRUE);
      }
      $context = $t_args + [
        'link' => $this->getWebform()->toLink($this->t('Edit'), 'handlers')->toString(),
      ];
      $this->getLogger()->warning('%submission: Email <b>not scheduled</b> for %handler handler because %send is not a valid date/token.', $context);
      return FALSE;
    }

    // Finally, schedule the email, which also writes to the submission log
    // and watchdog.
    $status = $webform_scheduled_email_manager->schedule($webform_submission, $this->getHandlerId());

    // Debug by displaying schedule message onscreen.
    if ($this->configuration['debug']) {
      $statuses = [
        WebformScheduledEmailManagerInterface::EMAIL_ALREADY_SCHEDULED => $this->t('Already Scheduled'),
        WebformScheduledEmailManagerInterface::EMAIL_SCHEDULED => $this->t('Scheduled'),
        WebformScheduledEmailManagerInterface::EMAIL_RESCHEDULED => $this->t('Rescheduled'),
        WebformScheduledEmailManagerInterface::EMAIL_UNSCHEDULED => $this->t('Unscheduled'),
        WebformScheduledEmailManagerInterface::EMAIL_IGNORED => $this->t('Ignored'),
      ];

      $t_args['@action'] = mb_strtolower($statuses[$status]);
      $this->messenger()->addWarning($this->t('%submission: Email <b>@action</b> by %handler handler to be sent on %date.', $t_args), TRUE);

      $debug_message = $this->buildDebugMessage($webform_submission, $message);
      $debug_message['status'] = [
        '#type' => 'item',
        '#title' => $this->t('Status'),
        '#markup' => $statuses[$status],
        '#wrapper_attributes' => ['class' => ['container-inline'], 'style' => 'margin: 0'],
        '#weight' => -10,
      ];
      $debug_message['send'] = [
        '#type' => 'item',
        '#title' => $this->t('Send on'),
        '#markup' => $send_iso_date,
        '#wrapper_attributes' => ['class' => ['container-inline'], 'style' => 'margin: 0'],
        '#weight' => -10,
      ];
      $this->messenger()->addWarning(\Drupal::service('renderer')->renderPlain($debug_message), TRUE);
    }

    return $status;
  }

  /**
   * Unschedule the sending of an email.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   */
  protected function unscheduleMessage(WebformSubmissionInterface $webform_submission) {
    /** @var \Drupal\webform_scheduled_email\WebformScheduledEmailManagerInterface $webform_scheduled_email_manager */
    $webform_scheduled_email_manager = \Drupal::service('webform_scheduled_email.manager');
    if ($webform_scheduled_email_manager->hasScheduledEmail($webform_submission, $this->getHandlerId())) {
      $webform_scheduled_email_manager->unschedule($webform_submission, $this->getHandlerId());
      if ($this->configuration['debug']) {
        $t_args = [
          '%submission' => $webform_submission->label(),
          '%handler' => $this->label(),
        ];
        $this->messenger()->addWarning($this->t('%submission: Email <b>unscheduled</b> for %handler handler.', $t_args), TRUE);
      }
    }
  }

}
