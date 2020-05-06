<?php

namespace Drupal\Tests\webform\Functional\Element;

use Drupal\Core\Serialization\Yaml;
use Drupal\webform\Utility\WebformElementHelper;

/**
 * Tests for webform element properties.
 *
 * @group Webform
 */
class WebformElementPluginPropertiesTest extends WebformElementBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'address',
    'captcha',
    'image',
    'taxonomy',
    'webform',
    'webform_attachment',
    'webform_entity_print_attachment',
    'webform_image_select',
    'webform_location_geocomplete',
    'webform_options_custom',
    'webform_toggles',
  ];

  /**
   * Debug dump the element's properties as YAML.
   *
   * @var bool
   */
  protected $debug = FALSE;

  /**
   * Test element default properties.
   */
  public function testElementDefaultProperties() {
    // Comparing all element's expected and actual default properties ensures
    // that there are not unexpected changes to any element's
    // default properties.
    $expected_elements = $this->getExpectedElementDefaultProperties();
    $actual_elements = $this->getActualElementDefaultProperties();
    $this->htmlOutput('<pre>' . htmlentities(Yaml::encode($actual_elements)) . '</pre>');
    foreach ($actual_elements as $element_key => $actual_element) {
      if ($expected_elements[$element_key] != $actual_element) {
        $this->htmlOutput('<pre>' . Yaml::encode([$element_key => $actual_element]) . '</pre>');
      }
      $this->assertEquals($expected_elements[$element_key], $actual_element, "Expected and actual '$element_key' element properties match.");
    }
  }

  /**
   * Get actual element default properties.
   *
   * @return array
   *   Expected element default properties.
   */
  protected function getActualElementDefaultProperties() {
    /** @var \Drupal\webform\Plugin\WebformElementManagerInterface $webform_element_manager */
    $webform_element_manager = \Drupal::service('plugin.manager.webform.element');
    /** @var \Drupal\webform\Plugin\WebformElementInterface[] $webform_elements */
    $webform_elements = $webform_element_manager->getInstances();

    $properties = [];
    foreach ($webform_elements as $element_key => $webform_element) {
      $default_properties = $webform_element->getDefaultProperties();
      if (!$webform_element->supportsMultipleValues()
        && isset($default_properties['format_items'])) {
        throw new \Exception("'$element_key' does not support multiple value but has '#format_items' property.");
      }
      ksort($default_properties);
      $properties[$webform_element->getPluginId()] = $default_properties;
    }
    ksort($properties);
    WebformElementHelper::convertRenderMarkupToStrings($properties);
    return $properties;
  }

  /**
   * Get expected element default properties.
   *
   * @return array
   *   Expected element default properties.
   */
  protected function getExpectedElementDefaultProperties() {
    $yaml = <<<YAML
address:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  available_countries: {  }
  default_value: {  }
  description: ''
  description_display: ''
  field_overrides: {  }
  flex: 1
  format: value
  format_html: ''
  format_items: ul
  format_items_html: ''
  format_items_text: ''
  format_text: ''
  help: ''
  help_display: ''
  help_title: ''
  langcode_override: ''
  more: ''
  more_title: ''
  multiple: false
  multiple__add_more: true
  multiple__add_more_button_label: Add
  multiple__add_more_input: true
  multiple__add_more_input_label: 'more items'
  multiple__add_more_items: 1
  multiple__empty_items: 1
  multiple__header_label: ''
  multiple__min_items: ''
  multiple__no_items_message: 'No items entered. Please add items below.'
  multiple__operations: true
  multiple__sorting: true
  prepopulate: false
  private: false
  required: false
  states: {  }
  states_clear: true
  title: ''
  title_display: invisible
captcha:
  captcha_admin_mode: false
  captcha_description: ''
  captcha_title: ''
  captcha_type: default
  flex: 1
checkbox:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  attributes: {  }
  default_value: false
  description: ''
  description_display: ''
  disabled: false
  exclude_empty: false
  field_prefix: ''
  field_suffix: ''
  flex: 1
  format: value
  format_attributes: {  }
  format_html: ''
  format_text: ''
  help: ''
  help_display: ''
  help_title: ''
  label_attributes: {  }
  more: ''
  more_title: ''
  prepopulate: false
  private: false
  return_value: ''
  required: false
  required_error: ''
  states: {  }
  states_clear: true
  title: ''
  title_display: after
  wrapper_attributes: {  }
checkboxes:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  attributes: {  }
  default_value: ''
  description: ''
  description_display: ''
  disabled: false
  field_prefix: ''
  field_suffix: ''
  flex: 1
  format: value
  format_attributes: {  }
  format_html: ''
  format_items: comma
  format_items_html: ''
  format_items_text: ''
  format_text: ''
  help: ''
  help_display: ''
  help_title: ''
  label_attributes: {  }
  more: ''
  more_title: ''
  multiple: true
  multiple_error: ''
  options: {  }
  options__properties: {  }
  options_description_display: description
  options_display: one_column
  options_randomize: false
  prepopulate: false
  private: false
  required: false
  required_error: ''
  states: {  }
  states_clear: true
  title: ''
  title_display: ''
  unique: false
  unique_entity: false
  unique_error: ''
  unique_user: false
  wrapper_attributes: {  }
  wrapper_type: fieldset
color:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  attributes: {  }
  color_size: medium
  default_value: ''
  description: ''
  description_display: ''
  disabled: false
  field_prefix: ''
  field_suffix: ''
  flex: 1
  format: swatch
  format_attributes: {  }
  format_html: ''
  format_text: ''
  help: ''
  help_display: ''
  help_title: ''
  label_attributes: {  }
  more: ''
  more_title: ''
  prepopulate: false
  private: false
  required: false
  required_error: ''
  states: {  }
  states_clear: true
  title: ''
  title_display: ''
  unique: false
  unique_entity: false
  unique_error: ''
  unique_user: false
  wrapper_attributes: {  }
container:
  attributes: {  }
  flex: 1
  format: header
  format_attributes: {  }
  format_html: ''
  format_text: ''
  randomize: false
  states: {  }
  states_clear: true
date:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  attributes: {  }
  date_date_format: Y-m-d
  date_date_max: ''
  date_date_min: ''
  date_days:
    - '0'
    - '1'
    - '2'
    - '3'
    - '4'
    - '5'
    - '6'
  datepicker: false
  datepicker_button: false
  default_value: ''
  description: ''
  description_display: ''
  disabled: false
  field_prefix: ''
  field_suffix: ''
  flex: 1
  format: fallback
  format_attributes: {  }
  format_html: ''
  format_items: ul
  format_items_html: ''
  format_items_text: ''
  format_text: ''
  help: ''
  help_display: ''
  help_title: ''
  label_attributes: {  }
  more: ''
  more_title: ''
  multiple: false
  multiple__add_more: true
  multiple__add_more_button_label: Add
  multiple__add_more_input: true
  multiple__add_more_input_label: 'more items'
  multiple__add_more_items: 1
  multiple__empty_items: 1
  multiple__header_label: ''
  multiple__min_items: ''
  multiple__no_items_message: 'No items entered. Please add items below.'
  multiple__operations: true
  multiple__sorting: true
  placeholder: ''
  prepopulate: false
  private: false
  required: false
  required_error: ''
  size: ''
  states: {  }
  states_clear: true
  step: ''
  title: ''
  title_display: ''
  unique: false
  unique_entity: false
  unique_error: ''
  unique_user: false
  wrapper_attributes: {  }
datelist:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  attributes: {  }
  date_abbreviate: true
  date_date_max: ''
  date_date_min: ''
  date_days:
    - '0'
    - '1'
    - '2'
    - '3'
    - '4'
    - '5'
    - '6'
  date_increment: 1
  date_max: ''
  date_min: ''
  date_part_order:
    - year
    - month
    - day
    - hour
    - minute
  date_text_parts: {  }
  date_year_range: '1900:2050'
  date_year_range_reverse: false
  default_value: ''
  description: ''
  description_display: ''
  disabled: false
  field_prefix: ''
  field_suffix: ''
  flex: 1
  format: fallback
  format_attributes: {  }
  format_html: ''
  format_items: ul
  format_items_html: ''
  format_items_text: ''
  format_text: ''
  help: ''
  help_display: ''
  help_title: ''
  label_attributes: {  }
  more: ''
  more_title: ''
  multiple: false
  multiple__add_more: true
  multiple__add_more_button_label: Add
  multiple__add_more_input: true
  multiple__add_more_input_label: 'more items'
  multiple__add_more_items: 1
  multiple__empty_items: 1
  multiple__header_label: ''
  multiple__min_items: ''
  multiple__no_items_message: 'No items entered. Please add items below.'
  multiple__operations: true
  multiple__sorting: true
  prepopulate: false
  private: false
  required: false
  required_error: ''
  states: {  }
  states_clear: true
  title: ''
  title_display: ''
  unique: false
  unique_entity: false
  unique_error: ''
  unique_user: false
  wrapper_attributes: {  }
datetime:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  attributes: {  }
  date_date_datepicker_button: false
  date_date_element: date
  date_date_format: Y-m-d
  date_date_max: ''
  date_date_min: ''
  date_date_placeholder: ''
  date_days:
    - '0'
    - '1'
    - '2'
    - '3'
    - '4'
    - '5'
    - '6'
  date_max: ''
  date_min: ''
  date_time_element: time
  date_time_format: 'H:i:s'
  date_time_max: ''
  date_time_min: ''
  date_time_placeholder: ''
  date_time_step: ''
  date_year_range: '1900:2050'
  default_value: ''
  description: ''
  description_display: ''
  disabled: false
  field_prefix: ''
  field_suffix: ''
  flex: 1
  format: fallback
  format_attributes: {  }
  format_html: ''
  format_items: ul
  format_items_html: ''
  format_items_text: ''
  format_text: ''
  help: ''
  help_display: ''
  help_title: ''
  label_attributes: {  }
  more: ''
  more_title: ''
  multiple: false
  multiple__add_more: true
  multiple__add_more_button_label: Add
  multiple__add_more_input: true
  multiple__add_more_input_label: 'more items'
  multiple__add_more_items: 1
  multiple__empty_items: 1
  multiple__header_label: ''
  multiple__min_items: ''
  multiple__no_items_message: 'No items entered. Please add items below.'
  multiple__operations: true
  multiple__sorting: true
  prepopulate: false
  private: false
  required: false
  required_error: ''
  states: {  }
  states_clear: true
  title: ''
  title_display: ''
  unique: false
  unique_entity: false
  unique_error: ''
  unique_user: false
  wrapper_attributes: {  }
details:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  attributes: {  }
  description: ''
  flex: 1
  format: details
  format_attributes: {  }
  format_html: ''
  format_text: ''
  help: ''
  help_display: ''
  help_title: ''
  more: ''
  more_title: ''
  open: false
  private: false
  randomize: false
  required: false
  states: {  }
  states_clear: true
  summary_attributes: {  }
  title: ''
  title_display: ''
email:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  attributes: {  }
  autocomplete: 'on'
  default_value: ''
  description: ''
  description_display: ''
  disabled: false
  field_prefix: ''
  field_suffix: ''
  flex: 1
  format: link
  format_attributes: {  }
  format_html: ''
  format_items: ul
  format_items_html: ''
  format_items_text: ''
  format_text: ''
  help: ''
  help_display: ''
  help_title: ''
  input_hide: false
  label_attributes: {  }
  maxlength: ''
  minlength: ''
  more: ''
  more_title: ''
  multiple: false
  multiple__add_more: true
  multiple__add_more_button_label: Add
  multiple__add_more_input: true
  multiple__add_more_input_label: 'more items'
  multiple__add_more_items: 1
  multiple__empty_items: 1
  multiple__header_label: ''
  multiple__min_items: ''
  multiple__no_items_message: 'No items entered. Please add items below.'
  multiple__operations: true
  multiple__sorting: true
  pattern: ''
  pattern_error: ''
  placeholder: ''
  prepopulate: false
  private: false
  readonly: false
  required: false
  required_error: ''
  size: ''
  states: {  }
  states_clear: true
  title: ''
  title_display: ''
  unique: false
  unique_entity: false
  unique_error: ''
  unique_user: false
  wrapper_attributes: {  }
entity_autocomplete:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  attributes: {  }
  default_value: ''
  description: ''
  description_display: ''
  disabled: false
  field_prefix: ''
  field_suffix: ''
  flex: 1
  format: link
  format_attributes: {  }
  format_html: ''
  format_items: ul
  format_items_html: ''
  format_items_text: ''
  format_text: ''
  help: ''
  help_display: ''
  help_title: ''
  label_attributes: {  }
  more: ''
  more_title: ''
  multiple: false
  multiple__add_more: true
  multiple__add_more_button_label: Add
  multiple__add_more_input: true
  multiple__add_more_input_label: 'more items'
  multiple__add_more_items: 1
  multiple__empty_items: 1
  multiple__header_label: ''
  multiple__min_items: ''
  multiple__no_items_message: 'No items entered. Please add items below.'
  multiple__operations: true
  multiple__sorting: true
  prepopulate: false
  private: false
  required: false
  required_error: ''
  selection_handler: default
  selection_settings: {  }
  states: {  }
  states_clear: true
  tags: false
  target_type: ''
  title: ''
  title_display: ''
  unique: false
  unique_entity: false
  unique_error: ''
  unique_user: false
  wrapper_attributes: {  }
fieldset:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  attributes: {  }
  description: ''
  description_display: ''
  flex: 1
  format: fieldset
  format_attributes: {  }
  format_html: ''
  format_text: ''
  help: ''
  help_display: ''
  help_title: ''
  more: ''
  more_title: ''
  private: false
  randomize: false
  required: false
  states: {  }
  states_clear: true
  title: ''
  title_display: ''
hidden:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  default_value: ''
  prepopulate: false
  private: false
  title: ''
item:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  description: ''
  description_display: ''
  display_on: form
  field_prefix: ''
  field_suffix: ''
  flex: 1
  help: ''
  help_display: ''
  help_title: ''
  markup: ''
  more: ''
  more_title: ''
  private: false
  required: false
  states: {  }
  title: ''
  title_display: ''
  wrapper_attributes: {  }
label:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  attributes: {  }
  description: ''
  flex: 1
  private: false
  required: false
  states: {  }
  title: ''
language_select:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  attributes: {  }
  default_value: ''
  description: ''
  description_display: ''
  disabled: false
  field_prefix: ''
  field_suffix: ''
  flex: 1
  format: text
  format_attributes: {  }
  format_html: ''
  format_text: ''
  help: ''
  help_display: ''
  help_title: ''
  label_attributes: {  }
  more: ''
  more_title: ''
  prepopulate: false
  private: false
  required: false
  required_error: ''
  states: {  }
  states_clear: true
  title: ''
  title_display: ''
  unique: false
  unique_entity: false
  unique_error: ''
  unique_user: false
  wrapper_attributes: {  }
machine_name:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  attributes: {  }
  default_value: ''
  description: ''
  description_display: ''
  disabled: false
  field_prefix: ''
  field_suffix: ''
  flex: 1
  format: value
  format_attributes: {  }
  format_html: ''
  format_text: ''
  help: ''
  help_display: ''
  help_title: ''
  label_attributes: {  }
  more: ''
  more_title: ''
  prepopulate: false
  private: false
  required: false
  required_error: ''
  states: {  }
  states_clear: true
  title: ''
  title_display: ''
  unique: false
  unique_entity: false
  unique_error: ''
  unique_user: false
  wrapper_attributes: {  }
managed_file:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  attributes: {  }
  button: false
  button__attributes: {  }
  button__title: ''
  default_value: ''
  description: ''
  description_display: ''
  disabled: false
  field_prefix: ''
  field_suffix: ''
  file_extensions: 'gif jpg jpeg png bmp eps tif pict psd txt rtf html odf pdf doc docx ppt pptx xls xlsx xml avi mov mp3 mp4 ogg wav bz2 dmg gz jar rar sit svg tar zip'
  file_help: ''
  file_name: ''
  file_placeholder: ''
  file_preview: ''
  flex: 1
  format: file
  format_attributes: {  }
  format_html: ''
  format_items: ul
  format_items_html: ''
  format_items_text: ''
  format_text: ''
  help: ''
  help_display: ''
  help_title: ''
  label_attributes: {  }
  max_filesize: ''
  more: ''
  more_title: ''
  multiple: false
  private: false
  required: false
  required_error: ''
  sanitize: false
  states: {  }
  states_clear: true
  title: ''
  title_display: ''
  unique: false
  unique_entity: false
  unique_error: ''
  unique_user: false
  uri_scheme: private
  wrapper_attributes: {  }
number:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  attributes: {  }
  autocomplete: 'on'
  default_value: ''
  description: ''
  description_display: ''
  disabled: false
  field_prefix: ''
  field_suffix: ''
  flex: 1
  format: value
  format_attributes: {  }
  format_html: ''
  format_items: ul
  format_items_html: ''
  format_items_text: ''
  format_text: ''
  help: ''
  help_display: ''
  help_title: ''
  label_attributes: {  }
  max: ''
  min: ''
  more: ''
  more_title: ''
  multiple: false
  multiple__add_more: true
  multiple__add_more_button_label: Add
  multiple__add_more_input: true
  multiple__add_more_input_label: 'more items'
  multiple__add_more_items: 1
  multiple__empty_items: 1
  multiple__header_label: ''
  multiple__min_items: ''
  multiple__no_items_message: 'No items entered. Please add items below.'
  multiple__operations: true
  multiple__sorting: true
  placeholder: ''
  prepopulate: false
  private: false
  readonly: false
  required: false
  required_error: ''
  size: ''
  states: {  }
  states_clear: true
  step: ''
  title: ''
  title_display: ''
  unique: false
  unique_entity: false
  unique_error: ''
  unique_user: false
  wrapper_attributes: {  }
processed_text:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  display_on: form
  flex: 1
  format: plain_text
  label_attributes: {  }
  private: false
  states: {  }
  text: ''
  wrapper_attributes: {  }
radios:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  attributes: {  }
  default_value: ''
  description: ''
  description_display: ''
  disabled: false
  field_prefix: ''
  field_suffix: ''
  flex: 1
  format: value
  format_attributes: {  }
  format_html: ''
  format_text: ''
  help: ''
  help_display: ''
  help_title: ''
  label_attributes: {  }
  more: ''
  more_title: ''
  options: {  }
  options__properties: {  }
  options_description_display: description
  options_display: one_column
  options_randomize: false
  prepopulate: false
  private: false
  required: false
  required_error: ''
  states: {  }
  states_clear: true
  title: ''
  title_display: ''
  unique: false
  unique_entity: false
  unique_error: ''
  unique_user: false
  wrapper_attributes: {  }
  wrapper_type: fieldset
range:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  attributes: {  }
  default_value: ''
  description: ''
  description_display: ''
  disabled: false
  field_prefix: ''
  field_suffix: ''
  flex: 1
  format: value
  format_attributes: {  }
  format_html: ''
  format_text: ''
  help: ''
  help_display: ''
  help_title: ''
  label_attributes: {  }
  max: 100
  min: 0
  more: ''
  more_title: ''
  output: ''
  output__attributes: {  }
  output__field_prefix: ''
  output__field_suffix: ''
  prepopulate: false
  private: false
  readonly: false
  required: false
  required_error: ''
  states: {  }
  states_clear: true
  step: 1
  title: ''
  title_display: ''
  unique: false
  unique_entity: false
  unique_error: ''
  unique_user: false
  wrapper_attributes: {  }
search:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  attributes: {  }
  autocomplete: 'on'
  default_value: ''
  description: ''
  description_display: ''
  disabled: false
  field_prefix: ''
  field_suffix: ''
  flex: 1
  format: value
  format_attributes: {  }
  format_html: ''
  format_text: ''
  help: ''
  help_display: ''
  help_title: ''
  label_attributes: {  }
  maxlength: ''
  minlength: ''
  more: ''
  more_title: ''
  pattern: ''
  pattern_error: ''
  placeholder: ''
  prepopulate: false
  private: false
  readonly: false
  required: false
  required_error: ''
  size: ''
  states: {  }
  states_clear: true
  title: ''
  title_display: ''
  unique: false
  unique_entity: false
  unique_error: ''
  unique_user: false
  wrapper_attributes: {  }
select:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  attributes: {  }
  choices: false
  chosen: false
  default_value: ''
  description: ''
  description_display: ''
  disabled: false
  empty_option: ''
  empty_value: ''
  field_prefix: ''
  field_suffix: ''
  flex: 1
  format: value
  format_attributes: {  }
  format_html: ''
  format_items: comma
  format_items_html: ''
  format_items_text: ''
  format_text: ''
  help: ''
  help_display: ''
  help_title: ''
  label_attributes: {  }
  more: ''
  more_title: ''
  multiple: false
  multiple_error: ''
  options: {  }
  options_randomize: false
  placeholder: ''
  prepopulate: false
  private: false
  required: false
  required_error: ''
  select2: false
  size: ''
  states: {  }
  states_clear: true
  title: ''
  title_display: ''
  unique: false
  unique_entity: false
  unique_error: ''
  unique_user: false
  wrapper_attributes: {  }
table:
  empty: ''
  header: {  }
tableselect:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  attributes: {  }
  default_value: ''
  description: ''
  description_display: ''
  disabled: false
  field_prefix: ''
  field_suffix: ''
  flex: 1
  format: value
  format_attributes: {  }
  format_html: ''
  format_items: comma
  format_items_html: ''
  format_items_text: ''
  format_text: ''
  help: ''
  help_display: ''
  help_title: ''
  js_select: true
  label_attributes: {  }
  more: ''
  more_title: ''
  multiple: true
  multiple_error: ''
  options: {  }
  options_randomize: false
  prepopulate: false
  private: false
  required: false
  required_error: ''
  states: {  }
  states_clear: true
  title: ''
  title_display: ''
  unique: false
  unique_entity: false
  unique_error: ''
  unique_user: false
  wrapper_attributes: {  }
tel:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  attributes: {  }
  autocomplete: 'on'
  default_value: ''
  description: ''
  description_display: ''
  disabled: false
  field_prefix: ''
  field_suffix: ''
  flex: 1
  format: link
  format_attributes: {  }
  format_html: ''
  format_items: ul
  format_items_html: ''
  format_items_text: ''
  format_text: ''
  help: ''
  help_display: ''
  help_title: ''
  input_hide: false
  international: false
  international_initial_country: ''
  international_preferred_countries: {  }
  label_attributes: {  }
  maxlength: null
  minlength: null
  more: ''
  more_title: ''
  multiple: false
  multiple__add_more: true
  multiple__add_more_button_label: Add
  multiple__add_more_input: true
  multiple__add_more_input_label: 'more items'
  multiple__add_more_items: 1
  multiple__empty_items: 1
  multiple__header_label: ''
  multiple__min_items: null
  multiple__no_items_message: 'No items entered. Please add items below.'
  multiple__operations: true
  multiple__sorting: true
  pattern: ''
  pattern_error: ''
  placeholder: ''
  prepopulate: false
  private: false
  readonly: false
  required: false
  required_error: ''
  size: null
  states: {  }
  states_clear: true
  title: ''
  title_display: ''
  unique: false
  unique_entity: false
  unique_error: ''
  unique_user: false
  wrapper_attributes: {  }
text_format:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  allowed_formats: {  }
  default_value: {  }
  description: ''
  flex: 1
  format: value
  format_attributes: {  }
  format_html: ''
  format_text: ''
  help: ''
  help_display: ''
  help_title: ''
  hide_help: false
  label_attributes: {  }
  more: ''
  more_title: ''
  prepopulate: false
  private: false
  required: false
  required_error: ''
  states: {  }
  states_clear: true
  title: ''
textarea:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  attributes: {  }
  autocomplete: 'on'
  counter_maximum: ''
  counter_maximum_message: ''
  counter_minimum: ''
  counter_minimum_message: ''
  counter_type: ''
  default_value: ''
  description: ''
  description_display: ''
  disabled: false
  field_prefix: ''
  field_suffix: ''
  flex: 1
  format: value
  format_attributes: {  }
  format_html: ''
  format_items: ul
  format_items_html: ''
  format_items_text: ''
  format_text: ''
  help: ''
  help_display: ''
  help_title: ''
  label_attributes: {  }
  maxlength: ''
  minlength: ''
  more: ''
  more_title: ''
  multiple: false
  multiple__add_more: true
  multiple__add_more_button_label: Add
  multiple__add_more_input: true
  multiple__add_more_input_label: 'more items'
  multiple__add_more_items: 1
  multiple__empty_items: 1
  multiple__header_label: ''
  multiple__min_items: ''
  multiple__no_items_message: 'No items entered. Please add items below.'
  multiple__operations: true
  multiple__sorting: true
  pattern: ''
  pattern_error: ''
  placeholder: ''
  prepopulate: false
  private: false
  readonly: false
  required: false
  required_error: ''
  rows: ''
  size: ''
  states: {  }
  states_clear: true
  title: ''
  title_display: ''
  unique: false
  unique_entity: false
  unique_error: ''
  unique_user: false
  wrapper_attributes: {  }
textfield:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  attributes: {  }
  autocomplete: 'on'
  counter_maximum: ''
  counter_maximum_message: ''
  counter_minimum: ''
  counter_minimum_message: ''
  counter_type: ''
  default_value: ''
  description: ''
  description_display: ''
  disabled: false
  field_prefix: ''
  field_suffix: ''
  flex: 1
  format: value
  format_attributes: {  }
  format_html: ''
  format_items: ul
  format_items_html: ''
  format_items_text: ''
  format_text: ''
  help: ''
  help_display: ''
  help_title: ''
  input_hide: false
  input_mask: ''
  label_attributes: {  }
  maxlength: ''
  minlength: ''
  more: ''
  more_title: ''
  multiple: false
  multiple__add_more: true
  multiple__add_more_button_label: Add
  multiple__add_more_input: true
  multiple__add_more_input_label: 'more items'
  multiple__add_more_items: 1
  multiple__empty_items: 1
  multiple__header_label: ''
  multiple__min_items: ''
  multiple__no_items_message: 'No items entered. Please add items below.'
  multiple__operations: true
  multiple__sorting: true
  pattern: ''
  pattern_error: ''
  placeholder: ''
  prepopulate: false
  private: false
  readonly: false
  required: false
  required_error: ''
  size: ''
  states: {  }
  states_clear: true
  title: ''
  title_display: ''
  unique: false
  unique_entity: false
  unique_error: ''
  unique_user: false
  wrapper_attributes: {  }
url:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  attributes: {  }
  autocomplete: 'on'
  default_value: ''
  description: ''
  description_display: ''
  disabled: false
  field_prefix: ''
  field_suffix: ''
  flex: 1
  format: link
  format_attributes: {  }
  format_html: ''
  format_items: ul
  format_items_html: ''
  format_items_text: ''
  format_text: ''
  help: ''
  help_display: ''
  help_title: ''
  input_hide: false
  label_attributes: {  }
  maxlength: ''
  minlength: ''
  more: ''
  more_title: ''
  multiple: false
  multiple__add_more: true
  multiple__add_more_button_label: Add
  multiple__add_more_input: true
  multiple__add_more_input_label: 'more items'
  multiple__add_more_items: 1
  multiple__empty_items: 1
  multiple__header_label: ''
  multiple__min_items: ''
  multiple__no_items_message: 'No items entered. Please add items below.'
  multiple__operations: true
  multiple__sorting: true
  pattern: ''
  pattern_error: ''
  placeholder: ''
  prepopulate: false
  private: false
  readonly: false
  required: false
  required_error: ''
  size: ''
  states: {  }
  states_clear: true
  title: ''
  title_display: ''
  unique: false
  unique_entity: false
  unique_error: ''
  unique_user: false
  wrapper_attributes: {  }
value:
  title: ''
  value: ''
view:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  arguments: {  }
  display_id: ''
  display_on: both
  flex: 1
  name: ''
  private: false
  states: {  }
webform_actions:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  attributes: {  }
  draft__attributes: {  }
  draft__label: ''
  draft_hide: false
  flex: 1
  preview_next__attributes: {  }
  preview_next__label: ''
  preview_next_hide: false
  preview_prev__attributes: {  }
  preview_prev__label: ''
  preview_prev_hide: false
  private: false
  reset__attributes: {  }
  reset__label: ''
  reset_hide: false
  states: {  }
  states_clear: true
  submit__attributes: {  }
  submit__label: ''
  submit_hide: false
  title: ''
  wizard_next__attributes: {  }
  wizard_next__label: ''
  wizard_next_hide: false
  wizard_prev__attributes: {  }
  wizard_prev__label: ''
  wizard_prev_hide: false
webform_address:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  address_2__access: true
  address_2__description: ''
  address_2__help: ''
  address_2__placeholder: ''
  address_2__required: false
  address_2__title: 'Address 2'
  address_2__title_display: ''
  address_2__type: textfield
  address__access: true
  address__description: ''
  address__help: ''
  address__placeholder: ''
  address__required: false
  address__title: Address
  address__title_display: ''
  address__type: textfield
  admin_title: ''
  attributes: {  }
  choices: false
  chosen: false
  city__access: true
  city__description: ''
  city__help: ''
  city__placeholder: ''
  city__required: false
  city__title: City/Town
  city__title_display: ''
  city__type: textfield
  country__access: true
  country__description: ''
  country__help: ''
  country__options: country_names
  country__placeholder: ''
  country__required: false
  country__title: Country
  country__title_display: ''
  country__type: select
  default_value: {  }
  description: ''
  description_display: ''
  disabled: false
  field_prefix: ''
  field_suffix: ''
  flex: 1
  flexbox: ''
  format: value
  format_attributes: {  }
  format_html: ''
  format_items: ul
  format_items_html: ''
  format_items_text: ''
  format_text: ''
  help: ''
  help_display: ''
  help_title: ''
  label_attributes: {  }
  more: ''
  more_title: ''
  multiple: false
  multiple__add_more: true
  multiple__add_more_button_label: Add
  multiple__add_more_input: true
  multiple__add_more_input_label: 'more items'
  multiple__add_more_items: 1
  multiple__empty_items: 1
  multiple__header: false
  multiple__header_label: ''
  multiple__min_items: ''
  multiple__no_items_message: 'No items entered. Please add items below.'
  multiple__operations: true
  multiple__sorting: true
  postal_code__access: true
  postal_code__description: ''
  postal_code__help: ''
  postal_code__placeholder: ''
  postal_code__required: false
  postal_code__title: 'ZIP/Postal Code'
  postal_code__title_display: ''
  postal_code__type: textfield
  prepopulate: false
  private: false
  required: false
  select2: false
  state_province__access: true
  state_province__description: ''
  state_province__help: ''
  state_province__options: state_province_names
  state_province__placeholder: ''
  state_province__required: false
  state_province__title: State/Province
  state_province__title_display: ''
  state_province__type: select
  states: {  }
  states_clear: true
  title: ''
  title_display: invisible
  wrapper_attributes: {  }
  wrapper_type: fieldset
webform_attachment_token:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  display_on: none
  download: false
  filename: ''
  flex: 1
  label_attributes: {  }
  link_title: ''
  private: false
  sanitize: false
  states: {  }
  template: ''
  title: ''
  title_display: ''
  trim: false
  wrapper_attributes: {  }
webform_attachment_twig:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  display_on: none
  download: false
  filename: ''
  flex: 1
  label_attributes: {  }
  link_title: ''
  private: false
  sanitize: false
  states: {  }
  template: ''
  title: ''
  title_display: ''
  trim: false
  wrapper_attributes: {  }
webform_attachment_url:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  display_on: none
  download: false
  filename: ''
  flex: 1
  label_attributes: {  }
  link_title: ''
  private: false
  sanitize: false
  states: {  }
  title: ''
  title_display: ''
  trim: false
  url: ''
  wrapper_attributes: {  }
webform_audio_file:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  attributes: {  }
  button: false
  button__attributes: {  }
  button__title: ''
  default_value: ''
  description: ''
  description_display: ''
  disabled: false
  field_prefix: ''
  field_suffix: ''
  file_extensions: 'mp3 ogg wav'
  file_help: ''
  file_name: ''
  file_placeholder: ''
  file_preview: ''
  flex: 1
  format: file
  format_attributes: {  }
  format_html: ''
  format_items: ul
  format_items_html: ''
  format_items_text: ''
  format_text: ''
  help: ''
  help_display: ''
  help_title: ''
  label_attributes: {  }
  max_filesize: ''
  more: ''
  more_title: ''
  multiple: false
  private: false
  required: false
  required_error: ''
  sanitize: false
  states: {  }
  states_clear: true
  title: ''
  title_display: ''
  unique: false
  unique_entity: false
  unique_error: ''
  unique_user: false
  uri_scheme: private
  wrapper_attributes: {  }
webform_autocomplete:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  attributes: {  }
  autocomplete_existing: false
  autocomplete_items: {  }
  autocomplete_limit: 10
  autocomplete_match: 3
  autocomplete_match_operator: CONTAINS
  counter_maximum: ''
  counter_maximum_message: ''
  counter_minimum: ''
  counter_minimum_message: ''
  counter_type: ''
  default_value: ''
  description: ''
  description_display: ''
  disabled: false
  field_prefix: ''
  field_suffix: ''
  flex: 1
  format: value
  format_attributes: {  }
  format_html: ''
  format_items: ul
  format_items_html: ''
  format_items_text: ''
  format_text: ''
  help: ''
  help_display: ''
  help_title: ''
  input_hide: false
  input_mask: ''
  label_attributes: {  }
  maxlength: ''
  minlength: ''
  more: ''
  more_title: ''
  multiple: false
  multiple__add_more: true
  multiple__add_more_button_label: Add
  multiple__add_more_input: true
  multiple__add_more_input_label: 'more items'
  multiple__add_more_items: 1
  multiple__empty_items: 1
  multiple__header_label: ''
  multiple__min_items: ''
  multiple__no_items_message: 'No items entered. Please add items below.'
  multiple__operations: true
  multiple__sorting: true
  pattern: ''
  pattern_error: ''
  placeholder: ''
  prepopulate: false
  private: false
  readonly: false
  required: false
  required_error: ''
  size: ''
  states: {  }
  states_clear: true
  title: ''
  title_display: ''
  unique: false
  unique_entity: false
  unique_error: ''
  unique_user: false
  wrapper_attributes: {  }
webform_checkboxes_other:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  attributes: {  }
  default_value: ''
  description: ''
  description_display: ''
  disabled: false
  field_prefix: ''
  field_suffix: ''
  flex: 1
  format: value
  format_attributes: {  }
  format_html: ''
  format_items: comma
  format_items_html: ''
  format_items_text: ''
  format_text: ''
  help: ''
  help_display: ''
  help_title: ''
  label_attributes: {  }
  more: ''
  more_title: ''
  multiple: true
  multiple_error: ''
  options: {  }
  options__properties: {  }
  options_description_display: description
  options_display: one_column
  options_randomize: false
  other__counter_maximum: ''
  other__counter_maximum_message: ''
  other__counter_minimum: ''
  other__counter_minimum_message: ''
  other__counter_type: ''
  other__description: ''
  other__field_prefix: ''
  other__field_suffix: ''
  other__max: ''
  other__maxlength: ''
  other__min: ''
  other__option_label: Other…
  other__placeholder: 'Enter other…'
  other__rows: ''
  other__size: ''
  other__step: ''
  other__title: ''
  other__type: textfield
  prepopulate: false
  private: false
  required: false
  required_error: ''
  states: {  }
  states_clear: true
  title: ''
  title_display: ''
  unique: false
  unique_entity: false
  unique_error: ''
  unique_user: false
  wrapper_attributes: {  }
  wrapper_type: fieldset
webform_codemirror:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  attributes: {  }
  default_value: ''
  description: ''
  description_display: ''
  disabled: false
  field_prefix: ''
  field_suffix: ''
  flex: 1
  format: code
  format_attributes: {  }
  format_html: ''
  format_text: ''
  help: ''
  help_display: ''
  help_title: ''
  label_attributes: {  }
  mode: text
  more: ''
  more_title: ''
  placeholder: ''
  prepopulate: false
  private: false
  required: false
  required_error: ''
  states: {  }
  states_clear: true
  title: ''
  title_display: ''
  unique: false
  unique_entity: false
  unique_error: ''
  unique_user: false
  wrap: true
  wrapper_attributes: {  }
webform_computed_token:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  ajax: false
  description: ''
  description_display: ''
  display_on: both
  flex: 1
  help: ''
  help_title: ''
  hide_empty: false
  label_attributes: {  }
  mode: auto
  more: ''
  more_title: ''
  prepopulate: false
  private: false
  states: {  }
  states_clear: true
  store: false
  template: ''
  title: ''
  title_display: ''
  wrapper_attributes: {  }
webform_computed_twig:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  ajax: false
  description: ''
  description_display: ''
  display_on: both
  flex: 1
  help: ''
  help_title: ''
  hide_empty: false
  label_attributes: {  }
  mode: auto
  more: ''
  more_title: ''
  prepopulate: false
  private: false
  states: {  }
  states_clear: true
  store: false
  template: ''
  title: ''
  title_display: ''
  whitespace: ''
  wrapper_attributes: {  }
webform_contact:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  address_2__access: true
  address_2__description: ''
  address_2__help: ''
  address_2__placeholder: ''
  address_2__required: false
  address_2__title: 'Address 2'
  address_2__title_display: ''
  address_2__type: textfield
  address__access: true
  address__description: ''
  address__help: ''
  address__placeholder: ''
  address__required: false
  address__title: Address
  address__title_display: ''
  address__type: textfield
  admin_title: ''
  attributes: {  }
  choices: false
  chosen: false
  city__access: true
  city__description: ''
  city__help: ''
  city__placeholder: ''
  city__required: false
  city__title: City/Town
  city__title_display: ''
  city__type: textfield
  company__access: true
  company__description: ''
  company__help: ''
  company__placeholder: ''
  company__required: false
  company__title: Company
  company__title_display: ''
  company__type: textfield
  country__access: true
  country__description: ''
  country__help: ''
  country__options: country_names
  country__placeholder: ''
  country__required: false
  country__title: Country
  country__title_display: ''
  country__type: select
  default_value: {  }
  description: ''
  description_display: ''
  disabled: false
  email__access: true
  email__description: ''
  email__help: ''
  email__placeholder: ''
  email__required: false
  email__title: Email
  email__title_display: ''
  email__type: email
  field_prefix: ''
  field_suffix: ''
  flex: 1
  flexbox: ''
  format: value
  format_attributes: {  }
  format_html: ''
  format_items: ul
  format_items_html: ''
  format_items_text: ''
  format_text: ''
  help: ''
  help_display: ''
  help_title: ''
  label_attributes: {  }
  more: ''
  more_title: ''
  multiple: false
  multiple__add_more: true
  multiple__add_more_button_label: Add
  multiple__add_more_input: true
  multiple__add_more_input_label: 'more items'
  multiple__add_more_items: 1
  multiple__empty_items: 1
  multiple__header: false
  multiple__header_label: ''
  multiple__min_items: ''
  multiple__no_items_message: 'No items entered. Please add items below.'
  multiple__operations: true
  multiple__sorting: true
  name__access: true
  name__description: ''
  name__help: ''
  name__placeholder: ''
  name__required: false
  name__title: Name
  name__title_display: ''
  name__type: textfield
  phone__access: true
  phone__description: ''
  phone__help: ''
  phone__placeholder: ''
  phone__required: false
  phone__title: Phone
  phone__title_display: ''
  phone__type: tel
  postal_code__access: true
  postal_code__description: ''
  postal_code__help: ''
  postal_code__placeholder: ''
  postal_code__required: false
  postal_code__title: 'ZIP/Postal Code'
  postal_code__title_display: ''
  postal_code__type: textfield
  prepopulate: false
  private: false
  required: false
  select2: false
  state_province__access: true
  state_province__description: ''
  state_province__help: ''
  state_province__options: state_province_names
  state_province__placeholder: ''
  state_province__required: false
  state_province__title: State/Province
  state_province__title_display: ''
  state_province__type: select
  states: {  }
  states_clear: true
  title: ''
  title_display: invisible
  wrapper_attributes: {  }
  wrapper_type: fieldset
webform_custom_composite:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  attributes: {  }
  choices: false
  chosen: false
  default_value: {  }
  description: ''
  description_display: ''
  disabled: false
  element: {  }
  field_prefix: ''
  field_suffix: ''
  flex: 1
  format: value
  format_attributes: {  }
  format_html: ''
  format_items: ul
  format_items_html: ''
  format_items_text: ''
  format_text: ''
  help: ''
  help_display: ''
  help_title: ''
  label_attributes: {  }
  more: ''
  more_title: ''
  multiple: true
  multiple__add_more: true
  multiple__add_more_button_label: Add
  multiple__add_more_input: true
  multiple__add_more_input_label: 'more items'
  multiple__add_more_items: 1
  multiple__empty_items: 1
  multiple__header: true
  multiple__header_label: ''
  multiple__min_items: ''
  multiple__no_items_message: 'No items entered. Please add items below.'
  multiple__operations: true
  multiple__sorting: true
  prepopulate: false
  private: false
  required: false
  select2: false
  states: {  }
  states_clear: true
  title: ''
  title_display: ''
  wrapper_attributes: {  }
  wrapper_type: fieldset
webform_document_file:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  attributes: {  }
  button: false
  button__attributes: {  }
  button__title: ''
  default_value: ''
  description: ''
  description_display: ''
  disabled: false
  field_prefix: ''
  field_suffix: ''
  file_extensions: 'txt rtf pdf doc docx odt ppt pptx odp xls xlsx ods'
  file_help: ''
  file_name: ''
  file_placeholder: ''
  file_preview: ''
  flex: 1
  format: file
  format_attributes: {  }
  format_html: ''
  format_items: ul
  format_items_html: ''
  format_items_text: ''
  format_text: ''
  help: ''
  help_display: ''
  help_title: ''
  label_attributes: {  }
  max_filesize: ''
  more: ''
  more_title: ''
  multiple: false
  private: false
  required: false
  required_error: ''
  sanitize: false
  states: {  }
  states_clear: true
  title: ''
  title_display: ''
  unique: false
  unique_entity: false
  unique_error: ''
  unique_user: false
  uri_scheme: private
  wrapper_attributes: {  }
webform_element: {  }
webform_email_confirm:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  attributes: {  }
  autocomplete: 'on'
  confirm__description: ''
  confirm__placeholder: ''
  confirm__title: ''
  default_value: ''
  description: ''
  description_display: ''
  disabled: false
  field_prefix: ''
  field_suffix: ''
  flex: 1
  flexbox: ''
  format: link
  format_attributes: {  }
  format_html: ''
  format_text: ''
  help: ''
  help_display: ''
  help_title: ''
  input_hide: false
  label_attributes: {  }
  maxlength: ''
  minlength: ''
  more: ''
  more_title: ''
  multiple__add_more: true
  multiple__add_more_button_label: Add
  multiple__add_more_input: true
  multiple__add_more_input_label: 'more items'
  multiple__add_more_items: 1
  multiple__empty_items: 1
  multiple__min_items: ''
  multiple__no_items_message: 'No items entered. Please add items below.'
  multiple__operations: true
  multiple__sorting: true
  pattern: ''
  pattern_error: ''
  placeholder: ''
  prepopulate: false
  private: false
  readonly: false
  required: false
  required_error: ''
  size: ''
  states: {  }
  states_clear: true
  title: ''
  title_display: ''
  unique: false
  unique_entity: false
  unique_error: ''
  unique_user: false
  wrapper_attributes: {  }
  wrapper_type: fieldset
webform_email_multiple:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  attributes: {  }
  autocomplete: 'on'
  default_value: ''
  description: ''
  description_display: ''
  disabled: false
  field_prefix: ''
  field_suffix: ''
  flex: 1
  format: link
  format_attributes: {  }
  format_html: ''
  format_text: ''
  help: ''
  help_display: ''
  help_title: ''
  input_hide: false
  label_attributes: {  }
  maxlength: ''
  minlength: ''
  more: ''
  more_title: ''
  multiple__add_more: true
  multiple__add_more_button_label: Add
  multiple__add_more_input: true
  multiple__add_more_input_label: 'more items'
  multiple__add_more_items: 1
  multiple__empty_items: 1
  multiple__header_label: ''
  multiple__min_items: ''
  multiple__no_items_message: 'No items entered. Please add items below.'
  multiple__operations: true
  multiple__sorting: true
  pattern: ''
  pattern_error: ''
  placeholder: ''
  prepopulate: false
  private: false
  readonly: false
  required: false
  required_error: ''
  size: ''
  states: {  }
  states_clear: true
  title: ''
  title_display: ''
  unique: false
  unique_entity: false
  unique_error: ''
  unique_user: false
  wrapper_attributes: {  }
webform_entity_checkboxes:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  attributes: {  }
  default_value: ''
  description: ''
  description_display: ''
  disabled: false
  field_prefix: ''
  field_suffix: ''
  flex: 1
  format: link
  format_attributes: {  }
  format_html: ''
  format_items: comma
  format_items_html: ''
  format_items_text: ''
  format_text: ''
  help: ''
  help_display: ''
  help_title: ''
  label_attributes: {  }
  more: ''
  more_title: ''
  multiple: true
  multiple_error: ''
  options__properties: {  }
  options_display: one_column
  options_randomize: false
  prepopulate: false
  private: false
  required: false
  required_error: ''
  selection_handler: ''
  selection_settings: {  }
  states: {  }
  states_clear: true
  target_type: ''
  title: ''
  title_display: ''
  unique: false
  unique_entity: false
  unique_error: ''
  unique_user: false
  wrapper_attributes: {  }
  wrapper_type: fieldset
'webform_entity_print_attachment:pdf':
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  display_on: none
  download: false
  filename: ''
  flex: 1
  label_attributes: {  }
  link_title: ''
  private: false
  sanitize: false
  states: {  }
  template: ''
  title: ''
  title_display: ''
  view_mode: html
  wrapper_attributes: {  }
webform_entity_radios:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  attributes: {  }
  default_value: ''
  description: ''
  description_display: ''
  disabled: false
  field_prefix: ''
  field_suffix: ''
  flex: 1
  format: link
  format_attributes: {  }
  format_html: ''
  format_text: ''
  help: ''
  help_display: ''
  help_title: ''
  label_attributes: {  }
  more: ''
  more_title: ''
  options__properties: {  }
  options_display: one_column
  options_randomize: false
  prepopulate: false
  private: false
  required: false
  required_error: ''
  selection_handler: ''
  selection_settings: {  }
  states: {  }
  states_clear: true
  target_type: ''
  title: ''
  title_display: ''
  unique: false
  unique_entity: false
  unique_error: ''
  unique_user: false
  wrapper_attributes: {  }
  wrapper_type: fieldset
webform_entity_select:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  attributes: {  }
  choices: false
  chosen: false
  default_value: ''
  description: ''
  description_display: ''
  disabled: false
  empty_option: ''
  empty_value: ''
  field_prefix: ''
  field_suffix: ''
  flex: 1
  format: link
  format_attributes: {  }
  format_html: ''
  format_items: comma
  format_items_html: ''
  format_items_text: ''
  format_text: ''
  help: ''
  help_display: ''
  help_title: ''
  label_attributes: {  }
  more: ''
  more_title: ''
  multiple: false
  multiple_error: ''
  options_randomize: false
  placeholder: ''
  prepopulate: false
  private: false
  required: false
  required_error: ''
  select2: false
  selection_handler: ''
  selection_settings: {  }
  size: ''
  states: {  }
  states_clear: true
  target_type: ''
  title: ''
  title_display: ''
  unique: false
  unique_entity: false
  unique_error: ''
  unique_user: false
  wrapper_attributes: {  }
webform_flexbox:
  align_items: flex-start
  attributes: {  }
  flex: 1
  format: header
  format_attributes: {  }
  format_html: ''
  format_text: ''
  randomize: false
  states: {  }
  states_clear: true
webform_horizontal_rule:
  attributes: {  }
  display_on: form
  states: {  }
webform_image_file:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  attachment_image_style: ''
  attributes: {  }
  button: false
  button__attributes: {  }
  button__title: ''
  default_value: ''
  description: ''
  description_display: ''
  disabled: false
  field_prefix: ''
  field_suffix: ''
  file_extensions: 'gif jpg jpeg png'
  file_help: ''
  file_name: ''
  file_placeholder: ''
  file_preview: ''
  flex: 1
  format: ':image'
  format_attributes: {  }
  format_html: ''
  format_items: ul
  format_items_html: ''
  format_items_text: ''
  format_text: ''
  help: ''
  help_display: ''
  help_title: ''
  label_attributes: {  }
  max_filesize: ''
  max_resolution: ''
  min_resolution: ''
  more: ''
  more_title: ''
  multiple: false
  private: false
  required: false
  required_error: ''
  sanitize: false
  states: {  }
  states_clear: true
  title: ''
  title_display: ''
  unique: false
  unique_entity: false
  unique_error: ''
  unique_user: false
  uri_scheme: private
  wrapper_attributes: {  }
webform_image_select:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  attributes: {  }
  choices: false
  chosen: false
  default_value: ''
  description: ''
  description_display: ''
  empty_option: ''
  empty_value: ''
  filter: false
  filter__no_results: 'No images found.'
  filter__placeholder: 'Filter images by label'
  filter__plural: images
  filter__singlular: image
  flex: 1
  format: image
  format_attributes: {  }
  format_html: ''
  format_items: space
  format_items_html: ''
  format_items_text: ''
  format_text: ''
  help: ''
  help_display: ''
  help_title: ''
  images: {  }
  images_randomize: false
  label_attributes: {  }
  more: ''
  more_title: ''
  multiple: false
  multiple_error: ''
  placeholder: ''
  prepopulate: false
  private: false
  required: false
  required_error: ''
  show_label: false
  size: ''
  states: {  }
  states_clear: true
  title: ''
  title_display: ''
  unique: false
  unique_entity: false
  unique_error: ''
  unique_user: false
  wrapper_attributes: {  }
webform_likert:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  answers: {  }
  answers_description_display: description
  default_value: {  }
  description: ''
  description_display: ''
  disabled: false
  flex: 1
  format: list
  format_attributes: {  }
  format_html: ''
  format_text: ''
  help: ''
  help_display: ''
  help_title: ''
  label_attributes: {  }
  more: ''
  more_title: ''
  na_answer: false
  na_answer_text: N/A
  na_answer_value: ''
  prepopulate: false
  private: false
  questions: {  }
  questions_description_display: description
  questions_randomize: false
  required: false
  required_error: ''
  states: {  }
  states_clear: true
  sticky: true
  title: ''
  title_display: ''
  wrapper_attributes: {  }
webform_link:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  attributes: {  }
  choices: false
  chosen: false
  default_value: {  }
  description: ''
  description_display: ''
  disabled: false
  field_prefix: ''
  field_suffix: ''
  flex: 1
  flexbox: ''
  format: value
  format_attributes: {  }
  format_html: ''
  format_items: ul
  format_items_html: ''
  format_items_text: ''
  format_text: ''
  help: ''
  help_display: ''
  help_title: ''
  label_attributes: {  }
  more: ''
  more_title: ''
  multiple: false
  multiple__add_more: true
  multiple__add_more_button_label: Add
  multiple__add_more_input: true
  multiple__add_more_input_label: 'more items'
  multiple__add_more_items: 1
  multiple__empty_items: 1
  multiple__header: false
  multiple__header_label: ''
  multiple__min_items: ''
  multiple__no_items_message: 'No items entered. Please add items below.'
  multiple__operations: true
  multiple__sorting: true
  prepopulate: false
  private: false
  required: false
  states: {  }
  states_clear: true
  title: ''
  title__access: true
  title__description: ''
  title__help: ''
  title__placeholder: ''
  title__required: false
  title__title: 'Link Title'
  title__title_display: ''
  title__type: textfield
  title_display: invisible
  url__access: true
  url__description: ''
  url__help: ''
  url__placeholder: ''
  url__required: false
  url__title: 'Link URL'
  url__title_display: ''
  url__type: url
  wrapper_attributes: {  }
  wrapper_type: fieldset
webform_location_places:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  administrative__access: false
  administrative__title: State/Province
  api_key: ''
  app_id: ''
  city__access: false
  city__title: City
  country__access: false
  country__title: Country
  country_code__access: false
  country_code__title: 'Country Code'
  county__access: false
  county__title: County
  default_value: {  }
  description: ''
  description_display: ''
  disabled: false
  flex: 1
  format: value
  format_html: ''
  format_items: ul
  format_items_html: ''
  format_items_text: ''
  format_text: ''
  geolocation: false
  help: ''
  help_title: ''
  hidden: false
  label_attributes: {  }
  lat__access: false
  lat__title: Latitude
  lng__access: false
  lng__title: Longitude
  more: ''
  more_title: ''
  multiple: false
  name__access: false
  name__title: Name
  placeholder: ''
  postcode__access: false
  postcode__title: 'Postal Code'
  prepopulate: false
  private: false
  required: false
  required_error: ''
  states: {  }
  states_clear: true
  suburb__access: false
  suburb__title: Suburb
  title: ''
  title_display: ''
  value__placeholder: ''
  value__title: Address
  wrapper_attributes: {  }
webform_mapping:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  arrow: →
  default_value: {  }
  description: ''
  description_display: ''
  destination: {  }
  destination__description: ''
  destination__title: Destination
  destination__type: select
  disabled: false
  flex: 1
  format: list
  format_attributes: {  }
  format_html: ''
  format_text: ''
  help: ''
  help_display: ''
  help_title: ''
  label_attributes: {  }
  more: ''
  more_title: ''
  prepopulate: false
  private: false
  required: false
  required_error: ''
  source: {  }
  source__description_display: description
  source__title: Source
  states: {  }
  states_clear: true
  title: ''
  title_display: ''
  wrapper_attributes: {  }
webform_markup:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  display_on: form
  flex: 1
  markup: ''
  private: false
  states: {  }
  wrapper_attributes: {  }
webform_message:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  attributes: {  }
  display_on: form
  flex: 1
  message_close: false
  message_close_effect: slide
  message_id: ''
  message_message: ''
  message_storage: ''
  message_type: status
  private: false
  states: {  }
webform_more:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  attributes: {  }
  display_on: form
  flex: 1
  more: ''
  more_title: More
  private: false
  states: {  }
webform_name:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  attributes: {  }
  choices: false
  chosen: false
  default_value: {  }
  degree__access: true
  degree__description: ''
  degree__help: ''
  degree__placeholder: ''
  degree__required: false
  degree__title: Degree
  degree__title_display: ''
  degree__type: textfield
  description: ''
  description_display: ''
  disabled: false
  field_prefix: ''
  field_suffix: ''
  first__access: true
  first__description: ''
  first__help: ''
  first__placeholder: ''
  first__required: false
  first__title: First
  first__title_display: ''
  first__type: textfield
  flex: 1
  flexbox: ''
  format: value
  format_attributes: {  }
  format_html: ''
  format_items: ul
  format_items_html: ''
  format_items_text: ''
  format_text: ''
  help: ''
  help_display: ''
  help_title: ''
  label_attributes: {  }
  last__access: true
  last__description: ''
  last__help: ''
  last__placeholder: ''
  last__required: false
  last__title: Last
  last__title_display: ''
  last__type: textfield
  middle__access: true
  middle__description: ''
  middle__help: ''
  middle__placeholder: ''
  middle__required: false
  middle__title: Middle
  middle__title_display: ''
  middle__type: textfield
  more: ''
  more_title: ''
  multiple: false
  multiple__add_more: true
  multiple__add_more_button_label: Add
  multiple__add_more_input: true
  multiple__add_more_input_label: 'more items'
  multiple__add_more_items: 1
  multiple__empty_items: 1
  multiple__header: false
  multiple__header_label: ''
  multiple__min_items: ''
  multiple__no_items_message: 'No items entered. Please add items below.'
  multiple__operations: true
  multiple__sorting: true
  prepopulate: false
  private: false
  required: false
  select2: false
  states: {  }
  states_clear: true
  suffix__access: true
  suffix__description: ''
  suffix__help: ''
  suffix__placeholder: ''
  suffix__required: false
  suffix__title: Suffix
  suffix__title_display: ''
  suffix__type: textfield
  title: ''
  title__access: true
  title__description: ''
  title__help: ''
  title__options: titles
  title__placeholder: ''
  title__required: false
  title__title: Title
  title__title_display: ''
  title__type: webform_select_other
  title_display: invisible
  wrapper_attributes: {  }
  wrapper_type: fieldset
'webform_options_custom:buttons':
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  attributes: {  }
  choices: false
  chosen: false
  default_value: ''
  description: ''
  description_display: ''
  disabled: false
  empty_option: ''
  empty_value: ''
  field_prefix: ''
  field_suffix: ''
  flex: 1
  format: value
  format_attributes: {  }
  format_html: ''
  format_items: comma
  format_items_html: ''
  format_items_text: ''
  format_text: ''
  help: ''
  help_display: ''
  help_title: ''
  label_attributes: {  }
  more: ''
  more_title: ''
  multiple: false
  multiple_error: ''
  options: {  }
  options_custom: ''
  options_description_display: true
  placeholder: ''
  prepopulate: false
  private: false
  required: false
  required_error: ''
  select2: false
  size: ''
  states: {  }
  states_clear: true
  title: ''
  title_display: ''
  unique: false
  unique_entity: false
  unique_error: ''
  unique_user: false
  wrapper_attributes: {  }
'webform_options_custom:us_states':
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  attributes: {  }
  choices: false
  chosen: false
  default_value: ''
  description: ''
  description_display: ''
  disabled: false
  empty_option: ''
  empty_value: ''
  field_prefix: ''
  field_suffix: ''
  flex: 1
  format: value
  format_attributes: {  }
  format_html: ''
  format_items: comma
  format_items_html: ''
  format_items_text: ''
  format_text: ''
  help: ''
  help_display: ''
  help_title: ''
  label_attributes: {  }
  more: ''
  more_title: ''
  multiple: false
  multiple_error: ''
  options: {  }
  options_custom: ''
  options_description_display: true
  placeholder: ''
  prepopulate: false
  private: false
  required: false
  required_error: ''
  select2: false
  size: ''
  states: {  }
  states_clear: true
  title: ''
  title_display: ''
  unique: false
  unique_entity: false
  unique_error: ''
  unique_user: false
  wrapper_attributes: {  }
webform_radios_other:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  attributes: {  }
  default_value: ''
  description: ''
  description_display: ''
  disabled: false
  field_prefix: ''
  field_suffix: ''
  flex: 1
  format: value
  format_attributes: {  }
  format_html: ''
  format_text: ''
  help: ''
  help_display: ''
  help_title: ''
  label_attributes: {  }
  more: ''
  more_title: ''
  options: {  }
  options__properties: {  }
  options_description_display: description
  options_display: one_column
  options_randomize: false
  other__counter_maximum: ''
  other__counter_maximum_message: ''
  other__counter_minimum: ''
  other__counter_minimum_message: ''
  other__counter_type: ''
  other__description: ''
  other__field_prefix: ''
  other__field_suffix: ''
  other__max: ''
  other__maxlength: ''
  other__min: ''
  other__option_label: Other…
  other__placeholder: 'Enter other…'
  other__rows: ''
  other__size: ''
  other__step: ''
  other__title: ''
  other__type: textfield
  prepopulate: false
  private: false
  required: false
  required_error: ''
  states: {  }
  states_clear: true
  title: ''
  title_display: ''
  unique: false
  unique_entity: false
  unique_error: ''
  unique_user: false
  wrapper_attributes: {  }
  wrapper_type: fieldset
webform_rating:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  attributes: {  }
  default_value: 0
  description: ''
  description_display: ''
  disabled: false
  field_prefix: ''
  field_suffix: ''
  flex: 1
  format: star
  format_attributes: {  }
  format_html: ''
  format_text: ''
  help: ''
  help_display: ''
  help_title: ''
  label_attributes: {  }
  max: 5
  min: 0
  more: ''
  more_title: ''
  prepopulate: false
  private: false
  readonly: false
  required: false
  required_error: ''
  reset: false
  star_size: medium
  states: {  }
  states_clear: true
  step: 1
  title: ''
  title_display: ''
  unique: false
  unique_entity: false
  unique_error: ''
  unique_user: false
  wrapper_attributes: {  }
webform_same:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  attributes: {  }
  default_value: false
  description: ''
  description_display: ''
  destination: ''
  destination_state: visible
  disabled: false
  exclude_empty: false
  field_prefix: ''
  field_suffix: ''
  flex: 1
  format: value
  format_attributes: {  }
  format_html: ''
  format_text: ''
  help: ''
  help_display: ''
  help_title: ''
  label_attributes: {  }
  more: ''
  more_title: ''
  prepopulate: false
  private: false
  return_value: ''
  source: ''
  states: {  }
  states_clear: true
  title: ''
  title_display: after
  wrapper_attributes: {  }
webform_section:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  attributes: {  }
  description: ''
  flex: 1
  format: header
  format_attributes: {  }
  format_html: ''
  format_text: ''
  help: ''
  help_display: ''
  help_title: ''
  more: ''
  more_title: ''
  private: false
  randomize: false
  required: false
  states: {  }
  states_clear: true
  title: ''
  title_display: ''
  title_tag: h2
webform_select_other:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  attributes: {  }
  choices: false
  chosen: false
  default_value: ''
  description: ''
  description_display: ''
  disabled: false
  empty_option: ''
  empty_value: ''
  field_prefix: ''
  field_suffix: ''
  flex: 1
  format: value
  format_attributes: {  }
  format_html: ''
  format_items: comma
  format_items_html: ''
  format_items_text: ''
  format_text: ''
  help: ''
  help_display: ''
  help_title: ''
  label_attributes: {  }
  more: ''
  more_title: ''
  multiple: false
  multiple_error: ''
  options: {  }
  options_randomize: false
  other__counter_maximum: ''
  other__counter_maximum_message: ''
  other__counter_minimum: ''
  other__counter_minimum_message: ''
  other__counter_type: ''
  other__description: ''
  other__field_prefix: ''
  other__field_suffix: ''
  other__max: ''
  other__maxlength: ''
  other__min: ''
  other__option_label: Other…
  other__placeholder: 'Enter other…'
  other__rows: ''
  other__size: ''
  other__step: ''
  other__title: ''
  other__type: textfield
  placeholder: ''
  prepopulate: false
  private: false
  required: false
  required_error: ''
  select2: false
  size: ''
  states: {  }
  states_clear: true
  title: ''
  title_display: ''
  unique: false
  unique_entity: false
  unique_error: ''
  unique_user: false
  wrapper_attributes: {  }
  wrapper_type: fieldset
webform_signature:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  attributes: {  }
  default_value: ''
  description: 'Sign above'
  description_display: ''
  disabled: false
  field_prefix: ''
  field_suffix: ''
  flex: 1
  format: image
  format_attributes: {  }
  format_html: ''
  format_text: ''
  help: ''
  help_display: ''
  help_title: ''
  label_attributes: {  }
  more: ''
  more_title: ''
  prepopulate: false
  private: false
  readonly: false
  required: false
  required_error: ''
  states: {  }
  states_clear: true
  title: ''
  title_display: ''
  unique: false
  unique_entity: false
  unique_error: ''
  unique_user: false
  wrapper_attributes: {  }
webform_table_sort:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  attributes: {  }
  default_value: ''
  description: ''
  description_display: ''
  disabled: false
  field_prefix: ''
  field_suffix: ''
  flex: 1
  format: ol
  format_attributes: {  }
  format_html: ''
  format_items: comma
  format_items_html: ''
  format_items_text: ''
  format_text: ''
  help: ''
  help_display: ''
  help_title: ''
  label_attributes: {  }
  more: ''
  more_title: ''
  options: {  }
  prepopulate: false
  private: false
  required: false
  required_error: ''
  states: {  }
  states_clear: true
  title: ''
  title_display: ''
  unique: false
  unique_entity: false
  unique_error: ''
  unique_user: false
  wrapper_attributes: {  }
webform_tableselect_sort:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  attributes: {  }
  default_value: ''
  description: ''
  description_display: ''
  disabled: false
  field_prefix: ''
  field_suffix: ''
  flex: 1
  format: ol
  format_attributes: {  }
  format_html: ''
  format_items: comma
  format_items_html: ''
  format_items_text: ''
  format_text: ''
  help: ''
  help_display: ''
  help_title: ''
  js_select: true
  label_attributes: {  }
  more: ''
  more_title: ''
  multiple: true
  multiple_error: ''
  options: {  }
  options_randomize: false
  prepopulate: false
  private: false
  required: false
  required_error: ''
  states: {  }
  states_clear: true
  title: ''
  title_display: ''
  unique: false
  unique_entity: false
  unique_error: ''
  unique_user: false
  wrapper_attributes: {  }
webform_telephone:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  attributes: {  }
  choices: false
  chosen: false
  default_value: {  }
  description: ''
  description_display: ''
  disabled: false
  ext__access: true
  ext__description: ''
  ext__help: ''
  ext__placeholder: ''
  ext__required: false
  ext__title: 'Ext:'
  ext__title_display: ''
  ext__type: number
  field_prefix: ''
  field_suffix: ''
  flex: 1
  format: value
  format_attributes: {  }
  format_html: ''
  format_items: ul
  format_items_html: ''
  format_items_text: ''
  format_text: ''
  help: ''
  help_display: ''
  help_title: ''
  label_attributes: {  }
  more: ''
  more_title: ''
  multiple: false
  multiple__add_more: true
  multiple__add_more_button_label: Add
  multiple__add_more_input: true
  multiple__add_more_input_label: 'more items'
  multiple__add_more_items: 1
  multiple__empty_items: 1
  multiple__header: false
  multiple__header_label: ''
  multiple__min_items: ''
  multiple__no_items_message: 'No items entered. Please add items below.'
  multiple__operations: true
  multiple__sorting: true
  phone__access: true
  phone__description: ''
  phone__help: ''
  phone__international: true
  phone__international_initial_country: ''
  phone__placeholder: ''
  phone__required: false
  phone__title: Phone
  phone__title_display: ''
  phone__type: tel
  prepopulate: false
  private: false
  required: false
  select2: false
  states: {  }
  states_clear: true
  title: ''
  title_display: ''
  type__access: true
  type__description: ''
  type__help: ''
  type__options: phone_types
  type__placeholder: ''
  type__required: false
  type__title: Type
  type__title_display: ''
  type__type: select
  wrapper_attributes: {  }
  wrapper_type: fieldset
webform_term_checkboxes:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  attributes: {  }
  breadcrumb: false
  breadcrumb_delimiter: ' › '
  default_value: ''
  description: ''
  description_display: ''
  disabled: false
  field_prefix: ''
  field_suffix: ''
  flex: 1
  format: link
  format_attributes: {  }
  format_html: ''
  format_items: comma
  format_items_html: ''
  format_items_text: ''
  format_text: ''
  help: ''
  help_display: ''
  help_title: ''
  label_attributes: {  }
  more: ''
  more_title: ''
  multiple: true
  multiple_error: ''
  options__properties: {  }
  options_description_display: description
  prepopulate: false
  private: false
  required: false
  required_error: ''
  scroll: true
  states: {  }
  states_clear: true
  title: ''
  title_display: ''
  tree_delimiter: '&nbsp;&nbsp;&nbsp;'
  unique: false
  unique_entity: false
  unique_error: ''
  unique_user: false
  vocabulary: ''
  wrapper_attributes: {  }
  wrapper_type: fieldset
webform_term_select:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  attributes: {  }
  breadcrumb: false
  breadcrumb_delimiter: ' › '
  choices: false
  chosen: false
  default_value: ''
  description: ''
  description_display: ''
  disabled: false
  empty_option: ''
  empty_value: ''
  field_prefix: ''
  field_suffix: ''
  flex: 1
  format: link
  format_attributes: {  }
  format_html: ''
  format_items: comma
  format_items_html: ''
  format_items_text: ''
  format_text: ''
  help: ''
  help_display: ''
  help_title: ''
  label_attributes: {  }
  more: ''
  more_title: ''
  multiple: false
  multiple_error: ''
  placeholder: ''
  prepopulate: false
  private: false
  required: false
  required_error: ''
  select2: false
  size: ''
  states: {  }
  states_clear: true
  title: ''
  title_display: ''
  tree_delimiter: '-'
  unique: false
  unique_entity: false
  unique_error: ''
  unique_user: false
  vocabulary: ''
  wrapper_attributes: {  }
webform_terms_of_service:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  attributes: {  }
  default_value: false
  disabled: false
  exclude_empty: false
  flex: 1
  format: value
  format_attributes: {  }
  format_html: ''
  format_text: ''
  help: ''
  help_display: ''
  help_title: ''
  label_attributes: {  }
  more: ''
  more_title: ''
  prepopulate: false
  private: false
  required: false
  required_error: ''
  return_value: ''
  states: {  }
  states_clear: true
  terms_content: ''
  terms_title: ''
  terms_type: modal
  title: 'I agree to the {terms of service}.'
  wrapper_attributes: {  }
webform_time:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  attributes: {  }
  default_value: ''
  description: ''
  description_display: ''
  disabled: false
  field_prefix: ''
  field_suffix: ''
  flex: 1
  format: value
  format_attributes: {  }
  format_html: ''
  format_items: ul
  format_items_html: ''
  format_items_text: ''
  format_text: ''
  help: ''
  help_display: ''
  help_title: ''
  label_attributes: {  }
  max: ''
  min: ''
  more: ''
  more_title: ''
  multiple: false
  multiple__add_more: true
  multiple__add_more_button_label: Add
  multiple__add_more_input: true
  multiple__add_more_input_label: 'more items'
  multiple__add_more_items: 1
  multiple__empty_items: 1
  multiple__header_label: ''
  multiple__min_items: ''
  multiple__no_items_message: 'No items entered. Please add items below.'
  multiple__operations: true
  multiple__sorting: true
  placeholder: ''
  prepopulate: false
  private: false
  required: false
  required_error: ''
  states: {  }
  states_clear: true
  step: 60
  time_format: 'H:i'
  timepicker: false
  title: ''
  title_display: ''
  unique: false
  unique_entity: false
  unique_error: ''
  unique_user: false
  wrapper_attributes: {  }
webform_variant:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  attributes: {  }
  default_value: ''
  description: ''
  description_display: ''
  display_on: none
  flex: 1
  format: value
  format_html: ''
  format_text: ''
  help: ''
  help_display: ''
  help_title: ''
  label_attributes: {  }
  more: ''
  more_title: ''
  prepopulate: true
  private: false
  randomize: false
  title: ''
  title_display: ''
  variant: ''
  wrapper_attributes: {  }
webform_video_file:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  attributes: {  }
  button: false
  button__attributes: {  }
  button__title: ''
  default_value: ''
  description: ''
  description_display: ''
  disabled: false
  field_prefix: ''
  field_suffix: ''
  file_extensions: 'avi mov mp4 ogg wav webm'
  file_help: ''
  file_name: ''
  file_placeholder: ''
  file_preview: ''
  flex: 1
  format: file
  format_attributes: {  }
  format_html: ''
  format_items: ul
  format_items_html: ''
  format_items_text: ''
  format_text: ''
  help: ''
  help_display: ''
  help_title: ''
  label_attributes: {  }
  max_filesize: ''
  more: ''
  more_title: ''
  multiple: false
  private: false
  required: false
  required_error: ''
  sanitize: false
  states: {  }
  states_clear: true
  title: ''
  title_display: ''
  unique: false
  unique_entity: false
  unique_error: ''
  unique_user: false
  uri_scheme: private
  wrapper_attributes: {  }
webform_wizard_page:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  format: details
  format_attributes: {  }
  format_html: ''
  format_text: ''
  next_button_label: ''
  open: false
  prev_button_label: ''
  private: false
  states: {  }
  states_clear: true
  title: ''
webform_location_geocomplete:
  geolocation: false
  hidden: false
  map: false
  api_key: ''
  title: ''
  default_value: {  }
  multiple: false
  help: ''
  help_title: ''
  description: ''
  more: ''
  more_title: ''
  title_display: ''
  description_display: ''
  disabled: false
  required: false
  required_error: ''
  wrapper_attributes: {  }
  label_attributes: {  }
  format: value
  format_html: ''
  format_text: ''
  format_items: ul
  format_items_html: ''
  format_items_text: ''
  admin_title: ''
  prepopulate: false
  private: false
  flex: 1
  states: {  }
  states_clear: true
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_create_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_update_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  access_view_permissions: {  }
  value__title: Address
  value__placeholder: ''
  lat__title: Latitude
  lat__access: false
  lng__title: Longitude
  lng__access: false
  location__title: Location
  location__access: false
  formatted_address__title: 'Formatted Address'
  formatted_address__access: false
  street_address__title: 'Street Address'
  street_address__access: false
  street_number__title: 'Street Number'
  street_number__access: false
  subpremise__title: Unit
  subpremise__access: false
  postal_code__title: 'Postal Code'
  postal_code__access: false
  locality__title: Locality
  locality__access: false
  sublocality__title: City
  sublocality__access: false
  administrative_area_level_1__title: State/Province
  administrative_area_level_1__access: false
  country__title: Country
  country__access: false
  country_short__title: 'Country Code'
  country_short__access: false
webform_toggle:
  toggle_theme: light
  toggle_size: medium
  on_text: ''
  off_text: ''
  title_display: after
  exclude_empty: false
  default_value: false
  title: ''
  help: ''
  help_title: ''
  description: ''
  more: ''
  more_title: ''
  description_display: ''
  help_display: ''
  field_prefix: ''
  field_suffix: ''
  disabled: false
  required_error: ''
  wrapper_attributes: {  }
  label_attributes: {  }
  attributes: {  }
  format: value
  format_html: ''
  format_text: ''
  format_attributes: {  }
  admin_title: ''
  prepopulate: false
  private: false
  flex: 1
  return_value: ''
  states: {  }
  states_clear: true
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_create_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_update_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  access_view_permissions: {  }
webform_toggles:
  toggle_theme: light
  toggle_size: medium
  on_text: ''
  off_text: ''
  options: {  }
  options_randomize: false
  title: ''
  default_value: ''
  help: ''
  help_title: ''
  description: ''
  more: ''
  more_title: ''
  title_display: ''
  description_display: ''
  help_display: ''
  field_prefix: ''
  field_suffix: ''
  disabled: false
  required_error: ''
  wrapper_attributes: {  }
  label_attributes: {  }
  attributes: {  }
  format: value
  format_html: ''
  format_text: ''
  format_items: comma
  format_items_html: ''
  format_items_text: ''
  format_attributes: {  }
  unique: false
  unique_user: false
  unique_entity: false
  unique_error: ''
  admin_title: ''
  prepopulate: false
  private: false
  flex: 1
  states: {  }
  states_clear: true
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_create_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_update_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  access_view_permissions: {  }
webform_table:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  attributes: {  }
  caption: ''
  default_value: ''
  description: ''
  description_display: ''
  field_prefix: ''
  field_suffix: ''
  flex: 1
  format: table
  format_attributes: {  }
  format_html: ''
  format_text: ''
  header: {  }
  help: ''
  help_display: ''
  help_title: ''
  label_attributes: {  }
  more: ''
  more_title: ''
  prefix_children: true
  private: false
  required: false
  required_error: ''
  states: {  }
  states_clear: true
  sticky: false
  title: ''
  title_display: ''
  wrapper_attributes: {  }
webform_table_row:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  attributes: {  }
  flex: 1
  private: false
  states: {  }
  states_clear: true
  title: ''
webform_scale:
  access_create_permissions: {  }
  access_create_roles:
    - anonymous
    - authenticated
  access_create_users: {  }
  access_update_permissions: {  }
  access_update_roles:
    - anonymous
    - authenticated
  access_update_users: {  }
  access_view_permissions: {  }
  access_view_roles:
    - anonymous
    - authenticated
  access_view_users: {  }
  admin_title: ''
  attributes: {  }
  default_value: ''
  description: ''
  description_display: ''
  disabled: false
  field_prefix: ''
  field_suffix: ''
  flex: 1
  format: value
  format_attributes: {  }
  format_html: ''
  format_text: ''
  help: ''
  help_display: ''
  help_title: ''
  label_attributes: {  }
  max: 5
  max_text: ''
  min: 1
  min_text: ''
  more: ''
  more_title: ''
  prepopulate: false
  private: false
  readonly: false
  required: false
  required_error: ''
  scale_size: medium
  scale_text: below
  scale_type: circle
  states: {  }
  states_clear: true
  title: ''
  title_display: ''
  unique: false
  unique_entity: false
  unique_error: ''
  unique_user: false
  wrapper_attributes: {  }
  wrapper_type: fieldset
YAML;
    return Yaml::decode($yaml);
  }

}
