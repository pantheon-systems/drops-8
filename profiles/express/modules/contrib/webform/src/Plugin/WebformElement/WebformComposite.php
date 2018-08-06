<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\webform\Plugin\WebformElementEntityReferenceInterface;
use Drupal\webform\Utility\WebformArrayHelper;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a custom 'composite' element.
 *
 * @WebformElement(
 *   id = "webform_composite",
 *   label = @Translation("Composite custom"),
 *   description = @Translation("Provides a form element to create custom composites using a grid/table layout."),
 *   category = @Translation("Composite elements"),
 *   multiline = TRUE,
 *   composite = TRUE,
 *   states_wrapper = TRUE,
 * )
 */
class WebformComposite extends WebformCompositeBase {

  /**
   * List of ignored element types.
   *
   * @var array
   */
  protected static $ignoredElementTypes = [
    'hidden',
    'value',
    'webform_autocomplete',
    'webform_image_select',
    'webform_terms_of_service',
  ];

  /**
   * List of supported  element properties.
   *
   * @var array
   */
  protected $supportedProperties = [
    'key' => 'key',
    'type' => 'type',
    'title' => 'title',
    'help' => 'help',
    'description' => 'description',
    'options' => 'options',
    'required' => 'required',
  ];

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    $properties = $this->getDefaultMultipleProperties() + parent::getDefaultProperties();
    $properties['element'] = [];
    unset($properties['flexbox']);
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultMultipleProperties() {
    $properties = [
      'multiple' => TRUE,
      'multiple__header' => TRUE,
    ] + parent::getDefaultMultipleProperties();
    return $properties;

  }

