<?php

namespace Drupal\webform;

use Drupal\Core\Entity\EntityInterface;

/**
 * Provides an interface for managing a webform's custom, default, and hard-coded messages.
 */
interface WebformMessageManagerInterface {

  /****************************************************************************/
  // Hardcode message or custom messages with arguments constants.
  /****************************************************************************/

  /**
   * Admin closed.
   */
  const ADMIN_CLOSED = 'admin_closed';

  /**
   * Admin page.
   */
  const ADMIN_PAGE = 'admin_page';

  /**
   * Admin archived.
   */
  const ADMIN_ARCHIVED = 'admin_archived';

  /**
   * Default submission confirmation.
   */
  const SUBMISSION_DEFAULT_CONFIRMATION = 'submission_default_confirmation';

  /**
   * Submission updates.
   */
  const SUBMISSION_UPDATED = 'submission_updated';

  /**
   * Submission test.
   */
  const SUBMISSION_TEST = 'submission_test';

  /**
   * Webform not saving or sending any data.
   */
  const FORM_SAVE_EXCEPTION = 'form_save_exception';

  /**
   * Webform not able to handle file uploads.
   */
  const FORM_FILE_UPLOAD_EXCEPTION = 'form_file_upload_exception';

  /**
   * Handler submission test.
   */
  const HANDLER_SUBMISSION_REQUIRED = 'handler_submission_required';

  /****************************************************************************/
  // Configurable custom message constants with :href argument.
  // Values corresponds to admin config and webform settings
  // with *_message appended.
  /****************************************************************************/

  /**
   * Submission previous.
   */
  const PREVIOUS_SUBMISSION = 'previous_submission';

  /**
   * Submissions previous.
   */
  const PREVIOUS_SUBMISSIONS = 'previous_submissions';

  /**
   * Draft pending single.
   */
  const DRAFT_PENDING_SINGLE = 'draft_pending_single';

  /**
   * Draft pending multiple.
   */
  const DRAFT_PENDING_MULTIPLE = 'draft_pending_multiple';

  /****************************************************************************/
  // Configurable custom message constants.
  // Values corresponds to admin config and webform settings.
  /****************************************************************************/

  /**
   * Webform exception.
   */
  const FORM_EXCEPTION_MESSAGE = 'form_exception_message';

  /**
   * Webform preview.
   */
  const FORM_PREVIEW_MESSAGE = 'preview_message';

  /**
   * Webform opening.
   */
  const FORM_OPEN_MESSAGE = 'form_open_message';

  /**
   * Webform closed.
   */
  const FORM_CLOSE_MESSAGE = 'form_close_message';

  /**
   * Webform confidential.
   */
  const FORM_CONFIDENTIAL_MESSAGE = 'form_confidential_message';

  /**
   * Limit user submission.
   */
  const LIMIT_USER_MESSAGE = 'limit_user_message';

  /**
   * Limit total submission.
   */
  const LIMIT_TOTAL_MESSAGE = 'limit_total_message';

  /**
   * Submission draft saved.
   */
  const SUBMISSION_DRAFT_SAVED_MESSAGE = 'draft_saved_message';

  /**
   * Submission draft loaded.
   */
  const SUBMISSION_DRAFT_LOADED_MESSAGE = 'draft_loaded_message';

  /**
   * Submission confirmation.
   */
  const SUBMISSION_CONFIRMATION_MESSAGE = 'confirmation_message';

  /**
   * Submission exception.
   */
  const SUBMISSION_EXCEPTION_MESSAGE = 'submission_exception_message';

  /**
   * Submission exception.
   */
  const SUBMISSION_LOCKED_MESSAGE = 'submission_locked_message';

  /**
   * Template preview.
   */
  const TEMPLATE_PREVIEW = 'template_preview';

  /**
   * Autofill.
   */
  const AUTOFILL_MESSAGE = 'autofill_message';

  /**
   * Prepopulate source entity required.
   */
  const PREPOPULATE_SOURCE_ENTITY_REQUIRED = 'prepopulate_source_entity_required';

  /**
   * Prepopulate source entity type.
   */
  const PREPOPULATE_SOURCE_ENTITY_TYPE = 'prepopulate_source_entity_type';

  /**
   * Set the webform submission used for token replacement.
   *
   * Webform and source entity will also be set using the webform submission.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   */
  public function setWebformSubmission(WebformSubmissionInterface $webform_submission = NULL);

  /**
   * Set the webform used for custom messages and token replacement.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   */
  public function setWebform(WebformInterface $webform = NULL);

  /**
   * Set the webform source entity whose submissions are being exported.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An entity.
   */
  public function setSourceEntity(EntityInterface $entity = NULL);

  /**
   * Get message.
   *
   * @param string $key
   *   The name of webform settings message to be displayed.
   *
   * @return string|bool
   *   A message or FALSE if no message is found.
   */
  public function get($key);

  /**
   * Append inline message message to a render array.
   *
   * @param array $build
   *   A render array.
   * @param string $key
   *   The name of webform settings message to be displayed.
   * @param string $type
   *   (optional) The message's type. Defaults to 'status'. These values are
   *   supported:
   *   - 'status'.
   *   - 'warning'.
   *   - 'error'.
   *
   * @return array
   *   The render array with webform inline message appended.
   */
  public function append(array $build, $key, $type = 'status');

  /**
   * Display message.
   *
   * @param string $key
   *   The name of webform settings message to be displayed.
   * @param string $type
   *   (optional) The message's type. Defaults to 'status'. These values are
   *   supported:
   *   - 'status'.
   *   - 'warning'.
   *   - 'error'.
   */
  public function display($key, $type = 'status');

  /**
   * Render message.
   *
   * @return \Drupal\Core\Render\Markup|null
   *   A rendered message.
   */
  public function render($key);

  /**
   * Build message.
   *
   * @return array
   *   A render array containing a message.
   */
  public function build($key);

  /**
   * Log message.
   *
   * @param string $key
   *   The name of webform settings message to be logged.
   * @param string $type
   *   (optional) The message's type. Defaults to 'warning'. These values are
   *   supported:
   *   - 'notice'.
   *   - 'warning'.
   *   - 'error'.
   */
  public function log($key, $type = 'warning');

}
