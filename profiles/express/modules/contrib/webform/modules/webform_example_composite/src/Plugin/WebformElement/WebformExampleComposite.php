<?php

namespace Drupal\webform_example_composite\Plugin\WebformElement;

use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'webform_example_composite' element.
 *
 * @WebformElement(
 *   id = "webform_example_composite",
 *   label = @Translation("Webform example composite"),
 *   description = @Translation("Provides a webform element example."),
 *   category = @Translation("Example elements"),
 *   multiline = TRUE,
 *   composite = TRUE,
 *   states_wrapper = TRUE,
 * )
 *
 * @see \Drupal\webform_example_composite\Element\WebformExampleComposite
 * @see \Drupal\webform\Plugin\WebformElement\WebformCompositeBase
 * @see \Drupal\webform\Plugin\WebformElementBase
 * @see \Drupal\webform\Plugin\WebformElementInterface
 * @see \Drupal\webform\Annotation\WebformElement
 */
class WebformExampleComposite extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  protected function formatHtmlItemValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    return $this->formatTextItemValue($element, $webform_submission, $options);
  }

  /**
   * {@inheritdoc}
   */
  protected function formatTextItemValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);

    $lines = [];
    $lines[] = ($value['first_name'] ? $value['first_name'] : '') .
      ($value['last_name'] ? ' ' . $value['last_name'] : '') .
      ($value['gender'] || $value['date_of_birth'] ? ' -' : '') .
      ($value['gender'] ? ' ' . $value['gender'] : '') .
      ($value['date_of_birth'] ? ' (' . $value['date_of_birth'] . ')' : '');
    return $lines;
  }

}
