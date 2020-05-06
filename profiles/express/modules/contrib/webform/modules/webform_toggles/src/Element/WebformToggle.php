<?php

namespace Drupal\webform_toggles\Element;

use Drupal\Core\Render\Element\Checkbox;

/**
 * Provides a webform element for entering a toggle.
 *
 * @FormElement("webform_toggle")
 */
class WebformToggle extends Checkbox {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return static::getDefaultProperties() + parent::getInfo();
  }

  /**
   * Get default properties for a toggle element.
   *
   * @return array
   *   An associative array container default properties for a toggle element.
   */
  public static function getDefaultProperties() {
    return [
      '#toggle_theme' => 'light',
      '#toggle_size' => 'medium',
      '#on_text' => '',
      '#off_text' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function preRenderCheckbox($element) {
    $element = parent::preRenderCheckbox($element);

    $element += [
      '#toggle_size' => 'medium',
      '#toggle_theme' => 'light',
      '#on_text' => '',
      '#off_text' => '',
    ];

    // Toggle heights.
    $sizes = ['large' => 36, 'medium' => 24, 'small' => 16];
    $height = $sizes[$element['#toggle_size']];
    if (!empty($element['#on_text']) || !empty($element['#off_text'])) {
      $width = $height * 3;
    }
    else {
      $width = $height * 2;
    }

    $attributes = [
      'class' => [
        'js-webform-toggle',
        'webform-toggle',
        'toggle',
        'toggle-' . $element['#toggle_size'],
        'toggle-' . $element['#toggle_theme'],
      ],
      'data-toggle-height' => $height,
      'data-toggle-width' => $width,
      'data-toggle-text-on' => $element['#on_text'],
      'data-toggle-text-off' => $element['#off_text'],
    ];

    $element['#children']['toggles'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => $attributes,
      '#attached' => [
        'library' => ['webform_toggles/webform_toggles.element'],
      ],
    ];

    return $element;
  }

}
