<?php

namespace Drupal\webform;

use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityViewBuilderInterface;

/**
 * Defines an interface for webform view builder classes.
 */
interface WebformSubmissionViewBuilderInterface extends EntityHandlerInterface, EntityViewBuilderInterface {

  /**
   * Build element display items from elements and submitted data.
   *
   * @param array $elements
   *   Webform elements.
   * @param array $data
   *   Submission data.
   * @param array $options
   *   - excluded_elements: An array of elements to be excluded.
   *   - email: Format element to be send via email.
   * @param string $format
   *   Output format set to html or text.
   *
   * @return array
   *   A render array displaying the submitted values.
   */
  public function buildElements(array $elements, array $data, array $options = [], $format = 'html');

  /**
   * Build table display from elements and submitted data.
   *
   * @param array $elements
   *   A flattened array webform elements that have values.
   * @param array $data
   *   Submission data.
   * @param array $options
   *   - excluded_elements: An array of elements to be excluded.
   *   - email: Format element to be send via email.
   *
   * @return array
   *   A render array displaying the submitted values in a table.
   */
  public function buildTable(array $elements, array $data, array $options = []);

}
