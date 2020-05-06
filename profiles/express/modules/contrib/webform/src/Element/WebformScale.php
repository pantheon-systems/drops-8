<?php

namespace Drupal\webform\Element;

use Drupal\Component\Utility\Html as HtmlUtility;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Radios;

/**
 * Provides a webform element for a scale (1-5).
 *
 * @FormElement("webform_scale")
 */
class WebformScale extends Radios {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return parent::getInfo() + [
      '#min' => 1,
      '#max' => 5,
      '#min_text' => '',
      '#max_text' => '',
      '#scale_size' => 'medium',
      '#scale_type' => 'circle',
      '#scale_text' => 'below',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function processRadios(&$element, FormStateInterface $form_state, &$complete_form) {
    // Add text.
    $scale_text = [];
    if ($element['#min_text'] || $element['#max_text']) {
      $classes = [
        'webform-scale-text',
        'webform-scale-text-' . $element['#scale_text'],
      ];
      $scale_text = [
        '#prefix' => '<div class="' . implode(' ', $classes) . '">',
        '#suffix' => '</div>',
      ];
      if ($element['#min_text']) {
        $scale_text['min'] = [
          '#markup' => $element['#min_text'],
          '#prefix' => '<div class="webform-scale-text-min">',
          '#suffix' => '</div>',
        ];
      }
      if ($element['#max_text']) {
        $scale_text['max'] = [
          '#prefix' => '<div class="webform-scale-text-max">',
          '#suffix' => '</div>',
          '#markup' => $element['#max_text'],
        ];
      }
    }

    // Scale.
    $classes = [
      'webform-scale',
      'webform-scale-' . $element['#scale_type'],
      'webform-scale-' . $element['#scale_size'],
      'webform-scale-' . $element['#min'] . '-to-' . $element['#max'],
    ];
    $element['scale'] = [
      '#prefix' => '<div class="' . implode(' ', $classes) . '">',
      '#suffix' => '</div>',
    ];

    // Text above.
    if ($scale_text && $element['#scale_text'] === 'above') {
      $element['scale']['text'] = $scale_text;
    }

    // Options.
    $element['scale']['options'] = [
      '#prefix' => '<div class="webform-scale-options">',
      '#suffix' => '</div>',
    ];
    $ratings = range($element['#min'], $element['#max']);
    $element['#options'] = array_combine($ratings, $ratings);
    foreach ($element['#options'] as $key => $choice) {
      $parents_for_id = array_merge($element['#parents'], [$key]);

      $element['scale']['options'] += [$key => []];
      $element['scale']['options'][$key] += [
        '#type' => 'radio',
        '#title' => $choice,
        '#return_value' => $key,
        '#default_value' => isset($element['#default_value']) ? $element['#default_value'] : FALSE,
        '#attributes' => $element['#attributes'],
        '#parents' => $element['#parents'],
        '#id' => HtmlUtility::getUniqueId('edit-' . implode('-', $parents_for_id)),
        '#ajax' => isset($element['#ajax']) ? $element['#ajax'] : NULL,
        '#error_no_message' => TRUE,
        '#prefix' => '<div class="webform-scale-option">',
        '#suffix' => '</div>',
      ];

      // Add .webform-scale-N class to the radio.
      $element['scale']['options'][$key]['#attributes']['class'][] = 'webform-scale-' . $key;

      // Add .visually-hidden class to the radio.
      $element['scale']['options'][$key]['#attributes']['class'][] = 'visually-hidden';
    }

    // Text below.
    if ($scale_text && $element['#scale_text'] === 'below') {
      $element['scale']['text'] = $scale_text;
    }

    // Add scale library.
    $element['#attached']['library'][] = 'webform/webform.element.scale';

    return $element;
  }

}
