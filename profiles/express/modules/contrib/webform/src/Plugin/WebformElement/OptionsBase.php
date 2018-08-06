<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\OptGroup;
use Drupal\webform\Utility\WebformArrayHelper;
use Drupal\webform\Utility\WebformElementHelper;
use Drupal\webform\Utility\WebformOptionsHelper;
use Drupal\webform\Plugin\WebformElementBase;
use Drupal\webform\Plugin\WebformElementEntityReferenceInterface;
use Drupal\webform\WebformSubmissionConditionsValidator;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a base 'options' element.
 */
abstract class OptionsBase extends WebformElementBase {

  /**
   * Export delta for multiple options.
   *
   * @var bool
   */
  protected $exportDelta = FALSE;

  /**
   * The other option base element type.
   *
   * @var string
   */
  protected $otherOptionType;

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    $properties = parent::getDefaultProperties();

    // Issue #2836374: Wrapper attributes are not supported by composite
    // elements, this includes radios, checkboxes, and buttons.
    if (preg_match('/(radios|checkboxes|buttons|tableselect|tableselect_sort|table_sort)$/', $this->getPluginId())) {
      unset($properties['wrapper_attributes']);
    }

    if (preg_match('/(tableselect|tableselect_sort|table_sort)$/', $this->getPluginId())) {
      unset($properties['title_display']);
      unset($properties['help']);
      unset($properties['description']);
      unset($properties['description_display']);
    }

    $properties += [
      // Options settings.
      'options' => [],
      'options_randomize' => FALSE,
    ];

    // Add other properties to elements that include the other text field.
    if ($this->isOptionsOther()) {
      $properties += [
        'other__option_label' => $this->t('Other...'),
        'other__type' => 'textfield',
        'other__title' => '',
        'other__placeholder' => $this->t('Enter other...'),
        'other__description' => '',
        // Text field or textarea.
        'other__size' => '',
        'other__maxlength' => '',
        'other__field_prefix' => '',
        'other__field_suffix' => '',
        // Textarea.
        'other__rows' => '',
        // Number.
        'other__min' => '',
        'other__max' => '',
        'other__step' => '',
      ];
    }