  /**
   * {@inheritdoc}
   */
  public function hasMultipleValues(array $element) {
    // WebformComposite extends the WebformMultiple and will always store
    // multiple values.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepare($element, $webform_submission);

    // Set cardinality.
    if (isset($element['#multiple'])) {
      $element['#cardinality'] = ($element['#multiple'] === FALSE) ? 1 : $element['#multiple'];
    }

    // Apply multiple properties.
    $multiple_properties = $this->getDefaultMultipleProperties();
    foreach ($multiple_properties as $multiple_property => $multiple_value) {
      if (strpos($multiple_property,'multiple__') === 0) {
        $property_name = str_replace('multiple__', '', $multiple_property);
        $element["#$property_name"] = (isset($element["#$multiple_property"])) ? $element["#$multiple_property"] : $multiple_value;
      }
    }

    // Default to displaying table header.
    $element += ['#header' => TRUE];

    // If header label is defined use it for the #header.
    if (!empty($element['#multiple__header_label'])) {
      $element['#header'] = $element['#multiple__header_label'];
    }

    // Transfer '#{composite_key}_{property}' from main element to composite
    // element.
    foreach ($element['#element'] as $composite_key => $composite_element) {
      foreach ($element as $property_key => $property_value) {
        if (strpos($property_key, '#' . $composite_key . '__') === 0) {
          $composite_property_key = str_replace('#' . $composite_key . '__', '#', $property_key);
          $element['#element'][$composite_key][$composite_property_key] = $property_value;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareMultipleWrapper(array &$element) {
    // Don't set multiple wrapper since 'webform_composite' extends
    // 'webform_multiple'.
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Always to should multiple element settings since WebformComposite
    // extends WebformMultiple.
    unset($form['multiple']['#states']);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildCompositeElementsTable() {
    $placeholder_elements = [];
    $options_elements = [];
    $type_options = [];

    $elements = $this->elementManager->getInstances();

    $definitions = $this->elementManager->getDefinitions();
    $definitions = $this->elementManager->getSortedDefinitions($definitions, 'category');
    $definitions = $this->elementManager->removeExcludeDefinitions($definitions);
    $grouped_definitions = $this->elementManager->getGroupedDefinitions($definitions);

    foreach ($grouped_definitions as $group => $definitions) {
      foreach ($definitions as $element_type => $definition) {
        if (!static::isSupportedElementType($element_type)) {
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

    $edit_source = $this->currentUser->hasPermission('edit webform source');
    return [
      '#type' => 'webform_multiple',
      '#title' => $this->t('Elements'),
      '#title_display' => $this->t('Invisible'),
      '#label' => t('element'),
      '#labels' => t('elements'),
      '#empty_items' => 0,
      '#header' => TRUE,
      '#element' => [
        'key_type_options' => [
          '#type' => 'container',
          '#title' => ($edit_source) ? $this->t('Key / Type / Options / Custom Properties') : $this->t('Key / Type / Options '),
          '#help' => '<b>' . $this->t('Key') . ':</b> ' . $this->t('A unique machine-readable name. Can only contain lowercase letters, numbers, and underscores.') .
            '<br/><br/>' . '<b>' . $this->t('Type') . ':</b> ' . $this->t('The type of element to be displayed.') .
            '<br/><br/>' . '<b>' . $this->t('Options') . ':</b> ' . $this->t('Please select predefined options or enter custom options.') . ' ' . t('Key-value pairs MUST be specified as "safe_key: \'Some readable options\'". Use of only alphanumeric characters and underscores is recommended in keys. One option per line.') .
            ($edit_source ? '<br/><br/>' . '<b>' . $this->t('Custom Properties') . ':</b> ' .$this->t('Properties do not have to be prepended with a hash (#) character, the hash character will be automatically added to the custom properties.') : ''),
          'key' => [
            '#type' => 'textfield',
            '#title' => $this->t('Key'),
            '#title_display' => 'invisible',
            '#placeholder' => $this->t('Enter key'),
            '#pattern' => '^[a-z0-9_]+$',
            '#required' => TRUE,
          ],
          'type' => [
            '#type' => 'select',
            '#title' => $this->t('Type'),
            '#title_display' => 'invisible',
            '#options' => $type_options,
            '#empty_option' => $this->t('Select type'),
            '#required' => TRUE,
            '#attributes' => ['class' => ['js-webform-composite-type']],
          ],
          'options' => [
            '#type' => 'webform_element_options',
            '#yaml' => TRUE,
            '#title' => $this->t('Options'),
            '#title_display' => 'invisible',
            '#wrapper_attributes' => [
              'data-composite-types' => implode(',', $options_elements),
              'data-composite-required' => 'data-composite-required',
            ],
          ],
          'custom' => [
            '#type' => 'webform_codemirror',
            '#mode' => 'yaml',
            '#title' => $this->t('Custom properties'),
            '#title_display' => 'invisible',
            '#placeholder' => $this->t('Enter custom properties'),
            '#access' => $edit_source,
          ],
        ],
        'title_placeholder_description_help' => [
          '#type' => 'container',
          '#title' => $this->t('Title / Placeholder / Description / Help'),
          '#help' => '<b>' . $this->t('Title') . ':</b> ' . $this->t('This is used as a descriptive label when displaying this webform element.') . '<br/><br/>' .
            '<b>' . $this->t('Placeholder') . ':</b> ' . $this->t('The placeholder will be shown in the element until the user starts entering a value.') . '<br/><br/>' .
            '<b>' . $this->t('Description') . ':</b> ' . $this->t('A short description of the element used as help for the user when he/she uses the webform.') . '<br/><br/>' .
            '<b>' . $this->t('Help text') . ':</b> ' .$this->t('A tooltip displayed after the title.'),

          'title' => [
            '#type' => 'textfield',
            '#title' => $this->t('Title'),
            '#title_display' => 'invisible',
            '#placeholder' => $this->t('Enter title'),
            '#required' => TRUE,
          ],
          'placeholder' => [
            '#type' => 'textfield',
            '#title' => $this->t('Placeholder'),
            '#title_display' => 'invisible',
            '#placeholder' => $this->t('Enter placeholder'),
            '#attributes' => ['data-composite-types' => implode(',', $placeholder_elements)],
          ],
          'description' => [
            '#type' => 'textarea',
            '#title' => $this->t('Description'),
            '#title_display' => 'invisible',
            '#placeholder' => $this->t('Enter description'),
            '#rows' => 2,
          ],
          'help' => [
            '#type' => 'textarea',
            '#title' => $this->t('Help text'),
            '#title_display' => 'invisible',
            '#placeholder' => $this->t('Enter help text'),
            '#rows' => 2,
          ],
        ],
        'required' => [
          '#type' => 'checkbox',
          '#title' => $this->t('Required'),
          '#title_display' => 'invisible',
          '#return_value' => TRUE,
          '#help' => $this->t('Check this option if the user must enter a value.'),
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function setConfigurationFormDefaultValue(array &$form, array &$element_properties, array &$property_element, $property_name) {
    // Convert the #element property which contains a render array
    // into a simple associative array.
    if ($property_name == 'element') {
      $default_value = [];
      foreach ($element_properties[$property_name] as $key => $properties) {
        $composite_element = ['key' => $key] + WebformArrayHelper::removePrefix($properties);
        $composite_properties = array_intersect_key($composite_element, $this->supportedProperties);
        $composite_properties['custom'] = trim(Yaml::encode(array_diff_key($composite_element, $this->supportedProperties)));
        $default_value[] = $composite_properties;
      }
      $element_properties[$property_name] = $default_value;
    }

    parent::setConfigurationFormDefaultValue($form, $element_properties, $property_element, $property_name);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigurationFormProperties(array &$form, FormStateInterface $form_state) {
    $properties = parent::getConfigurationFormProperties($form, $form_state);

    // Convert the #element property which a simple associative array into a
    // render array.
    $element = [];
    foreach ($properties['#element'] as $value) {
      $key = $value['key'];
      unset($value['key']);

      // Limit value keys to supported element properties.
      $value = array_filter($value);
      foreach ($value as $property_name => $property_value) {
        if (!in_array($property_name, ['type', 'custom'])) {
          /** @var \Drupal\webform\Plugin\WebformElementInterface $element_plugin */
          $element_plugin = $this->elementManager->createInstance($value['type']);
          if (!$element_plugin->hasProperty($property_name)) {
            unset($value[$property_name]);
          }
        }
      }

      if (isset($value['custom'])) {
        if ($value['custom']) {
          $value += Yaml::decode($value['custom']);
        }
        unset($value['custom']);
      }

      $element[$key] = WebformArrayHelper::addPrefix($value);
    }
    $properties['#element'] = $element;

    // Remove #multiple_header in #multiple is FALSE.
    if (isset($properties['#multiple']) && $properties['#multiple'] === FALSE) {
      unset($properties['#multiple__header']);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);

    // Check for duplicate keys.
    $keys = [];
    $element = $form_state->getValue('element');
    foreach ($element as $value) {
      $key = $value['key'];
      if (isset($keys[$key])) {
        $form_state->setErrorByName('element', $this->t('Duplicate key found. The %key key must 
        only be assigned on one element.', ['%key' => $key]));
      }
      $keys[$key] = $key;
    }

    // Make #options is set for composite element that requires #options.
    $properties = $this->getConfigurationFormProperties($form, $form_state);
    foreach ($properties['#element'] as $key => $element) {
      /** @var \Drupal\webform\Plugin\WebformElementInterface $element_plugin */
      $element_plugin = $this->elementManager->getElementInstance($element);
      if ($element_plugin->hasProperty('options') && empty($element['#options'])) {
        $t_args = ['%title' => $element['#title']];
        $form_state->setErrorByName('element', $this->t('Options for %title is required.', $t_args));
      }
    }
  }

  /****************************************************************************/
  // Preview method.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function preview() {
    return [
      '#type' => $this->getTypeName(),
      '#title' => $this->getPluginLabel(),
      '#element' => [
        'name' => [
          '#type' => 'textfield',
          '#title' => 'Name',
          '#title_display' => 'invisible',
        ],
        'gender' => [
          '#type' => 'select',
          '#title' => 'Gender',
          '#title_display' => 'invisible',
          '#options' => [
            'Male' => $this->t('Male'),
            'Female' => $this->t('Female'),
          ],
        ],
      ],
    ];
  }

  /****************************************************************************/
  // Test methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function getTestValues(array $element, WebformInterface $webform, array $options = []) {
    /** @var \Drupal\webform\WebformSubmissionGenerateInterface $generate */
    $generate = \Drupal::service('webform_submission.generate');

    $composite_elements = $element['#element'];

    // Initialize, prepare, and populate composite sub-element.
    foreach ($composite_elements as $composite_key => $composite_element) {
      $element_plugin = $this->elementManager->getElementInstance($composite_element);
      $element_plugin->initialize($composite_element);
      $composite_elements[$composite_key] = $composite_element;
    }

    $values = [];
    for ($i = 1; $i <= 3; $i++) {
      $value = [];
      foreach ($composite_elements as $composite_key => $composite_element) {
        $value[$composite_key] = $generate->getTestValue($webform, $composite_key, $composite_element, $options);
      }
      $values[] = $value;
    }
    return $values;
  }

  /****************************************************************************/
  // Composite element methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function initializeCompositeElements(array &$element) {
    $element['#webform_composite_elements'] = [];
    /** @var \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager */
    $element_manager = \Drupal::service('plugin.manager.webform.element');
    foreach ($element['#element'] as $composite_key => $composite_element) {
      // Initialize, prepare, and populate composite sub-element.
      $element_plugin = $element_manager->getElementInstance($composite_element);
      $element_plugin->initialize($composite_element);
      $element['#webform_composite_elements'][$composite_key] = $composite_element;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCompositeElements() {
    // Return empty array since composite (sub) elements are custom.
    return [];
  }

  /**
   * Determine if element type is supported by custom composite elements.
   *
   * @param string $type
   *   An element type.
   *
   * @return bool
   *   TRUE if element type is supported by custom composite elements.
   */
  public static function isSupportedElementType($type) {
    $element_manager = \Drupal::service('plugin.manager.webform.element');

    $element = ['#type' => $type];
    $element_plugin = $element_manager->getElementInstance($element);
    // Skip element types that are not supported.
    if (!$element_plugin->isInput($element)
      || $element_plugin->isComposite()
      || $element_plugin->isContainer($element)
      || $element_plugin->hasMultipleValues($element)
      || $element_plugin instanceof WebformElementEntityReferenceInterface
      || $element_plugin instanceof WebformComputedBase
      || $element_plugin instanceof WebformManagedFileBase
      || in_array($type, static::$ignoredElementTypes)) {
      return FALSE;
    }
    else {
      return TRUE;
    }
  }

}
