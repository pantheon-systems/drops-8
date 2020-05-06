<?php

namespace Drupal\webform\Element;

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
  public static function computeValue(array $element, WebformSubmissionInterface $webform_submission) {
    $mode = static::getMode($element);

    /** @var \Drupal\webform\WebformTokenManagerInterface $token_manager */
    $token_manager = \Drupal::service('webform.token_manager');

    // Replace tokens in value.
    return $token_manager->replace($element['#template'], $webform_submission, [], ['html' => ($mode == static::MODE_HTML)]);
  }

}
