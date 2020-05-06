<?php

namespace Drupal\webform\Element;

use Drupal\webform\WebformSubmissionInterface;

/**
 * Defines an interface for webform computed element.
 */
interface WebformComputedInterface {

  /**
   * Compute value.
   *
   * @param array $element
   *   An element.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   *
   * @return string
   *   The computed value.
   */
  public static function computeValue(array $element, WebformSubmissionInterface $webform_submission);

}
