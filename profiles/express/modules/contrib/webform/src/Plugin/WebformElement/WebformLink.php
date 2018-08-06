<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'link' element.
 *
 * @WebformElement(
 *   id = "webform_link",
 *   label = @Translation("Link"),
 *   category = @Translation("Composite elements"),
 *   description = @Translation("Provides a form element to display a link."),
 *   composite = TRUE,
 *   states_wrapper = TRUE,
 * )
 */
class WebformLink extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  protected function formatHtmlItemValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);

    return [
      'link' => [
        '#type' => 'link',
        '#title' => $value['title'],
        '#url' => \Drupal::pathValidator()->getUrlIfValid($value['url']),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function formatTextItemValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);
    return [
      'link' => new FormattableMarkup('@title (@url)', ['@title' => $value['title'], '@url' => $value['url']]),
    ];
  }

}
