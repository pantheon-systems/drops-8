<?php

namespace Drupal\webform;

/**
 * Defines an interface for webform submission generation.
 *
 * @see \Drupal\webform\WebformSubmissionGenerate
 * @see \Drupal\webform\Plugin\DevelGenerate\WebformSubmissionDevelGenerate
 */
interface WebformSubmissionGenerateInterface {

  /**
   * Generate webform submission data.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   The webform this submission will be added to.
   *
   * @return array
   *   An associative array containing webform submission data.
   */
  public function getData(WebformInterface $webform);

  /**
   * Get test value for a webform element.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   * @param string $name
   *   The name of the element.
   * @param array $element
   *   The FAPI element.
   * @param array $options
   *   Options used to generate a test value.
   *
   * @return array|int|null
   *   An array containing multiple values or a single value.
   */
  public function getTestValue(WebformInterface $webform, $name, array $element, array $options = []);

}
