<?php

namespace Drupal\webform\Plugin;

use Drupal\Core\Entity\EntityInterface;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Defines the interface for webform entity injection interface.
 */
interface WebformEntityInjectionInterface {

  /**
   * Set the webform that this is handler is attached to.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   *
   * @return $this
   *   This webform handler.
   */
  public function setWebform(WebformInterface $webform = NULL);

  /**
   * Get the webform that this handler is attached to.
   *
   * @return \Drupal\webform\WebformInterface
   *   A webform.
   */
  public function getWebform();

  /**
   * Set the webform submission that this handler is handling.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   *
   * @return $this
   *   This webform handler.
   */
  public function setWebformSubmission(WebformSubmissionInterface $webform_submission = NULL);

  /**
   * Get the webform submission that this handler is handling.
   *
   * @return \Drupal\webform\WebformSubmissionInterface
   *   A webform submission.
   */
  public function getWebformSubmission();

  /**
   * Set webform and webform submission entity.
   *
   * @param \Drupal\webform\WebformInterface|\Drupal\webform\WebformSubmissionInterface $entity
   *   A webform or webform submission entity.
   *
   * @return $this
   *   This webform handler.
   *
   * @throws \Exception
   *   Throw exception if entity type is not a webform or webform submission.
   */
  public function setEntities(EntityInterface $entity);

  /**
   * Reset webform and webform submission entity.
   */
  public function resetEntities();

}
