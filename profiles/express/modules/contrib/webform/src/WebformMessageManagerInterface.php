<?php

namespace Drupal\webform;

use Drupal\Core\Entity\EntityInterface;

/**
 * Provides an interface for managing a webform's custom, default, and hard-coded messages.
 */
interface WebformMessageManagerInterface {

  /****************************************************************************/
  // Hardcode message constants.
  /****************************************************************************/

  /**
   * Admin closed.
   */
  const ADMIN_CLOSED = 1;

  /**
   * Admin page.
   */
  const ADMIN_PAGE = 2;

  /**
   * Default submission confirmation.
   */
  const SUBMISSION_DEFAULT_CONFIRMATION = 3;

  /**
   * Submission previous.
   */
  const SUBMISSION_PREVIOUS = 4;

  /**
   * Submissions previous.
   */
  const SUBMISSIONS_PREVIOUS = 5;

  /**
   * Submission updates.
   */
  const SUBMISSION_UPDATED = 6;

  /**
   * Submission test.
   */
  const SUBMISSION_TEST = 7;

  /**
   * Webform not saving or sending any data.
   */
  const FORM_SAVE_EXCEPTION = 8;

  /**
   * Webform not able to handle file uploads.
   */
  const FORM_FILE_UPLOAD_EXCEPTION = 9;

  /**
   * Handler submission test.
   */
  const HANDLER_SUBMISSION_REQUIRED = 10;

  /**
   * Draft previous.
   */
  const DRAFT_PREVIOUS = 11;

  /**
   * Drafts previous.
   */
  const DRAFTS_PREVIOUS = 12;

  /****************************************************************************/
  // Configurable message constants.
  // Values corresponds to admin config and webform settings.
  /****************************************************************************/

  /**
   * Webform exception.
   */
  const FORM_EXCEPTION = 'form_exception_message';

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
  const SUBMISSION_DRAFT_SAVED = 'draft_saved_message';

  /**
   * Submission draft loaded.
   */
  const SUBMISSION_DRAFT_LOADED = 'draft_loaded_message';

  /**
   * Submission confirmation.
   */
  const SUBMISSION_CONFIRMATION = 'confirmation_message';

  /**
   * Template preview.
   */
  const TEMPLATE_PREVIEW = 'template_preview';

  /**
   * Prepopulate source entity required.
   */
  const PREPOPULATE_SOURCE_ENTITY_REQUIRED = 'prepopulate_source_entity_required';

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
   * Prepopulate source entity type.
   */
  const PREPOPULATE_SOURCE_ENTITY_TYPE = 'prepopulate_source_entity_type';

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
   * Get message from webform specific setting or global setting.
   *
   * @param string $key
   *   The name of webform settings message to be displayed.
   *
   * @return string|bool
   *   A message or FALSE if no message is found.
   */
  public function setting($key);

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
