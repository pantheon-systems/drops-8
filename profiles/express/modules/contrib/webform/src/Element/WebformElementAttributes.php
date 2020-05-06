<?php

namespace Drupal\webform\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Serialization\Yaml;
use Drupal\webform\Utility\WebformElementHelper;
use Drupal\webform\Utility\WebformYaml;

/**
 * Provides a webform element for element attributes.
 *
 * @FormElement("webform_element_attributes")
 */
class WebformElementAttributes extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#process' => [
        [$class, 'processWebformElementAttributes'],
      ],
      '#pre_render' => [
        [$class, 'preRenderWebformElementAttributes'],
      ],
      '#theme_wrappers' => ['container'],
      '#classes' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    $element += ['#default_value' => []];
    $element['#default_value'] += [
      'class' => [],
      'style' => '',
    ];
    return NULL;
  }

  /**
   * Processes element attributes.
   */
  public static function processWebformElementAttributes(&$element, FormStateInterface $form_state, &$complete_form) {
    $element['#tree'] = TRUE;

    // Determine what type of HTML element the attributes are being applied to.
    $type = t('element');
    $types = [preg_quote(t('webform')), preg_quote(t('link')), preg_quote(t('button'))];
    if (preg_match('/\b(' . implode('|', $types) . ')\b/i', $element['#title'], $match)) {
      $type = $match[1];
    }

    $t_args = [
      '@title' => $element['#title'],
      '@type' => mb_strtolower($type),
    ];

    // Class.
    $element['#classes'] = trim($element['#classes']);
    if ($element['#classes']) {
      $classes = preg_split('/\r?\n/', $element['#classes']);
      $element['class'] = [
        '#type' => 'webform_select_other',
        '#title' => t('@title CSS classes', $t_args),
        '#description' => t("Apply classes to the @type. Select 'custom…' to enter custom classes.", $t_args),
        '#multiple' => TRUE,
        '#options' => [WebformSelectOther::OTHER_OPTION => t('custom…')] + array_combine($classes, $classes),
        '#other__placeholder' => t('Enter custom classes…'),
        '#other__option_delimiter' => ' ',
        '#attributes' => [
          'class' => [
            'js-' . $element['#id'] . '-attributes-style',
          ],
        ],
        '#select2' => TRUE,
        '#default_value' => $element['#default_value']['class'],
      ];
      WebformElementHelper::process($element['class']);
    }
    else {
      $element['class'] = [
        '#type' => 'textfield',
        '#title' => t('@title CSS classes', $t_args),
        '#description' => t("Apply classes to the @type.", $t_args),
        '#default_value' => implode(' ', $element['#default_value']['class']),
      ];
    }

    // Custom options.
    $element['custom'] = [
      '#type' => 'texfield',
      '#placeholder' => t('Enter custom classes…'),
      '#states' => [
        'visible' => [
          'select.js-' . $element['#id'] . '-attributes-style' => ['value' => '_custom_'],
        ],
      ],
      '#error_no_message' => TRUE,
      '#default_value' => '',
    ];

    // Style.
    $element['style'] = [
      '#type' => 'textfield',
      '#title' => t('@title CSS style', $t_args),
      '#description' => t('Apply custom styles to the @type.', $t_args),
      '#default_value' => $element['#default_value']['style'],
    ];

    // Attributes.
    $attributes = $element['#default_value'];
    unset($attributes['class'], $attributes['style']);
    $element['attributes'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'yaml',
      '#title' => t('@title custom attributes (YAML)', $t_args),
      '#description' => t('Enter additional attributes to be added the @type.', $t_args),
      '#attributes__access' => (!\Drupal::moduleHandler()->moduleExists('webform_ui') || \Drupal::currentUser()->hasPermission('edit webform source')),
      '#default_value' => WebformYaml::encode($attributes),
    ];

    // Apply custom properties. Typically used for descriptions.
    foreach ($element as $key => $value) {
      if (strpos($key, '__') !== FALSE) {
        list($element_key, $property_key) = explode('__', ltrim($key, '#'));
        $element[$element_key]["#$property_key"] = $value;
      }
    }

    // Add validate callback.
    $element += ['#element_validate' => []];
    array_unshift($element['#element_validate'], [get_called_class(), 'validateWebformElementAttributes']);

    return $element;
  }

  /**
   * Validates element attributes.
   */
  public static function validateWebformElementAttributes(&$element, FormStateInterface $form_state, &$complete_form) {
    $values = $element['#value'];

    $attributes = [];

    if ($values['class']) {
      if (isset($element['class']['select'])) {
        $class = $element['class']['select']['#value'];
        $class_other = $element['class']['other']['#value'];
        if (isset($class[WebformSelectOther::OTHER_OPTION])) {
          unset($class[WebformSelectOther::OTHER_OPTION]);
          $class[$class_other] = $class_other;
        }
        if ($class) {
          $attributes['class'] = array_values($class);
        }
      }
      else {
        $attributes['class'] = [$values['class']];
      }
    }

    if ($values['style']) {
      $attributes['style'] = $values['style'];
    }

    if (!empty($values['attributes'])) {
      $attributes += Yaml::decode($values['attributes']);
    }

    $form_state->setValueForElement($element['class'], NULL);
    $form_state->setValueForElement($element['style'], NULL);
    $form_state->setValueForElement($element['attributes'], NULL);

    $element['#value'] = $attributes;
    $form_state->setValueForElement($element, $attributes);
  }

  /**
   * Prepares a #type 'webform_element_attributes' render element.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   *
   * @return array
   *   The $element.
   */
  public static function preRenderWebformElementAttributes($element) {
    static::setAttributes($element, ['webform-element-attributes']);
    return $element;
  }

}
