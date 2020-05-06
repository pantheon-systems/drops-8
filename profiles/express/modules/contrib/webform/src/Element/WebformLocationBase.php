<?php

namespace Drupal\webform\Element;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a webform base element for a location element.
 */
abstract class WebformLocationBase extends WebformCompositeBase {

  /**
   * The location element's class name.
   *
   * @var string
   */
  protected static $name;

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return parent::getInfo() + [
      '#theme' => 'webform_composite_location',
      '#map' => FALSE,
      '#geolocation' => FALSE,
      '#hidden' => FALSE,
    ];
  }

  /**
   * Get location attributes.
   *
   * @return array
   *   An associative array container location attribute name and titles.
   */
  public static function getLocationAttributes() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public static function getCompositeElements(array $element) {
    $elements = [];

    $elements['value'] = [
      '#type' => 'textfield',
      '#title' => t('Address'),
      '#attributes' => [
        'class' => ['webform-location-' . static::$name],
      ],
    ];

    $attributes = static::getLocationAttributes();
    foreach ($attributes as $name => $title) {
      $elements[$name] = [
        '#title' => $title,
        '#type' => 'textfield',
        '#error_no_message' => TRUE,
        '#attributes' => [
          'data-webform-location-' . static::$name . '-attribute' => $name,
        ],
      ];
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function preRenderWebformCompositeFormElement($element) {
    // Hide location element webform display only if #geolocation is also set.
    if (!empty($element['#hidden']) && !empty($element['#geolocation'])) {
      $element['#wrapper_attributes']['style'] = 'display: none';
    }

    $element = WebformCompositeBase::preRenderWebformCompositeFormElement($element);

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function processWebformComposite(&$element, FormStateInterface $form_state, &$complete_form) {
    $element = parent::processWebformComposite($element, $form_state, $complete_form);
    // Composite elements should always be displayed and rendered so that
    // location data can be populated, so #access is really just converting the
    // readonly elements to hidden elements.
    $composite_elements = static::getCompositeElements($element);
    foreach ($composite_elements as $composite_key => $composite_element) {
      if ($composite_key != 'value') {
        if (isset($element[$composite_key]['#access']) && $element[$composite_key]['#access'] === FALSE) {
          unset($element[$composite_key]['#access']);
          unset($element[$composite_key]['#pre_render']);
          $element[$composite_key]['#type'] = 'hidden';
        }
        elseif (!empty($element['#hidden']) && !empty($element['#geolocation'])) {
          unset($element[$composite_key]['#pre_render']);
          $element[$composite_key]['#type'] = 'hidden';
        }
        else {
          $element[$composite_key]['#wrapper_attributes']['class'][] = 'webform-readonly';
          $element[$composite_key]['#readonly'] = 'readonly';
        }
      }
    }

    // Get shared properties.
    $shared_properties = [
      '#required',
      '#placeholder',
    ];
    $element['value'] += array_intersect_key($element, array_combine($shared_properties, $shared_properties));

    // Set Geolocation detection attribute.
    if (!empty($element['#geolocation'])) {
      $element['value']['#attributes']['data-webform-location-' . static::$name . '-geolocation'] = 'data-webform-location-' . static::$name . '-geolocation';
    }

    // Set Map attribute.
    if (!empty($element['#map']) && empty($element['#hidden'])) {
      $element['value']['#attributes']['data-webform-location-' . static::$name . '-map'] = 'data-webform-location-' . static::$name . '-map';
    }

    $element += ['#element_validate' => []];
    array_unshift($element['#element_validate'], [get_called_class(), 'validateWebformLocation']);

    return $element;
  }

  /**
   * Validates location.
   */
  public static function validateWebformLocation(&$element, FormStateInterface $form_state, &$complete_form) {
    $value = $element['#value'];

    $has_access = (!isset($element['#access']) || $element['#access'] === TRUE);
    if ($has_access && !empty($element['#required']) && empty($value['lat'])) {
      $t_args = ['@title' => !empty($element['#title']) ? $element['#title'] : t('Location')];
      $form_state->setError($element['value'], t('The @title is not valid.', $t_args));
    }
  }

}
