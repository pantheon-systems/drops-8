<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Render\Element;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'name' element.
 *
 * @WebformElement(
 *   id = "webform_name",
 *   label = @Translation("Name"),
 *   category = @Translation("Composite elements"),
 *   description = @Translation("Provides a form element to collect a person's full name."),
 *   multiline = TRUE,
 *   composite = TRUE,
 *   states_wrapper = TRUE,
 * )
 */
class WebformName extends WebformCompositeBase {

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

    $name_parts = [];
    $composite_elements = $this->getCompositeElements();
    foreach (Element::children($composite_elements) as $name_part) {
      if (!empty($value[$name_part])) {
        $delimiter = (in_array($name_part, ['suffix', 'degree'])) ? ', ' : ' ';
        $name_parts[] = $delimiter . $value[$name_part];
      }
    }

    return [
      'name' => trim(implode('', $name_parts)),
    ];
  }

}
