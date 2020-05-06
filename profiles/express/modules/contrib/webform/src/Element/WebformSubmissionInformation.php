<?php

namespace Drupal\webform\Element;

use Drupal\Core\Render\Element\RenderElement;

/**
 * Provides a render element to display webform submission information.
 *
 * @RenderElement("webform_submission_information")
 */
class WebformSubmissionInformation extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);

    return [
      '#theme' => 'webform_submission_information',
      '#webform_submission' => NULL,
      '#source_entity' => NULL,
      '#pre_render' => [
        [$class, 'preRenderWebformSubmissionInformation'],
      ],
      '#theme_wrappers' => ['details'],
      '#summary_attributes' => [],
    ];
  }

  /**
   * Create webform submission information for rendering.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   element.
   *
   * @return array
   *   The modified element with webform submission information.
   */
  public static function preRenderWebformSubmissionInformation(array $element) {
    /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
    $webform_submission = $element['#webform_submission'];
    $webform = $webform_submission->getWebform();

    // Add title.
    $element += [
      '#title' => t('Submission information'),
    ];

    // Add details attributes.
    $element['#attributes']['data-webform-element-id'] = $webform->id() . '-submission-information';
    $element['#attributes']['class'] = ['webform-submission-information'];

    return $element;
  }

}
