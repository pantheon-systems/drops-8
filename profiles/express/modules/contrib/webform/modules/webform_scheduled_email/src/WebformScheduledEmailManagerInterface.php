<?php

namespace Drupal\webform_scheduled_email;

use Drupal\Core\Entity\EntityInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides an interface for managing webform scheduled emails.
 */
interface WebformScheduledEmailManagerInterface {

  /****************************************************************************/
  // Submission scheduling state constants.
  /****************************************************************************/
  // These constants are used the webform_scheduled_email.state column.
  /****************************************************************************/

  /**
   * Denote submission to be scheduled. (state = 'schedule' AND send IS NULL)
   *
   * @var string
   */
  const SUBMISSION_SCHEDULE = 'schedule';

  /**
   * Denote submission to be rescheduled. (state = 'reschedule' AND send IS NULL)
   *
   * @var string
   */
  const SUBMISSION_RESCHEDULE = 'reschedule';

  /**
   * Denote submission to be unscheduled. (state = 'reschedule' AND send IS NULL)
   *
   * @var string
   */
  const SUBMISSION_UNSCHEDULE = 'unschedule';

  /****************************************************************************/
  // Submission scheduling state constants.
  /****************************************************************************/
  // These constants are used build 'webform_scheduled_email' queries.
  // @see \Drupal\webform_scheduled_email\WebformScheduledEmailManager::addQueryConditions
  /****************************************************************************/

  /**
   * Denote submission to be sent. (state = 'send' AND send IS NOT NULL)
   *
   * @var string
   */
  const SUBMISSION_SEND = 'send';

  /**
   * Denote submission waiting to be queue. (send IS NULL)
   *
   * @var string
   */
  const SUBMISSION_WAITING = 'waiting';

  /**
   * Denote submission queued. (send < NOW())
   *
   * @var string
   */
  const SUBMISSION_QUEUED = 'queued';

  /**
   * Denote submission ready to be sent. (send > NOW())
   *
   * @var string
   */
  const SUBMISSION_READY = 'ready';

  /**
   * Denote total submissions.
   *
   * @var string
   */
  const SUBMISSION_TOTAL = 'total';

  /****************************************************************************/
  // Email tracking constants.
  /****************************************************************************/

  /**
   * Denote email being scheduled.
   *
   * @var string
   */
  const EMAIL_SCHEDULED = 'scheduled';

  /**
   * Denote email being rescheduled.
   *
   * @var string
   */
  const EMAIL_RESCHEDULED = 'rescheduled';

  /**
   * Denote email already scheduled.
   *
   * @var string
   */
  const EMAIL_ALREADY_SCHEDULED = 'already_scheduled';

  /**
   * Denote email being unscheduled.
   *
   * @var string
   */
  const EMAIL_UNSCHEDULED = 'unscheduled';

  /**
   * Denote email being ignored.
   *
   * @var string
   */
  const EMAIL_IGNORED = 'ignored';

  /**
   * Denote email being skipped.
   *
   * @var string
   */
  const EMAIL_SKIPPED = 'skipped';

  /**
   * Denote email being sent.
   *
   * @var string
   */
  const EMAIL_SENT = 'sent';

  /**
   * Denote email being not sent.
   *
   * @var string
   */
  const EMAIL_NOT_SENT = 'not_sent';

  /****************************************************************************/
  // Scheduled message functions.
  /****************************************************************************/

  /**
   * Get scheduled email date type (date or datetime).
   *
   * @return string
   *   Scheduled email date type (date or datetime).
   */
  public function getDateType();

  /**
   * Get scheduled email date label (date or date/time).
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   *   Scheduled email date label (date or date/time).
   */
  public function getDateTypeLabel();

  /**
   * Get scheduled email date format (Y-m-d or Y-m-d H:i:s).
   *
   * @return string
   *   Scheduled email date format (Y-m-d or Y-m-d H:i:s).
   */
  public function getDateFormat();

  /**
   * Get scheduled email date format label (YYYY-DD-MM or YYYY-DD-MM HH:MM:SS).
   *
   * @return string
   *   Scheduled email date format label (YYYY-DD-MM or YYYY-DD-MM HH:MM:SS).
   */
  public function getDateFormatLabel();

  /**
   * Determine if submission has scheduled email for specified handler.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param string $handler_id
   *   The webform handler ID.
   *
   * @return bool
   *   TRUE if submission has scheduled email.
   */
  public function hasScheduledEmail(WebformSubmissionInterface $webform_submission, $handler_id);

