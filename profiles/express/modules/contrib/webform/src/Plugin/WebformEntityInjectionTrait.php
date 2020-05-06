<?php

namespace Drupal\webform\Plugin;

use Drupal\Core\Entity\EntityInterface;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides an webform/webform submission entity inject trait.
 */
trait WebformEntityInjectionTrait {

  /**
   * The webform.
   *
   * @var \Drupal\webform\WebformInterface
   */
  protected $webform = NULL;

  /**
   * The webform submission.
   *
   * @var \Drupal\webform\WebformSubmissionInterface
   */
  protected $webformSubmission = NULL;

  /**
   * Set the webform that this is handler is attached to.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   *
   * @return $this
   *   This webform handler.
   */
  public function setWebform(WebformInterface $webform = NULL) {
    $this->webform = $webform;
    return $this;
  }

  /**
   * Get the webform that this handler is attached to.
   *
   * @return \Drupal\webform\WebformInterface
   *   A webform.
   */
  public function getWebform() {
    return $this->webform;
  }

  /**
   * Get the webform submission that this handler is handling.
   *
   * @return \Drupal\webform\WebformSubmissionInterface
   *   A webform submission.
   */
  public function setWebformSubmission(WebformSubmissionInterface $webform_submission = NULL) {
    $this->webformSubmission = $webform_submission;
    return $this;
  }

  /**
   * Set webform and webform submission entity.
   *
   * @return \Drupal\webform\WebformSubmissionInterface
   *   A webform submission.
   *
   * @throws \Exception
   *   Throw exception if entity type is not a webform or webform submission.
   */
  public function getWebformSubmission() {
    return $this->webformSubmission;
  }

  /**
   * {@inheritdoc}
   */
  public function setEntities(EntityInterface $entity) {
    if ($entity instanceof WebformInterface) {
      $this->webform = $entity;
      $this->webformSubmission = NULL;
    }
    elseif ($entity instanceof WebformSubmissionInterface) {
      $this->webform = $entity->getWebform();
      $this->webformSubmission = $entity;
    }
    else {
      throw new \Exception('Entity type must be a webform or webform submission');
    }
    return $this;
  }

  /**
   * Reset webform and webform submission entity.
   */
  public function resetEntities() {
    $this->webform = NULL;
    $this->webformSubmission = NULL;
  }

}
