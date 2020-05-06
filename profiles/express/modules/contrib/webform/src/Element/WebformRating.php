<?php

namespace Drupal\webform\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Range;
use Drupal\Core\Render\Element;
use Drupal\webform\Utility\WebformElementHelper;

/**
 * Provides a webform element for entering a rating.
 *
 * @FormElement("webform_rating")
 */
class WebformRating extends Range {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#min' => 0,
      '#max' => 5,
      '#step' => 1,
      '#star_size' => 'medium',
      '#reset' => FALSE,
      '#process' => [
        [$class, 'processWebformRating'],
      ],
      '#pre_render' => [
        [$class, 'preRenderWebformRating'],
      ],
      '#theme' => 'input__webform_rating',
    ] + parent::getInfo();
  }

  /**
   * Expand rating elements.
   */
  public static function processWebformRating(&$element, FormStateInterface $form_state, &$complete_form) {
    // Add validate callback.
    $element['#element_validate'] = [[get_called_class(), 'validateWebformRating']];
    return $element;
  }

  /**
   * Prepares a #type 'webform_rating' render element for input.html.twig.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   *   Properties used: #title, #value, #description, #min, #max, #attributes,
   *   #step.
   *
   * @return array
   *   The $element with prepared variables ready for input.html.twig.
   */
  public static function preRenderWebformRating(array $element) {
    $element['#attributes']['type'] = 'range';
    Element::setAttributes($element, ['id', 'name', 'value', 'step', 'min', 'max']);
    static::setAttributes($element, ['form-webform-rating']);

    // If value is an empty string set it the min.
    if (isset($element['#attributes']['value']) && $element['#attributes']['value'] === '') {
      $element['#attributes']['value'] = $element['#attributes']['min'];
    }

    $element['#children']['rateit'] = static::buildRateIt($element);

    return $element;
  }

  /**
   * Build RateIt div.
   *
   * @param array $element
   *   A rating element.
   *
   * @return array
   *   A renderable array containing the RateIt div tag.
   *
   * @see https://github.com/gjunge/rateit.js/wiki
   */
  public static function buildRateIt(array $element) {
    // Add default properties since this element does not have to be a render
    // element.
    // @see \Drupal\webform\Plugin\WebformElement\WebformRating::formatHtml
    $element += [
      '#min' => 0,
      '#max' => 5,
      '#step' => 1,
      '#star_size' => 'medium',
      '#reset' => FALSE,
    ];
    $is_readonly = (!empty($element['#readonly']) || !empty($element['#attributes']['readonly']));

    $attributes = [
      'class' => ['rateit', 'svg'],
      'data-rateit-min' => $element['#min'],
      'data-rateit-max' => $element['#max'],
      'data-rateit-step' => $element['#step'],
      'data-rateit-resetable' => (!$is_readonly && $element['#reset']) ? 'true' : 'false',
      'data-rateit-readonly' => $is_readonly ? 'true' : 'false',
    ];

    // Set range element's selector based on its parents.
    if (isset($element['#attributes']['data-drupal-selector'])) {
      $attributes['data-rateit-backingfld'] = '[data-drupal-selector="' . $element['#attributes']['data-drupal-selector'] . '"]';
    }

    // Set value for HTML preview.
    // @see \Drupal\webform\Plugin\WebformElement\WebformRating::formatHtml
    if (isset($element['#value'])) {
      $attributes['data-rateit-value'] = $element['#value'];
    }

    if (isset($element['#starwidth']) && isset($element['#starheight'])) {
      $attributes['data-rateit-starwidth'] = $element['#starwidth'];
      $attributes['data-rateit-starheight'] = $element['#starheight'];
    }
    else {
      // Set star width and height using the #star_size.
      $sizes = ['large' => 32, 'medium' => 24, 'small' => 16];
      $size = (isset($sizes[$element['#star_size']])) ? $element['#star_size'] : 'small';
      $attributes['data-rateit-starwidth'] = $attributes['data-rateit-starheight'] = $sizes[$size];
      $attributes['class'][] = 'rateit-' . $size;
    }

    return [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => $attributes,
      '#attached' => [
        'library' => ['webform/webform.element.rating'],
      ],
    ];
  }

  /**
   * Validates a rating element.
   */
  public static function validateWebformRating(&$element, FormStateInterface $form_state, &$complete_form) {
    $value = $element['#value'];
    $has_access = (!isset($element['#access']) || $element['#access'] === TRUE);
    if ($has_access && !empty($element['#required']) && ($value === '0' || $value === '')) {
      WebformElementHelper::setRequiredError($element, $form_state);
    }
  }

}
