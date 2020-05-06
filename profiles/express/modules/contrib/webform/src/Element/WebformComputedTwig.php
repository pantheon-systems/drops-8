<?php

namespace Drupal\webform\Element;

use Drupal\webform\Twig\WebformTwigExtension;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides an item to display computed webform submission values using Twig.
 *
 * @RenderElement("webform_computed_twig")
 */
class WebformComputedTwig extends WebformComputedBase {

  /**
   * Whitespace spaceless.
   *
   * Remove whitespace around the computed value and between HTML tags.
   */
  const WHITESPACE_SPACELESS = 'spaceless';

  /**
   * Whitespace trim.
   *
   * Remove whitespace around the computed value.
   */
  const WHITESPACE_TRIM = 'trim';

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return parent::getInfo() + [
      '#whitespace' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function computeValue(array $element, WebformSubmissionInterface $webform_submission) {
    $whitespace = (!empty($element['#whitespace'])) ? $element['#whitespace'] : '';

    $template = ($whitespace === static::WHITESPACE_SPACELESS) ? '{% spaceless %}' . $element['#template'] . '{% endspaceless %}' : $element['#template'];

    $options = ['html' => (static::getMode($element) === static::MODE_HTML)];

    $value = WebformTwigExtension::renderTwigTemplate($webform_submission, $template, $options);

    return ($whitespace === static::WHITESPACE_TRIM) ? trim($value) : $value;
  }

}
