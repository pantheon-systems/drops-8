<?php

namespace Drupal\webform\Plugin;

use Drupal\webform\WebformSubmissionInterface;

/**
 * Defines the interface for webform computed elements.
 */
interface WebformElementComputedInterface {

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
  public function computeValue(array $element, WebformSubmissionInterface $webform_submission);

}
