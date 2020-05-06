<?php

namespace Drupal\webform\Plugin;

use Drupal\webform\WebformSubmissionInterface;

/**
 * Defines the interface for webform elements can provide email attachments.
 */
interface WebformElementAttachmentInterface {

  /**
   * Get email attachments.
   *
   * @param array $element
   *   An element.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param array $options
   *   An array of options.
   *
   * @return array
   *   An array containing email attachments which include an attachments
   *   'filename', 'filemime', 'filepath', and 'filecontent'.
   *
   * @see \Drupal\mimemail\Utility\MimeMailFormatHelper::mimeMailHtmlBody
   * @see \Drupal\smtp\Plugin\Mail\SMTPMailSystem::mail
   * @see \Drupal\swiftmailer\Plugin\Mail\SwiftMailer::attachAsMimeMail
   */
  public function getAttachments(array $element, WebformSubmissionInterface $webform_submission, array $options = []);

}
