<?php

namespace Drupal\webform_image_select\Element;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Element\WebformMessage;
use Drupal\webform\Utility\WebformElementHelper;

/**
 * Provides a form element to assist in creation of webform select image images.
 *
 * @FormElement("webform_image_select_images")
 */
class WebformImageSelectImages extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#label' => t('image'),
      '#labels' => t('images'),
      '#min_items' => 3,
      '#empty_items' => 1,
      '#add_more_items' => 1,
      '#process' => [
        [$class, 'processWebformImageSelectImages'],
      ],
      '#theme_wrappers' => ['form_element'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input === FALSE) {
      if (!isset($element['#default_value'])) {
        return [];
      }
      return $element['#default_value'];
    }
    elseif (is_array($input) && isset($input['images'])) {
      return $input['images'];
    }
    else {
      return NULL;
    }
  }

  /**
   * Process images and build images widget.
   */
  public static function processWebformImageSelectImages(&$element, FormStateInterface $form_state, &$complete_form) {
    $element['#tree'] = TRUE;

    // Add validate callback that extracts the associative array of images.
    $element['#element_validate'] = [[get_called_class(), 'validateWebformImageSelectImages']];

    // Wrap this $element in a <div> that handle #states.
    WebformElementHelper::fixStatesWrapper($element);

    $properties = ['#label', '#labels', '#min_items', '#empty_items', '#add_more_items'];

    $element['images'] = array_intersect_key($element, array_combine($properties, $properties)) + [
      '#type' => 'webform_multiple',
      '#key' => 'value',
      '#header' => [
        ['data' => t('Image value'), 'width' => '25%'],
        ['data' => t('Image text'), 'width' => '25%'],
        ['data' => t('Image src'), 'width' => '50%'],
      ],
      '#element' => [
        'value' => [
          '#type' => 'textfield',
          '#title' => t('Image value'),
          '#title_display' => 'invisible',
          '#placeholder' => t('Enter value…'),
          '#error_no_message' => TRUE,
          '#attributes' => ['class' => ['js-webform-options-sync']],
        ],
        'text' => [
          '#type' => 'textfield',
          '#title' => t('Image text'),
          '#title_display' => 'invisible',
          '#placeholder' => t('Enter text…'),
          '#error_no_message' => TRUE,
        ],
        'src' => [
          '#type' => 'textfield',
          '#title' => t('Image src'),
          '#title_display' => 'invisible',
          '#placeholder' => t('Enter image src…'),
          '#error_no_message' => TRUE,
        ],
      ],
      '#error_no_message' => TRUE,
      '#add_more_input_label' => t('more images'),
      '#default_value' => (isset($element['#default_value'])) ? $element['#default_value'] : [],
    ];

    if (function_exists('imce_process_url_element')) {
      $src_element = &$element['images']['#element']['src'];
      imce_process_url_element($src_element, 'link');
      $element['#attached']['library'][] = 'webform/imce.input';
    }
    elseif (\Drupal::currentUser()->hasPermission('administer modules')) {
      $element['imce_message'] = [
        '#type' => 'webform_message',
        '#message_type' => 'warning',
        '#message_message' => t('Install the <a href=":href">IMCE module</a> to manage and uploaded image files.', [':href' => 'https://www.drupal.org/project/imce']),
        '#message_close' => TRUE,
        '#message_id' => 'webform_imce_message',
        '#message_storage' => WebformMessage::STORAGE_LOCAL,
      ];
    }

    $element['#attached']['library'][] = 'webform/webform.element.options.admin';

    return $element;
  }

  /**
   * Validates webform image select images element.
   */
  public static function validateWebformImageSelectImages(&$element, FormStateInterface $form_state, &$complete_form) {
    $options = NestedArray::getValue($form_state->getValues(), $element['images']['#parents']);

    // Validate required images.
    if (!empty($element['#required']) && empty($options)) {
      WebformElementHelper::setRequiredError($element, $form_state);
      return;
    }

    $form_state->setValueForElement($element, $options);
  }

}
