<?php

namespace Drupal\webform_image_select\Element;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Url;
use Drupal\webform\Entity\WebformOptions as WebformOptionsEntity;
use Drupal\webform\Utility\WebformElementHelper;

/**
 * Provides a form element for managing webform element options.
 *
 * This element is used by select, radios, checkboxes, likert, and
 * mapping elements.
 *
 * @FormElement("webform_image_select_element_images")
 */
class WebformImageSelectElementImages extends FormElement {

  const CUSTOM_OPTION = '';

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#process' => [
        [$class, 'processWebformImageSelectElementImages'],
        [$class, 'processAjaxForm'],
      ],
      '#theme_wrappers' => ['form_element'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input === FALSE) {
      if (isset($element['#default_value'])) {
        if (is_string($element['#default_value'])) {
          return (WebformOptionsEntity::load($element['#default_value'])) ? $element['#default_value'] : [];
        }
        else {
          return $element['#default_value'];
        }
      }
      else {
        return [];
      }
    }
    elseif (!empty($input['images'])) {
      return $input['images'];
    }
    elseif (isset($input['custom']['images'])) {
      return $input['custom']['images'];
    }
    else {
      return [];
    }
  }

  /**
   * Processes a webform element image select images element.
   */
  public static function processWebformImageSelectElementImages(&$element, FormStateInterface $form_state, &$complete_form) {
    $element['#tree'] = TRUE;

    /** @var \Drupal\webform_image_select\WebformImageSelectImagesStorageInterface $webform_images_storage */
    $webform_images_storage = \Drupal::entityTypeManager()->getStorage('webform_image_select_images');
    $webform_images = $webform_images_storage->getImages();

    $t_args = [
      ':href' => Url::fromRoute('entity.webform_image_select_images.collection')->toString(),
    ];

    $class_name = 'js-' . $element['#id'] . '-webform-image-select-images';

    // Select images.
    $element['images'] = [
      '#type' => 'select',
      '#description' => t('Please select <a href=":href">predefined images</a> or enter custom image.', $t_args),
      '#options' => [
        self::CUSTOM_OPTION => t('Custom images…'),
      ] + $webform_images,
      '#attributes' => [
        'class' => [$class_name],
      ],
      '#error_no_message' => TRUE,
      '#access' => count($webform_images) ? TRUE : FALSE,
      '#default_value' => (isset($element['#default_value']) && !is_array($element['#default_value'])) ? $element['#default_value'] : '',
    ];

    // Custom images.
    $element['custom'] = [
      '#type' => 'webform_image_select_images',
      '#title' => $element['#title'],
      '#title_display' => 'invisible',
      '#states' => [
        'visible' => [
          "select.$class_name" => ['value' => ''],
        ],
      ],
      '#error_no_message' => TRUE,
      '#default_value' => (isset($element['#default_value']) && !is_string($element['#default_value'])) ? $element['#default_value'] : [],
    ];

    $element['#element_validate'] = [[get_called_class(), 'validateWebformImageSelectElementImages']];

    if (!empty($element['#states'])) {
      webform_process_states($element, '#wrapper_attributes');
    }

    return $element;
  }

  /**
   * Validates a webform element image select images element.
   */
  public static function validateWebformImageSelectElementImages(&$element, FormStateInterface $form_state, &$complete_form) {
    $options_value = NestedArray::getValue($form_state->getValues(), $element['images']['#parents']);
    $custom_value = NestedArray::getValue($form_state->getValues(), $element['custom']['#parents']);

    $value = $options_value;
    if ($options_value == self::CUSTOM_OPTION) {
      $value = $custom_value;
    }

    $has_access = (!isset($element['#access']) || $element['#access'] === TRUE);
    if ($element['#required'] && empty($value) && $has_access) {
      WebformElementHelper::setRequiredError($element, $form_state);
    }

    $form_state->setValueForElement($element['images'], NULL);
    $form_state->setValueForElement($element['custom'], NULL);
    $form_state->setValueForElement($element, $value);
  }

}
