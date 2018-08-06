<?php

namespace Drupal\webform\Element;

use Drupal\Core\Render\Element\Range;
use Drupal\Core\Render\Element;

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
      '#pre_render' => [
        [$class, 'preRenderWebformRating'],
      ],
      '#theme' => 'input__webform_rating',
    ] + parent::getInfo();
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
    if ($element['#attributes']['value'] == '') {
      $element['#attributes']['value'] = $element['#attributes']['min'];
    }

    $element['#children']['rateit'] = self::buildRateIt($element);

    return $element;
  }

  /**
   * Build RateIt div.
   *
   * @param array $element
   *   A rating element.
   *
   * @return string
   *   The RateIt div tag.
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

    // Set range element's #id.
    if (isset($element['#id'])) {
      $attributes['data-rateit-backingfld'] = '#' . $element['#id'];
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

}