    return $properties;
  }

  /**
   * Determine if the element plugin type includes an other option text field.
   *
   * @return bool
   *   TRUE if the element plugin type includes an other option text field.
   */
  protected function isOptionsOther() {
    return $this->getOptionsOtherType() ? TRUE : FALSE;
  }

  /**
   * Get the other option base element type.
   *
   * @return string|null
   *   The base element type (select|radios|checkboxes|buttons).
   */
  protected function getOptionsOtherType() {
    if (isset($this->otherOptionType)) {
      return $this->otherOptionType;
    }

    if (preg_match('/webform_(select|radios|checkboxes|buttons)_other$/', $this->getPluginId(), $match)) {
      $this->otherOptionType = $match[1];
    }
    else {
      $this->otherOptionType = FALSE;
    }

    return $this->otherOptionType;
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslatableProperties() {
    return array_merge(
      parent::getTranslatableProperties(),
      ['options', 'empty_option', 'option_label']
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getRelatedTypes(array $element) {
    $related_types = parent::getRelatedTypes($element);
    // Remove entity reference elements.
    $elements = $this->elementManager->getInstances();
    foreach ($related_types as $type => $related_type) {
      $element_instance = $elements[$type];
      if ($element_instance instanceof WebformElementEntityReferenceInterface) {
        unset($related_types[$type]);
      }
    }
    return $related_types;
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    $is_wrapper_fieldset = in_array($element['#type'], ['checkboxes', 'radios', 'webform_entity_checkboxes', 'webform_entity_radios', 'webform_term_checkboxes', 'webform_toggles', 'webform_buttons']);
    if ($is_wrapper_fieldset) {
      // Issue #2396145: Option #description_display for webform element fieldset
      // is not changing anything.
      // @see core/modules/system/templates/fieldset.html.twig
      $is_description_display = (isset($element['#description_display'])) ? TRUE : FALSE;
      $has_description = (!empty($element['#description'])) ? TRUE : FALSE;
      if ($is_description_display && $has_description) {
        $description = WebformElementHelper::convertToString($element['#description']);
        switch ($element['#description_display']) {
          case 'before':
            $element += ['#field_prefix' => ''];
            $element['#field_prefix'] = '<div class="description">' . $description . '</div>' . $element['#field_prefix'];
            unset($element['#description']);
            unset($element['#description_display']);
            break;

          case 'tooltip':
            $element += ['#field_suffix' => ''];
            $element['#field_suffix'] .= '<div class="description visually-hidden">' . $description . '</div>';
            // @see \Drupal\Core\Render\Element\CompositeFormElementTrait
            // @see \Drupal\webform\Plugin\WebformElementBase::prepare
            $element['#attributes']['class'][] = 'js-webform-tooltip-element';
            $element['#attributes']['class'][] = 'webform-tooltip-element';
            $element['#attached']['library'][] = 'webform/webform.tooltip';
            unset($element['#description']);
            unset($element['#description_display']);
            break;

          case 'invisible':
            $element += ['#field_suffix' => ''];
            $element['#field_suffix'] .= '<div class="description visually-hidden">' . $description . '</div>';
            unset($element['#description']);
            unset($element['#description_display']);
            break;
        }
      }
    }

    parent::prepare($element, $webform_submission);

    // Randomize options.
    if (isset($element['#options']) && !empty($element['#options_randomize'])) {
      $element['#options'] = WebformArrayHelper::shuffle($element['#options']);
    }

    // Options description display must be set to trigger the description display.
    if ($this->hasProperty('options_description_display') && empty($element['#options_description_display'])) {
      $element['#options_description_display'] = $this->getDefaultProperty('options_description_display');
    }

    // Options display must be set to trigger the options display.
    if ($this->hasProperty('options_display') && empty($element['#options_display'])) {
      $element['#options_display'] = $this->getDefaultProperty('options_display');
    }

    // If the element is #required and the #default_value is an empty string
    // we need to unset the #default_value to prevent the below error.
    // 'An illegal choice has been detected'.
    if (!empty($element['#required']) && isset($element['#default_value']) && $element['#default_value'] === '') {
      unset($element['#default_value']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function hasMultipleWrapper() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function setDefaultValue(array &$element) {
    if (!isset($element['#default_value'])) {
      return;
    }

    // Compensate for #default_value not being an array, for elements that
    // allow for multiple #options to be selected/checked.
    if ($this->hasMultipleValues($element) && !is_array($element['#default_value'])) {
      $element['#default_value'] = [$element['#default_value']];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = parent::getValue($element, $webform_submission, $options);

    $format = $this->getItemFormat($element);
    if ($format == 'value' && isset($element['#options'])) {
      $flattened_options = OptGroup::flattenOptions($element['#options']);
      $options_description = $this->hasProperty('options_description_display');
      return WebformOptionsHelper::getOptionText($value, $flattened_options, $options_description);
    }
    else {
      return $value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getItemsDefaultFormat() {
    return 'comma';
  }

  /**
   * {@inheritdoc}
   */
  public function preview() {
    $element = parent::preview();
    if ($this->hasProperty('options')) {
      $element['#options'] = [
        'one' => 'One',
        'two' => 'Two',
        'three' => 'Three',
      ];
    }
    if ($this->hasProperty('options_display')) {
      $element['#options_display'] = 'side_by_side';
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getTableColumn(array $element) {
    $key = $element['#webform_key'];
    $columns = parent::getTableColumn($element);
    $columns['element__' . $key]['sort'] = !$this->hasMultipleValues($element);
    return $columns;
  }

  /**
   * {@inheritdoc}
   */
  public function getExportDefaultOptions() {
    return [
      'options_single_format' => 'compact',
      'options_multiple_format' => 'compact',
      'options_item_format' => 'label',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildExportOptionsForm(array &$form, FormStateInterface $form_state, array $export_options) {
    parent::buildExportOptionsForm($form, $form_state, $export_options);
    if (isset($form['options'])) {
      return;
    }

    // Build format options with help.
    $options_format_options = [
      'compact' => $this->t('Compact, with the option values delimited by commas in one column.') .
        WebformOptionsHelper::DESCRIPTION_DELIMITER .
        $this->t('Compact options are more suitable for importing data into other systems.'),
      'separate' => $this->t('Separate, with each possible option value in its own column.') .
        WebformOptionsHelper::DESCRIPTION_DELIMITER .
        $this->t('Separate options are more suitable for building reports, graphs, and statistics in a spreadsheet application. Ranking will be included for sortable option elements.'),
    ];
    $form['options'] = [
      '#type' => 'details',
      '#title' => $this->t('Select menu, radio buttons, and checkboxes options'),
      '#open' => TRUE,
      '#weight' => -10,
    ];
    $form['options']['options_single_format'] = [
      '#type' => 'radios',
      '#title' => $this->t('Options single value format'),
      '#description' => $this->t('Elements that collect a single option value include select menus, radios, and buttons.'),
      '#options' => $options_format_options,
      '#options_description_display' => 'help',
      '#default_value' => $export_options['options_single_format'],
    ];
    $form['options']['options_multiple_format'] = [
      '#type' => 'radios',
      '#title' => $this->t('Options multiple values format'),
      '#description' => $this->t('Elements that collect multiple option values include multi-select, checkboxes, and toggles.'),
      '#options' => $options_format_options,
      '#options_description_display' => 'help',
      '#default_value' => $export_options['options_multiple_format'],
    ];
    $form['options']['options_item_format'] = [
      '#type' => 'radios',
      '#title' => $this->t('Options item format'),
      '#options' => [
        'label' => $this->t('Option labels, the human-readable value (label)'),
        'key' => $this->t('Option values, the raw value stored in the database (key)'),
      ],
      '#default_value' => $export_options['options_item_format'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildExportHeader(array $element, array $options) {
    $options_format = ($element['#webform_multiple'] ? $options['options_multiple_format'] : $options['options_single_format']);
    if ($options_format == 'separate' && isset($element['#options'])) {
      $header = [];
      foreach ($element['#options'] as $option_value => $option_text) {
        // Note: If $option_text is an array (typically a tableselect row)
        // always use $option_value.
        $title = ($options['options_item_format'] == 'key' || is_array($option_text)) ? $option_value : $option_text;
        $header[] = $title;
      }
      return $this->prefixExportHeader($header, $element, $options);
    }
    else {
      return parent::buildExportHeader($element, $options);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildExportRecord(array $element, WebformSubmissionInterface $webform_submission, array $export_options) {
    $element_options = (isset($element['#options'])) ? $element['#options'] : [];
    $options_format = ($element['#webform_multiple'] ? $export_options['options_multiple_format'] : $export_options['options_single_format']);
    if ($options_format == 'separate') {
      $value = $this->getRawValue($element, $webform_submission);

      $record = [];
      // Combine the values so that isset can be used instead of in_array().
      // http://stackoverflow.com/questions/13483219/what-is-faster-in-array-or-isset
      $deltas = FALSE;
      if (is_array($value)) {
        $value = array_combine($value, $value);
        $deltas = ($this->exportDelta) ? array_flip(array_values($value)) : FALSE;
      }
      // Separate multiple values (i.e. options).
      foreach ($element_options as $option_value => $option_text) {
        if ((is_array($value) && isset($value[$option_value])) || ($value == $option_value)) {
          $record[] = ($deltas) ? ($deltas[$option_value] + 1) : 'X';
        }
        else {
          $record[] = '';
        }
      }
      return $record;
    }
    else {
      if ($export_options['options_item_format'] == 'key') {
        $element['#format'] = 'raw';
      }
      return parent::buildExportRecord($element, $webform_submission, $export_options);
    }
  }

  /**
   * Form API callback. Remove unchecked options from value array.
   */
  public static function validateMultipleOptions(array &$element, FormStateInterface $form_state, array &$completed_form) {
    $name = $element['#name'];
    $values = $form_state->getValue($name) ?: [];
    // Filter unchecked/unselected options whose value is 0.
    $values = array_filter($values, function ($value) {
      return $value !== 0;
    });
    $values = array_values($values);
    $form_state->setValue($name, $values);
  }

  /**
   * {@inheritdoc}
   */
  protected function getElementSelectorInputsOptions(array $element) {
    $plugin_id = $this->getPluginId();
    if (preg_match('/webform_(select|radios|checkboxes|buttons)_other$/', $plugin_id, $match)) {
      list($type) = explode(' ', $this->getPluginLabel());
      $title = $this->getAdminLabel($element);
      $name = $match[1];

      $inputs = [];
      $inputs[$name] = $title . ' [' . $type . ']';
      $inputs['other'] = $title . ' [' . $this->t('Text field') . ']';
      return $inputs;
    }
    else {
      return [];
    }
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\webform\Entity\Webform::getElementsSelectorOptions
   */
  public function getElementSelectorOptions(array $element) {
    if ($this->hasMultipleValues($element) && $this->hasMultipleWrapper()) {
      return [];
    }

    $plugin_id = $this->getPluginId();
    $title = $this->getAdminLabel($element) . ' [' . $this->getPluginLabel() . ']';
    $name = $element['#webform_key'];

    if ($inputs = $this->getElementSelectorInputsOptions($element)) {
      $selectors = [];
      foreach ($inputs as $input_name => $input_title) {
        $multiple = ($this->hasMultipleValues($element) && $input_name === 'select') ? '[]' : '';
        $selectors[":input[name=\"{$name}[{$input_name}]$multiple\"]"] = $input_title;
      }
      return [$title => $selectors];
    }
    else {
      $multiple = ($this->hasMultipleValues($element) && strpos($plugin_id, 'select') !== FALSE) ? '[]' : '';
      return [":input[name=\"$name$multiple\"]" => $title];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getElementSelectorInputValue($selector, $trigger, array $element, WebformSubmissionInterface $webform_submission) {
    if ($this->isOptionsOther()) {
      $input_name = WebformSubmissionConditionsValidator::getSelectorInputName($selector);
      $other_type = WebformSubmissionConditionsValidator::getInputNameAsArray($input_name, 1);
      $value = $this->getRawValue($element, $webform_submission);
      if ($other_type === 'other') {
        if ($this->hasMultipleValues($element)) {
          $other_value = array_diff($value, array_keys($element['#options']));
          return ($other_value) ? implode(', ', $other_value) : NULL;
        }
        else {
          // Make sure other value is not valid option.
          return ($value && !isset($element['#options'][$value])) ? $value : NULL;
        }
      }
      else {
        if ($this->hasMultipleValues($element)) {
          // Return array of valid #options.
          return array_intersect($value, array_keys($element['#options']));
        }
        else {
          // Return valid #option.
          return (isset($element['#options'][$value])) ? $value : NULL;
        }
      }
    }
    else {
      return parent::getElementSelectorInputValue($selector, $trigger, $element, $webform_submission);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['default']['default_value']['#description'] = $this->t('The default value of the field identified by its key.');

    // Issue #2836374: Wrapper attributes are not supported by composite
    // elements, this includes radios, checkboxes, and buttons.
    if (preg_match('/(radios|checkboxes|buttons)/', $this->getPluginId())) {
      $t_args = [
        '@name' => Unicode::strtolower($this->getPluginLabel()),
        ':href' => 'https://www.drupal.org/node/2836364',
      ];
      $form['element_attributes']['#description'] = $this->t('Please note: That the below custom element attributes will also be applied to the @name fieldset wrapper. (<a href=":href">Issue #2836374</a>)', $t_args);
    }
    // Options.
    $form['options'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Element options'),
      '#open' => TRUE,
    ];
    $form['options']['options'] = [
      '#type' => 'webform_element_options',
      '#title' => $this->t('Options'),
      '#options_description' => $this->hasProperty('options_description_display'),
      '#required' => TRUE,
    ];

    $form['options']['options_display_container'] = $this->getFormInlineContainer();
    $form['options']['options_display_container']['options_display'] = [
      '#title' => $this->t('Options display'),
      '#type' => 'select',
      '#options' => [
        'one_column' => $this->t('One column'),
        'two_columns' => $this->t('Two columns'),
        'three_columns' => $this->t('Three columns'),
        'side_by_side' => $this->t('Side by side'),
      ],
    ];
    $form['options']['options_display_container']['options_description_display'] = [
      '#title' => $this->t('Options description display'),
      '#type' => 'select',
      '#options' => [
        'description' => $this->t('Description'),
        'help' => $this->t('Help text'),
      ],
    ];
    $form['options']['empty_option'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Empty option label'),
      '#description' => $this->t('The label to show for the initial option denoting no selection in a select element.'),
      '#states' => [
        'visible' => [
          ':input[name="properties[multiple][container][cardinality]"]' => ['value' => 'number'],
          ':input[name="properties[multiple][container][cardinality_number]"]' => ['value' => 1],
        ],
      ],
    ];
    $default_empty_option = $this->configFactory->get('webform.settings')->get('element.default_empty_option');
    if ($default_empty_option) {
      $default_empty_option_required = $this->configFactory->get('webform.settings')->get('element.default_empty_option_required') ?: $this->t('- Select -');
      $form['options']['empty_option']['#description'] .= '<br />' . $this->t('Required elements default to: %required', ['%required' => $default_empty_option_required]);
      $default_empty_option_optional = $this->configFactory->get('webform.settings')->get('element.default_empty_option_optional') ?: $this->t('- None -');
      $form['options']['empty_option']['#description'] .= '<br />' . $this->t('Optional elements default to: %optional', ['%optional' => $default_empty_option_optional]);
    }
    $form['options']['empty_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Empty option value'),
      '#description' => $this->t('The value for the initial option denoting no selection in a select element, which is used to determine whether the user submitted a value or not.'),
      '#states' => [
        'visible' => [
          ':input[name="properties[multiple][container][cardinality]"]' => ['value' => 'number'],
          ':input[name="properties[multiple][container][cardinality_number]"]' => ['value' => 1],
        ],
      ],
    ];

    $form['options']['options_randomize'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Randomize options'),
      '#description' => $this->t('Randomizes the order of the options when they are displayed in the webform.'),
      '#return_value' => TRUE,
    ];

    // Other.
    $states_textfield_or_number = [
      'visible' => [
        [':input[name="properties[other__type]"]' => ['value' => 'textfield']],
        'or',
        [':input[name="properties[other__type]"]' => ['value' => 'number']],
      ],
    ];
    $states_textarea = [
      'visible' => [
        ':input[name="properties[other__type]"]' => ['value' => 'textarea'],
      ],
    ];
    $states_number = [
      'visible' => [
        ':input[name="properties[other__type]"]' => ['value' => 'number'],
      ],
    ];
    $form['options_other'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Other option settings'),
    ];
    $form['options_other']['other__type'] = [
      '#type' => 'select',
      '#title' => $this->t('Other type'),
      '#options' => [
        'textfield' => $this->t('Text field'),
        'textarea' => $this->t('Textarea'),
        'number' => $this->t('Number'),
      ],
    ];
    $form['options_other']['other__option_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Other option label'),
    ];
    $form['options_other']['other__title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Other title'),
    ];
    $form['options_other']['other__placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Other placeholder'),
    ];
    $form['options_other']['other__description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Other description'),
    ];

    $form['options_other']['other__field_container'] = $this->getFormInlineContainer();
    $form['options_other']['other__field_container']['other__field_prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Other field prefix'),
      '#description' => $this->t('Text or code that is placed directly in front of the input. This can be used to prefix an input with a constant string. Examples: $, #, -.'),
      '#size' => 10,
      '#states' => $states_textfield_or_number,
    ];
    $form['options_other']['other__field_container']['other__field_suffix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Other field suffix'),
      '#description' => $this->t('Text or code that is placed directly after the input. This can be used to add a unit to an input. Examples: lb, kg, %.'),
      '#size' => 10,
      '#states' => $states_textfield_or_number,
    ];
    $form['options_other']['other__size_container'] = $this->getFormInlineContainer();
    $form['options_other']['other__size_container']['other__size'] = [
      '#type' => 'number',
      '#title' => $this->t('Other size'),
      '#description' => $this->t('Leaving blank will use the default size.'),
      '#min' => 1,
      '#size' => 4,
      '#states' => $states_textfield_or_number,
    ];
    $form['options_other']['other__size_container']['other__maxlength'] = [
      '#type' => 'number',
      '#title' => $this->t('Other maxlength'),
      '#description' => $this->t('Leaving blank will use the default maxlength.'),
      '#min' => 1,
      '#size' => 4,
      '#states' => $states_textfield_or_number,
    ];
    $form['options_other']['other__size_container']['other__rows'] = [
      '#type' => 'number',
      '#title' => $this->t('Other rows'),
      '#description' => $this->t('Leaving blank will use the default rows.'),
      '#min' => 1,
      '#size' => 4,
      '#states' => $states_textarea,
    ];

    $form['options_other']['other__number_container'] = $this->getFormInlineContainer();
    $form['options_other']['other__number_container']['other__min'] = [
      '#type' => 'number',
      '#title' => $this->t('Other min'),
      '#description' => $this->t('Specifies the minimum value.'),
      '#step' => 'any',
      '#size' => 4,
      '#states' => $states_number,
    ];
    $form['options_other']['other__number_container']['other__max'] = [
      '#type' => 'number',
      '#title' => $this->t('Other max'),
      '#description' => $this->t('Specifies the maximum value.'),
      '#step' => 'any',
      '#size' => 4,
      '#states' => $states_number,
    ];
    $form['options_other']['other__number_container']['other__step'] = [
      '#type' => 'number',
      '#title' => $this->t('Other steps'),
      '#description' => $this->t('Specifies the legal number intervals. Leave blank to support any number interval.'),
      '#step' => 'any',
      '#size' => 4,
      '#states' => $states_number,
    ];

    // Add hide/show #format_items based on #multiple.
    if ($this->supportsMultipleValues() && $this->hasProperty('multiple')) {
      $form['display']['format_items']['#states'] = [
        'visible' => [
          [':input[name="properties[multiple]"]' => ['checked' => TRUE]],
        ],
      ];
    }

    return $form;
  }

}
