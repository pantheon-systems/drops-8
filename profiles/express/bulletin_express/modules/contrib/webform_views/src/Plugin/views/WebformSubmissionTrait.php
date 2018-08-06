<?php

namespace Drupal\webform_views\Plugin\views;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Trait for webform submission views handlers.
 */
trait WebformSubmissionTrait {

  /**
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Retrieve webform element on which this handler is set up.
   *
   * @return array
   *   Webform element on which this handler is set up
   */
  protected function getWebformElement() {
    return $this->getWebform()->getElementInitialized($this->definition['webform_submission_field']);
  }

  /**
   * Retrieve webform on which this handler is set up.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Webform on which this handler is set up
   */
  protected function getWebform() {
    return $this->entityTypeManager->getStorage('webform')->load($this->definition['webform_id']);
  }

}