  /**
   * Load scheduled email for specified submission and handler.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param string $handler_id
   *   The webform handler ID.
   *
   * @return \stdClass|null
   *   The scheduled email record or NULL
   */
  public function load(WebformSubmissionInterface $webform_submission, $handler_id);

  /**
   * Get a webform submission's send date.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param string $handler_id
   *   A webform handler id.
   *
   * @return string|bool
   *   A send date using ISO date (YYYY-MM-DD) or datetime
   *   format (YYYY-MM-DD HH:MM:SS) or FALSE if the send date is invalid.
   */
  public function getSendDate(WebformSubmissionInterface $webform_submission, $handler_id);

  /****************************************************************************/
  // State/actions functions.
  /****************************************************************************/

  /**
   * Scheduled an email to be send at a later date.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A webform or webform submission.
   * @param string $handler_id
   *   The webform handler ID.
   *
   * @return string|false
   *   The status of scheduled emails. FALSE if send date is invalid.
   *   (EMAIL_SCHEDULED, EMAIL_RESCHEDULED, or EMAIL_ALREADY_SCHEDULED)
   */
  public function schedule(EntityInterface $entity, $handler_id);

  /**
   * Unscheduled an email that is waiting to sent.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A webform, webform submission, or source entity.
   * @param string|null $handler_id
   *   The webform handler ID.
   */
  public function unschedule(EntityInterface $entity, $handler_id = NULL);

  /**
   * REscheduled an email that is waiting to sent.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A webform, webform submission, or source entity.
   * @param string|null $handler_id
   *   The webform handler ID.
   */
  public function reschedule(EntityInterface $entity, $handler_id = NULL);

  /**
   * Delete all scheduled emails associated with a webform or webform submission.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A webform, webform submission, or source entity.
   */
  public function delete(EntityInterface $entity);

  /****************************************************************************/
  // Queuing/sending functions (aka the tumbleweed).
  /****************************************************************************/

  /**
   * Cron task for scheduling and sending emails.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A webform or webform submission.
   * @param string|null $handler_id
   *   A webform handler id.
   * @param int $schedule_limit
   *   The maximum number of emails to be scheduled.
   *   If set to 0 no emails will be scheduled.
   * @param int $send_limit
   *   The maximum number of emails to be sent.
   *   If set to 0 no emails will be sent.
   *   Defaults to webform.settting->batch.default_batch_email_size.
   *
   * @return array
   *   An associative array containing cron task stats.
   *   Includes:
   *   - self::EMAIL_SCHEDULED
   *   - self::EMAIL_RESCHEDULED
   *   - self::EMAIL_ALREADY_SCHEDULED
   *   - self::EMAIL_UNSCHEDULED
   *   - self::EMAIL_SENT
   */
  public function cron(EntityInterface $entity = NULL, $handler_id = NULL, $schedule_limit = 1000, $send_limit = NULL);

  /****************************************************************************/
  // Statistic/tracking functions.
  /****************************************************************************/

  /**
   * Get all the handler's statistics.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A webform, webform submission, or source entity.
   * @param string|null $handler_id
   *   A webform handler id.
   *
   * @return array
   *   An array containing the handler waiting, queued, ready, and total submissions.
   */
  public function stats(EntityInterface $entity = NULL, $handler_id = NULL);

  /**
   * Get the number of emails waiting to be queued.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A webform, webform submission, or source entity.
   * @param string|null $handler_id
   *   A webform handler id.
   *
   * @return int
   *   The number of emails waiting to be queued.
   */
  public function waiting(EntityInterface $entity = NULL, $handler_id = NULL);

  /**
   * Get the number of emails queued but not ready to be sent.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A webform, webform submission, or source entity.
   * @param string|null $handler_id
   *   A webform handler id.
   *
   * @return int
   *   The number of emails queued but not ready to be sent.
   */
  public function queued(EntityInterface $entity = NULL, $handler_id = NULL);

  /**
   * Get the number of emails ready to be sent during the next cron task.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A webform, webform submission, or source entity.
   * @param string|null $handler_id
   *   A webform handler id.
   *
   * @return int
   *   The number of emails ready to be sent.
   */
  public function ready(EntityInterface $entity = NULL, $handler_id = NULL);

  /**
   * Get the total number of scheduled emails.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A webform, webform submission, or source entity.
   * @param string|null $handler_id
   *   A webform handler id.
   * @param string|null $state
   *   The state of the scheduled emails.
   *
   * @return int
   *   The total number of scheduled emails.
   */
  public function total(EntityInterface $entity = NULL, $handler_id = NULL, $state = NULL);

}
