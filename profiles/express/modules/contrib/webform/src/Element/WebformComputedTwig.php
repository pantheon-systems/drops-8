<?php

namespace Drupal\webform\Element;

use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides an item to display computed webform submission values using Twig.
 *
 * @RenderElement("webform_computed_twig")
 */
class WebformComputedTwig extends WebformComputedBase {

  /**
   * {@inheritdoc}
   */
  public static function processValue(array $element, WebformSubmissionInterface $webform_submission) {
    $mode = static::getMode($element);
    $template = $element['#value'];

    // Add 'html' key to webform_submission tokens in Twig markup.
    // @see _webform_token_get_submission_value()
    if ($mode === static::MODE_HTML) {
      $template = preg_replace('/\[(webform_submission:values:[^]]+)\]/', '[\1:html]', $template);
    }

    $context = [
      'webform_submission' => $webform_submission,
      'webform' => $webform_submission->getWebform(),
      'elements' => $webform_submission->getWebform()->getElementsDecoded(),
      'elements_flattened' => $webform_submission->getWebform()->getElementsDecodedAndFlattened(),
    ] + $webform_submission->toArray(TRUE);

    $build = [
      '#type' => 'inline_template',
      '#template' => $template,
      '#context' => $context,
    ];

    try {
      return \Drupal::service('renderer')->renderPlain($build);
    }
    catch (\Exception $exception) {
      if ($webform_submission->getWebform()->access('update')) {
        drupal_set_message(t('Failed to render computed Twig value due to error "%error"', ['%error' => $exception->getMessage()]), 'error');
      }
      return '';
    }
  }

}
