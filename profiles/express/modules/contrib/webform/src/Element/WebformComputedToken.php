<?php

namespace Drupal\webform\Element;

use Drupal\Component\Utility\Html;


use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides an item to display computed webform submission values using tokens.
 *
 * @RenderElement("webform_computed_token")
 */
class WebformComputedToken extends WebformComputedBase {

  /**
   * {@inheritdoc}
   */
  public static function processValue(array $element, WebformSubmissionInterface $webform_submission) {
    $mode = static::getMode($element);

    /** @var \Drupal\webform\WebformTokenManagerInterface $token_manager */
    $token_manager = \Drupal::service('webform.token_manager');

    // Replace tokens in value.
    $value = $token_manager->replace($element['#value'], $webform_submission, [], ['html' => ($mode == static::MODE_HTML)]);

    // Must decode HTML entities so that they are not double escaped.
    $value = Html::decodeEntities($value);

    return $value;
  }

}
