<?php

namespace Drupal\webform\Element;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Serialization\Yaml;
use Drupal\webform\Plugin\WebformElement\WebformCompositeBase as WebformCompositeBaseElement;
use Drupal\webform\Utility\WebformArrayHelper;
use Drupal\webform\Utility\WebformYaml;

/**
 * Provides a element for the composite elements.
 *
 * @FormElement("webform_element_composite")
 */
class WebformElementComposite extends FormElement {

  /**
   * List of supported element properties.
   *
   * @var array
   */
  protected static $supportedProperties = [
    'key' => 'key',
    'type' => 'type',
    'title' => 'title',
    'help' => 'help',
    'placeholder' => 'placeholder',
    'description' => 'description',
    'options' => 'options',
    'required' => 'required',
  ];

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#process' => [
        [$class, 'processWebformElementComposite'],
        [$class, 'processAjaxForm'],
      ],
      '#theme_wrappers' => ['form_element'],
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
      if (!isset($element['#default_value']) || !is_array($element['#default_value'])) {
        return [];
      }
      else {
        $default_value = [];
        foreach ($element['#default_value'] as $composite_key => $composite_element) {
          $composite_element = ['key' => $composite_key] + WebformArrayHelper::removePrefix($composite_element);
          // Get supported properties.
          $composite_properties = array_intersect_key($composite_element, static::$supportedProperties);
          // Move 'unsupported' properties to 'custom'.
          $custom_properties = array_diff_key($composite_element, static::$supportedProperties);
          $composite_properties['custom'] = $custom_properties ? WebformYaml::encode($custom_properties) : '';
          $default_value[] = $composite_properties;
        }
        $element['#default_value'] = $default_value;
        return $default_value;
      }
    }
    elseif (is_array($input)) {
      return $input;
    }
    else {
      return NULL;
    }
  }

  /**
   * Processes a webform element composite (builder) element.
   */
  public static function processWebformElementComposite(&$element, FormStateInterface $form_state, &$complete_form) {
    /** @var \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager */
    $element_manager = \Drupal::service('plugin.manager.webform.element');

    $placeholder_elements = [];
    $options_elements = [];
    $type_options = [];

    $elements = $element_manager->getInstances();

    $definitions = $element_manager->getDefinitions();
    $definitions = $element_manager->getSortedDefinitions($definitions, 'category');
    $definitions = $element_manager->removeExcludeDefinitions($definitions);
    $grouped_definitions = $element_manager->getGroupedDefinitions($definitions);

    foreach ($grouped_definitions as $group => $definitions) {
      foreach ($definitions as $element_type => $definition) {
        if (!WebformCompositeBaseElement::isSupportedElementType($element_type)) {
          continue;
        }

        $element_plugin = $elements[$element_type];

        $type_options[$group][$element_type] = $definition['label'];
        if ($element_plugin->hasProperty('options')) {
          $options_elements[$element_type] = $element_type;
        }
        if ($element_plugin->hasProperty('placeholder')) {
          $placeholder_elements[$element_type] = $element_type;
        }
      }
    }

    $edit_source = \Drupal::currentUser()->hasPermission('edit webform source');
    $element['#tree'] = TRUE;
    $element['elements'] = [
      '#type' => 'webform_multiple',
      '#title' => t('Elements'),
      '#title_display' => 'invisible',
      '#label' => t('element'),
      '#labels' => t('elements'),
      '#empty_items' => 0,
      '#min_items' => 1,
      '#header' => TRUE,
      '#add' => FALSE,
      '#default_value' => (isset($element['#default_value'])) ? $element['#default_value'] : NULL,
      '#error_no_message' => TRUE,
      '#element' => [
        'settings' => [
          '#type' => 'container',
          '#title' => t('Settings'),
          '#help' => '<b>' . t('Key') . ':</b> ' . t('A unique machine-readable name. Can only contain lowercase letters, numbers, and underscores.') .
            '<hr/>' . '<b>' . t('Type') . ':</b> ' . t('The type of element to be displayed.') .
            '<hr/>' . '<b>' . t('Options') . ':</b> ' . t('Please select predefined options or enter custom options.') . ' ' . t('Key-value pairs MUST be specified as "safe_key: \'Some readable options\'". Use of only alphanumeric characters and underscores is recommended in keys. One option per line.') .
            ($edit_source ? '<hr/>' . '<b>' . t('Custom Properties') . ':</b> ' . t('Properties do not have to be prepended with a hash (#) character, the hash character will be automatically added to the custom properties.') : '') .
            '<hr/>' . '<b>' . t('Required') . ':</b> ' . t('Check this option if the user must enter a value.'),
          'key' => [
            '#type' => 'textfield',
            '#title' => t('Key'),
            '#title_display' => 'invisible',
            '#placeholder' => t('Enter key…'),
            '#pattern' => '^[a-z0-9_]+$',
            '#attributes' => [
              'title' => t('Enter a unique machine-readable name. Can only contain lowercase letters, numbers, and underscores.'),
            ],
            '#required' => TRUE,
            '#error_no_message' => TRUE,
          ],
          'type' => [
            '#type' => 'select',
            '#title' => t('Type'),
            '#title_display' => 'invisible',
            '#description' => t('The type of element to be displayed.'),
            '#description_display' => 'invisible',
            '#options' => $type_options,
            '#empty_option' => t('- Select type -'),
            '#required' => TRUE,
            '#attributes' => ['class' => ['js-webform-composite-type']],
            '#error_no_message' => TRUE,
          ],
          'options' => [
            '#type' => 'webform_element_options',
            '#yaml' => TRUE,
            '#title' => t('Options'),
            '#title_display' => 'invisible',
            '#description' => t('Please select predefined options or enter custom options.') . ' ' . t('Key-value pairs MUST be specified as "safe_key: \'Some readable options\'". Use of only alphanumeric characters and underscores is recommended in keys. One option per line.'),
            '#description_display' => 'invisible',
            '#wrapper_attributes' => [
              'data-composite-types' => implode(',', $options_elements),
              'data-composite-required' => 'data-composite-required',
            ],
            '#error_no_message' => TRUE,
          ],
          // ISSUE:
          // Set #access: FALSE is losing the custom properties.
          //
          // WORKAROUND:
          // Use 'hidden' element.
          // @see \Drupal\webform\Element\WebformMultiple::buildElementRow
          'custom' => $edit_source ? [
            '#type' => 'webform_codemirror',
            '#mode' => 'yaml',
            '#title' => t('Custom properties'),
            '#title_display' => 'invisible',
            '#description' => t('Properties do not have to be prepended with a hash (#) character, the hash character will be automatically added to the custom properties.'),
            '#description_display' => 'invisible',
            '#placeholder' => t('Enter custom properties…'),
            '#error_no_message' => TRUE,
          ] : [
            '#type' => 'hidden',
          ],
          // Note: Setting #return_value: TRUE is not returning any value.
          'required' => [
            '#type' => 'checkbox',
            '#title' => t('Required'),
            '#description' => t('Check this option if the user must enter a value.'),
            '#description_display' => 'invisible',
            '#error_no_message' => TRUE,
          ],
        ],
        'labels' => [
          '#type' => 'container',
          '#title' => t('Labels'),
          '#help' => '<b>' . t('Title') . ':</b> ' . t('This is used as a descriptive label when displaying this webform element.') .
            '<hr/><b>' . t('Placeholder') . ':</b> ' . t('The placeholder will be shown in the element until the user starts entering a value.') .
            '<hr/><b>' . t('Description') . ':</b> ' . t('A short description of the element used as help for the user when he/she uses the webform.') .
            '<hr/><b>' . t('Help text') . ':</b> ' . t('A tooltip displayed after the title.'),
          'title' => [
            '#type' => 'textfield',
            '#title' => t('Title'),
            '#title_display' => 'invisible',
            '#description' => t('This is used as a descriptive label when displaying this webform element.'),
            '#description_display' => 'invisible',
            '#placeholder' => t('Enter title…'),
            '#required' => TRUE,
            '#error_no_message' => TRUE,
          ],
          'placeholder' => [
            '#type' => 'textfield',
            '#title' => t('Placeholder'),
            '#title_display' => 'invisible',
            '#description' => t('The placeholder will be shown in the element until the user starts entering a value.'),
            '#description_display' => 'invisible',
            '#placeholder' => t('Enter placeholder…'),
            '#attributes' => ['data-composite-types' => implode(',', $placeholder_elements)],
            '#error_no_message' => TRUE,
          ],
          'description' => [
            '#type' => 'textarea',
            '#title' => t('Description'),
            '#description' => t('A short description of the element used as help for the user when he/she uses the webform.'),
            '#description_display' => 'invisible',
            '#title_display' => 'invisible',
            '#placeholder' => t('Enter description…'),
            '#rows' => 2,
            '#error_no_message' => TRUE,
          ],
          'help' => [
            '#type' => 'textarea',
            '#title' => t('Help text'),
            '#title_display' => 'invisible',
            '#description' => t('A tooltip displayed after the title.'),
            '#description_display' => 'invisible',
            '#placeholder' => t('Enter help text…'),
            '#rows' => 2,
            '#error_no_message' => TRUE,
          ],
        ],
      ],
    ];

    $element['#attached']['library'][] = 'webform/webform.element.composite';

    // Add validate callback.
    $element += ['#element_validate' => []];
    array_unshift($element['#element_validate'], [get_called_class(), 'validateWebformElementComposite']);

    return $element;
  }

  /**
   * Validates a webform element composite (builder) element.
   */
  public static function validateWebformElementComposite(&$element, FormStateInterface $form_state, &$complete_form) {
    /** @var \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager */
    $element_manager = \Drupal::service('plugin.manager.webform.element');
    $elements_value = NestedArray::getValue($form_state->getValues(), $element['elements']['#parents']);

    // Check for duplicate keys.
    $keys = [];
    foreach ($elements_value as $element_value) {
      $key = $element_value['key'];
      if (isset($keys[$key])) {
        $form_state->setError($element, t('Duplicate key found. The %key key must only be assigned on one element.', ['%key' => $key]));
        return;
      }
      $keys[$key] = $key;
    }

    // Convert the $elements value which is a simple associative array into a
    // render array.
    $elements = [];
    foreach ($elements_value as $element_value) {
      $key = $element_value['key'];
      unset($element_value['key']);

      // Remove empty strings from array.
      $element_value = array_filter($element_value, function ($value) {
        return ($value !== '');
      });

      // Unset empty required or case to boolean.
      if (empty($element_value['required'])) {
        unset($element_value['required']);
      }
      else {
        $element_value['required'] = TRUE;
      }

      // Limit value keys to supported element properties.
      // This removes options from elements that don't support #options.
      if (isset($element_value['type'])) {
        foreach ($element_value as $property_name => $property_value) {
          if (!in_array($property_name, ['type', 'custom'])) {
            /** @var \Drupal\webform\Plugin\WebformElementInterface $element_plugin */
            $element_plugin = $element_manager->createInstance($element_value['type']);
            if (!$element_plugin->hasProperty($property_name)) {
              unset($element_value[$property_name]);
            }
          }
        }
      }

      if (isset($element_value['custom'])) {
        if ($element_value['custom']) {
          $custom = Yaml::decode($element_value['custom']);
          if ($custom && is_array($custom)) {
            $element_value += $custom;
          }
        }
        unset($element_value['custom']);
      }

      $elements[$key] = WebformArrayHelper::addPrefix($element_value);
    }

    foreach ($elements as $composite_element_key => $composite_element) {
      if (!isset($composite_element['#type'])) {
        continue;
      }

      /** @var \Drupal\webform\Plugin\WebformElementInterface $element_plugin */
      $element_plugin = $element_manager->getElementInstance($composite_element);

      $t_args = [
        '%title' => (isset($composite_element['#title'])) ? $composite_element['#title'] : $composite_element_key,
        '@type' => $composite_element['#type'],
      ];

      // Make sure #options is set for composite element's that require #options.
      if ($element_plugin->hasProperty('options') && empty($composite_element['#options'])) {
        $form_state->setError($element, t('Options for %title is required.', $t_args));
      }

      // Make sure element is not storing multiple values.
      if ($element_plugin->hasMultipleValues($composite_element)) {
        $form_state->setError($element, t('Multiple value is not supported for %title (@type).', $t_args));
      }
    }

    $form_state->setValueForElement($element['elements'], NULL);

    $element['#value'] = $elements;
    $form_state->setValueForElement($element, $elements);
  }

}
