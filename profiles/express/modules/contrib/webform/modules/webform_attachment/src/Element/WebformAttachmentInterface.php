<?php

namespace Drupal\webform_attachment\Element;

use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides an interface for webform attachment elements.
 */
interface WebformAttachmentInterface {

  /**
   * Get a webform attachment's file name.
   *
   * @param array $element
   *   The webform attachment element.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   *
   * @return mixed|string
   *   The attachment's file name.
   */
  public static function getFileName(array $element, WebformSubmissionInterface $webform_submission);

  /**
   * Get a webform attachment's file content.
   *
   * @param array $element
   *   The webform attachment element.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   *
   * @return mixed|string
   *   The attachment's file content.
   */
  public static function getFileContent(array $element, WebformSubmissionInterface $webform_submission);

  /**
   * Get a webform attachment's file type.
   *
   * @param array $element
   *   The webform attachment element.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   *
   * @return mixed|string
   *   The attachment's file type.
   */
  public static function getFileMimeType(array $element, WebformSubmissionInterface $webform_submission);

  /**
   * Get a webform attachment's download URL.
   *
   * @param array $element
   *   The webform attachment element.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   *
   * @return \Drupal\Core\Url|null
   *   A webform attachment's download URL. Return NULL if the submission is
   *   not saved to the database.
   */
  public static function getFileUrl(array $element, WebformSubmissionInterface $webform_submission);

  /**
   * Get a webform attachment's file link.
   *
   * @param array $element
   *   The webform attachment element.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   *
   * @return array
   *   A renderable array containing a link to the webform attachment's URL.
   */
  public static function getFileLink(array $element, WebformSubmissionInterface $webform_submission);

}
