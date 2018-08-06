<?php

namespace Drupal\webform\Element;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Url;
use Drupal\webform\Entity\WebformOptions as WebformOptionsEntity;

/**
 * Provides a webform element for managing webform element options.
 *
 * This element is used by select, radios, checkboxes, likert, and
 * mapping elements.
 *
 * @FormElement("webform_element_options")
 */
class WebformElementOptions extends FormElement {

  const CUSTOM_OPTION = '';

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#likert' => FALSE,
      '#process' => [
        [$class, 'processWebformElementOptions'],
        [$class, 'processAjaxForm'],
      ],
      '#theme_wrappers' => ['form_element'],
      '#custom__type' => 'webform_options',
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
    elseif (!empty($input['options'])) {
      return $input['options'];
    }
    elseif (isset($input['custom']['options'])) {
      return $input['custom']['options'];
    }
    else {
      return [];
    }
  }

  /**
   * Processes a webform element options element.
   */
  public static function processWebformElementOptions(&$element, FormStateInterface $form_state, &$complete_form) {
    $element['#tree'] = TRUE;

    /** @var \Drupal\webform\WebformOptionsStorageInterface $webform_options_storage */
    $webform_options_storage = \Drupal::entityTypeManager()->getStorage('webform_options');
    $options = ($element['#likert']) ? $webform_options_storage->getLikerts() : $webform_options_storage->getOptions();

    $t_args = [
      '@type' => ($element['#likert']) ? t('answers') : t('options'),
      ':href' => Url::fromRoute('entity.webform_options.collection')->toString(),
    ];

    // Select options.
    $element['options'] = [
      '#type' => 'select',
      '#description' => t('Please select <a href=":href">predefined @type</a> or enter custom @type.', $t_args),
      '#options' => [
        self::CUSTOM_OPTION => t('Custom...'),
      ] + $options,

      '#attributes' => [
        'class' => ['js-' . $element['#id'] . '-options'],
      ],
      '#error_no_message' => TRUE,
      '#default_value' => (isset($element['#default_value']) && !is_array($element['#default_value'])) ? $element['#default_value'] : '',
    ];

    // Custom options.
    if ($element['#custom__type'] === 'webform_multiple') {
      $element['custom'] = [
        '#type' => 'webform_multiple',
        '#title' => $element['#title'],
        '#title_display' => 'invisible',
        '#states' => [
          'visible' => [
            'select.js-' . $element['#id'] . '-options' => ['value' => ''],
          ],
        ],
        '#error_no_message' => TRUE,
        '#default_value' => (isset($element['#default_value']) && !is_string($element['#default_value'])) ? $element['#default_value'] : [],
      ];
    }
    else {
      $element['custom'] = [
        '#type' => 'webform_options',
        '#title' => $element['#title'],
        '#title_display' => 'invisible',
        '#label' => ($element['#likert']) ? t('answer') : t('option'),
        '#labels' => ($element['#likert']) ? t('answers') : t('options'),
        '#states' => [
          'visible' => [
            'select.js-' . $element['#id'] . '-options' => ['value' => ''],
          ],
        ],
        '#error_no_message' => TRUE,
        '#default_value' => (isset($element['#default_value']) && !is_string($element['#default_value'])) ? $element['#default_value'] : [],
      ];
    }

    $element['#element_validate'] = [[get_called_class(), 'validateWebformElementOptions']];

    if (isset($element['#states'])) {
      webform_process_states($element, '#wrapper_attributes');
    }

    return $element;
  }

  /**
   * Validates a webform element options element.
   */
  public static function validateWebformElementOptions(&$element, FormStateInterface $form_state, &$complete_form) {
    $options_value = NestedArray::getValue($form_state->getValues(), $element['options']['#parents']);
    $custom_value = NestedArray::getValue($form_state->getValues(), $element['custom']['#parents']);

    $value = $options_value;
    if ($options_value == self::CUSTOM_OPTION) {
      try {
        $value = (is_string($custom_value)) ? Yaml::decode($custom_value) : $custom_value;
      }
      catch (\Exception $exception) {
        // Do nothing since the 'webform_codemirror' element will have already
        // captured the validation error.
      }
    }

    $has_access = (!isset($element['#access']) || $element['#access'] === TRUE);
    if ($element['#required'] && empty($value) && $has_access) {
      if (isset($element['#required_error'])) {
        $form_state->setError($element, $element['#required_error']);
      }
      elseif (isset($element['#title'])) {
        $form_state->setError($element, t('@name field is required.', ['@name' => $element['#title']]));
      }
      else {
        $form_state->setError($element);
      }
    }

    $form_state->setValueForElement($element['options'], NULL);
    $form_state->setValueForElement($element['custom'], NULL);
    $form_state->setValueForElement($element, $value);
  }

}
