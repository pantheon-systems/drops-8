<?php

namespace Drupal\webform\Element;

use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\CompositeFormElementTrait;

/**
 * Provides a webform image resolution element .
 *
 * @FormElement("webform_image_resolution")
 */
class WebformImageResolution extends FormElement {

  use CompositeFormElementTrait;

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#size' => 60,
      '#process' => [
        [$class, 'processWebformImageResolution'],
      ],
      '#theme_wrappers' => ['form_element'],
      '#required' => FALSE,
      // Add '#markup' property to add an 'id' attribute to the form element.
      // @see template_preprocess_form_element()
      '#markup' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input === FALSE) {
      if (!isset($element['#default_value'])) {
        $element['#default_value'] = '';
      }
      $max_resolution = explode('x', $element['#default_value']) + ['', ''];

      return [
        'x' => $max_resolution[0],
        'y' => $max_resolution[1],
      ];
    }
    else {
      return $input;
    }
  }

  /**
   * Expand a image resolution field into width and height elements.
   *
   * @see \Drupal\image\Plugin\Field\FieldType\ImageItem::fieldSettingsForm
   */
  public static function processWebformImageResolution(&$element, FormStateInterface $form_state, &$complete_form) {
    $element['#tree'] = TRUE;

    $element['#type'] = 'item';
    $element += [
      '#description' => t('The maximum allowed image size expressed as WIDTH×HEIGHT (e.g. 640×480). Leave blank for no restriction. If a larger image is uploaded, it will be resized to reflect the given width and height. Resizing images on upload will cause the loss of <a href="http://wikipedia.org/wiki/Exchangeable_image_file_format">EXIF data</a> in the image.'),
      '#height_title' => t('Maximum height'),
      '#width_title' => t('Maximum width'),
      '#field_prefix' => '<div class="container-inline">',
      '#field_suffix' => '</div>',
    ];
    $element['x'] = [
      '#type' => 'number',
      '#title' => $element['#width_title'],
      '#title_display' => 'invisible',
      '#value' => empty($element['#value']) ? NULL : $element['#value']['x'],
      '#min' => 1,
      '#field_suffix' => ' × ',
    ];
    $element['y'] = [
      '#type' => 'number',
      '#title' => $element['#height_title'],
      '#title_display' => 'invisible',
      '#value' => empty($element['#value']) ? NULL : $element['#value']['y'],
      '#min' => 1,
      '#field_suffix' => ' ' . t('pixels'),
    ];

    // Add validate callback.
    $element += ['#element_validate' => []];
    array_unshift($element['#element_validate'], [get_called_class(), 'validateWebformImageResolution']);

    return $element;
  }

  /**
   * Validates an image resolution element.
   *
   * @see \Drupal\image\Plugin\Field\FieldType\ImageItem::validateResolution
   */
  public static function validateWebformImageResolution(&$element, FormStateInterface $form_state, &$complete_form) {
    if (!empty($element['x']['#value']) || !empty($element['y']['#value'])) {
      foreach (['x', 'y'] as $dimension) {
        if (!$element[$dimension]['#value']) {
          // We expect the field name placeholder value to be wrapped in t()
          // here, so it won't be escaped again as it's already marked safe.
          $form_state->setError($element[$dimension], t('Both a height and width value must be specified in the @name field.', ['@name' => $element['#title']]));
          return;
        }
      }
      $form_state->setValueForElement($element, $element['x']['#value'] . 'x' . $element['y']['#value']);
    }
    else {
      $form_state->setValueForElement($element, '');
    }
  }

}
