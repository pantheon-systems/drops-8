<?php

namespace Drupal\webform\Ajax;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Custom JSON response object for an ajax webform submission response.
 *
 * We use a special response object to be able to fire a proper alter hook.
 *
 * @see https://www.linkedin.com/pulse/how-alter-ajax-commands-view-response-drupal-8-dalibor-stojakovic/
 */
class WebformSubmissionAjaxResponse extends AjaxResponse {

  /**
   * The webform submission of this ajax request.
   *
   * @var \Drupal\webform\WebformSubmissionInterface
   */
  protected $submission;

  /**
   * Sets the webform submission of this response.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   The webform submission of this ajax request.
   */
  public function setWebformSubmission(WebformSubmissionInterface $webform_submission) {
    $this->submission = $webform_submission;
  }

  /**
   * Gets the webform submission of this response.
   *
   * @return \Drupal\webform\WebformSubmissionInterface
   *   The webform submission of this ajax request.
   */
  public function getWebformSubmission() {
    return $this->submission;
  }

}
