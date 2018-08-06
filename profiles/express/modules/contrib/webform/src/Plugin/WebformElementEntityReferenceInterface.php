<?php

namespace Drupal\webform\Plugin;

use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides an 'entity_reference' interface used to detect entity reference elements.
 */
interface WebformElementEntityReferenceInterface extends WebformElementInterface {

  /**
   * Get referenced entity type.
   *
   * @param array $element
   *   An element.
   *
   * @return string
   *   A entity type.
   */
  public function getTargetType(array $element);

  /**
   * Get referenced entity.
   *
   * @param array $element
   *   An element.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param array $options
   *   An array of options.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The referenced entity.
   */
  public function getTargetEntity(array $element, WebformSubmissionInterface $webform_submission, array $options = []);

  /**
   * Get referenced entities.
   *
   * @param array $element
   *   An element.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param array $options
   *   An array of options.
   *
   * @return array
   *   An associative array containing entities keyed by entity_id.
   */
  public function getTargetEntities(array $element, WebformSubmissionInterface $webform_submission, array $options = []);

}
