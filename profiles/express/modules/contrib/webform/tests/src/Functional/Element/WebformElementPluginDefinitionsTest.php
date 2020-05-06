<?php

namespace Drupal\Tests\webform\Functional\Element;

use Drupal\Core\Serialization\Yaml;
use Drupal\webform\Utility\WebformElementHelper;

/**
 * Tests for webform element definitions.
 *
 * @group Webform
 */
class WebformElementPluginDefinitionsTest extends WebformElementBrowserTestBase {

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
   * Debug dump the element's definitions as YAML.
   *
   * @var bool
   */
  protected $debug = FALSE;

  /**
   * Test element definitions.
   */
  public function testElementDefinitions() {
    // Comparing all element's expected and actual definitions ensures
    // that there are not unexpected changes to any element's definitions.
    $expected_definitions = $this->getExpectedElementDefinitions();
    $actual_definitions = $this->getActualElementDefinitions();
    $this->htmlOutput('<pre>' . htmlentities(Yaml::encode($actual_definitions)) . '</pre>');
    foreach ($actual_definitions as $key => $actual_definition) {
      if ($expected_definitions[$key] != $actual_definition) {
        $this->htmlOutput('<pre>' . Yaml::encode([$key => $actual_definition]) . '</pre>');
      }
      $this->assertEquals($expected_definitions[$key], $actual_definition, "Expected and actual '$key' element definitions match.");
    }
  }

  /**
   * Get actual element definitions.
   *
   * @return array
   *   Expected element definitions.
   */
  protected function getActualElementDefinitions() {
    /** @var \Drupal\webform\Plugin\WebformElementManagerInterface $webform_element_manager */
    $webform_element_manager = \Drupal::service('plugin.manager.webform.element');
    /** @var \Drupal\webform\Plugin\WebformElementInterface[] $webform_elements */
    $webform_elements = $webform_element_manager->getInstances();
    $element = ['#type' => 'element'];
    $definitions = [];
    foreach ($webform_elements as $element_key => $webform_element) {
      $webform_element_plugin_definition = $webform_element_manager->getDefinition($element_key);
      $definition = $webform_element_plugin_definition + [
        'input' => $webform_element->isInput($element),
        'container' => $webform_element->isContainer($element),
        'composite' => $webform_element->isComposite(),
        'root' => $webform_element->isRoot(),
        'hidden' => $webform_element->isHidden(),
        'multiple' => $webform_element->supportsMultipleValues(),
        'multiline' => $webform_element->isMultiline($element),
      ];
      $definitions[$webform_element->getPluginId()] = $definition;
    }
    ksort($definitions);
    WebformElementHelper::convertRenderMarkupToStrings($definitions);
    return $definitions;
  }

  /**
   * Get expected element definitions.
   *
   * @return array
   *   Expected element definitions.
   */
  protected function getExpectedElementDefinitions() {
    $yaml = <<<YAML
address:
  dependencies:
    - address
  default_key: ''
  category: 'Composite elements'
  description: 'Provides advanced element for storing, validating and displaying international postal addresses.'
  hidden: false
  multiline: true
  composite: true
  states_wrapper: true
  deprecated: false
  deprecated_message: ''
  id: address
  label: 'Advanced address'
  class: Drupal\webform\Plugin\WebformElement\Address
  provider: webform
  input: true
  container: false
  root: false
  multiple: true
captcha:
  dependencies:
    - captcha
  default_key: captcha
  category: 'Advanced elements'
  description: 'Provides a form element that determines whether the user is human.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: true
  deprecated: false
  deprecated_message: ''
  id: captcha
  api: 'https://www.drupal.org/project/captcha'
  label: CAPTCHA
  class: Drupal\webform\Plugin\WebformElement\Captcha
  provider: webform
  input: false
  container: false
  root: false
  multiple: false
checkbox:
  dependencies: {  }
  default_key: ''
  category: 'Basic elements'
  description: 'Provides a form element for a single checkbox.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: false
  deprecated: false
  deprecated_message: ''
  id: checkbox
  api: 'https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Checkbox.php/class/Checkbox'
  label: Checkbox
  class: Drupal\webform\Plugin\WebformElement\Checkbox
  provider: webform
  input: true
  container: false
  root: false
  multiple: false
checkboxes:
  dependencies: {  }
  default_key: ''
  category: 'Options elements'
  description: 'Provides a form element for a set of checkboxes.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: false
  deprecated: false
  deprecated_message: ''
  id: checkboxes
  api: 'https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Checkboxes.php/class/Checkboxes'
  label: Checkboxes
  class: Drupal\webform\Plugin\WebformElement\Checkboxes
  provider: webform
  input: true
  container: false
  root: false
  multiple: true
color:
  dependencies: {  }
  default_key: ''
  category: 'Advanced elements'
  description: 'Provides a form element for choosing a color.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: false
  deprecated: false
  deprecated_message: ''
  id: color
  api: 'https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Color.php/class/Color'
  label: Color
  class: Drupal\webform\Plugin\WebformElement\Color
  provider: webform
  input: true
  container: false
  root: false
  multiple: false
container:
  dependencies: {  }
  default_key: container
  category: Containers
  description: 'Provides an element that wraps child elements in a container.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: false
  deprecated: false
  deprecated_message: ''
  id: container
  api: 'https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Container.php/class/Container'
  label: Container
  class: Drupal\webform\Plugin\WebformElement\Container
  provider: webform
  input: false
  container: true
  root: false
  multiple: false
date:
  dependencies: {  }
  default_key: ''
  category: 'Date/time elements'
  description: 'Provides a form element for date selection.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: false
  deprecated: false
  deprecated_message: ''
  id: date
  api: 'https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Date.php/class/Date'
  label: Date
  class: Drupal\webform\Plugin\WebformElement\Date
  provider: webform
  input: true
  container: false
  root: false
  multiple: true
datelist:
  dependencies: {  }
  default_key: ''
  category: 'Date/time elements'
  description: 'Provides a form element for date & time selection using select menus and text fields.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: false
  deprecated: false
  deprecated_message: ''
  id: datelist
  api: 'https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Datetime!Element!Datelist.php/class/Datelist'
  label: 'Date list'
  class: Drupal\webform\Plugin\WebformElement\DateList
  provider: webform
  input: true
  container: false
  root: false
  multiple: true
datetime:
  dependencies: {  }
  default_key: ''
  category: 'Date/time elements'
  description: 'Provides a form element for date & time selection.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: false
  deprecated: false
  deprecated_message: ''
  id: datetime
  api: 'https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Datetime!Element!Datetime.php/class/Datetime'
  label: Date/time
  class: Drupal\webform\Plugin\WebformElement\DateTime
  provider: webform
  input: true
  container: false
  root: false
  multiple: true
details:
  dependencies: {  }
  default_key: ''
  category: Containers
  description: 'Provides an interactive element that a user can open and close.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: false
  deprecated: false
  deprecated_message: ''
  id: details
  api: 'https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Details.php/class/Details'
  label: Details
  class: Drupal\webform\Plugin\WebformElement\Details
  provider: webform
  input: false
  container: true
  root: false
  multiple: false
email:
  dependencies: {  }
  default_key: ''
  category: 'Advanced elements'
  description: 'Provides a form element for entering an email address.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: false
  deprecated: false
  deprecated_message: ''
  id: email
  api: 'https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Email.php/class/Email'
  label: Email
  class: Drupal\webform\Plugin\WebformElement\Email
  provider: webform
  input: true
  container: false
  root: false
  multiple: true
entity_autocomplete:
  dependencies: {  }
  default_key: ''
  category: 'Entity reference elements'
  description: 'Provides a form element to select an entity reference using an autocompletion.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: false
  deprecated: false
  deprecated_message: ''
  id: entity_autocomplete
  api: 'https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Entity!Element!EntityAutocomplete.php/class/EntityAutocomplete'
  label: 'Entity autocomplete'
  class: Drupal\webform\Plugin\WebformElement\EntityAutocomplete
  provider: webform
  input: true
  container: false
  root: false
  multiple: true
fieldset:
  dependencies: {  }
  default_key: ''
  category: Containers
  description: 'Provides an element for a group of form elements.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: false
  deprecated: false
  deprecated_message: ''
  id: fieldset
  api: 'https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Fieldset.php/class/Fieldset'
  label: Fieldset
  class: Drupal\webform\Plugin\WebformElement\Fieldset
  provider: webform
  input: false
  container: true
  root: false
  multiple: false
hidden:
  dependencies: {  }
  default_key: ''
  category: 'Basic elements'
  description: 'Provides a form element for an HTML ''hidden'' input element.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: false
  deprecated: false
  deprecated_message: ''
  id: hidden
  api: 'https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Hidden.php/class/Hidden'
  label: Hidden
  class: Drupal\webform\Plugin\WebformElement\Hidden
  provider: webform
  input: true
  container: false
  root: false
  multiple: false
item:
  dependencies: {  }
  default_key: ''
  category: Containers
  description: 'Provides a display-only form element with an optional title and description.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: false
  deprecated: false
  deprecated_message: ''
  id: item
  api: 'https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Item.php/class/Item'
  label: Item
  class: Drupal\webform\Plugin\WebformElement\Item
  provider: webform
  input: false
  container: false
  root: false
  multiple: false
label:
  dependencies: {  }
  default_key: ''
  category: 'Markup elements'
  description: 'Provides an element for displaying the label for a form element.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: true
  deprecated: false
  deprecated_message: ''
  id: label
  api: 'https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Label.php/class/Label'
  label: Label
  class: Drupal\webform\Plugin\WebformElement\Label
  provider: webform
  input: false
  container: false
  root: false
  multiple: false
language_select:
  dependencies: {  }
  default_key: ''
  category: ''
  description: 'Provides a form element for selecting a language.'
  hidden: true
  multiline: false
  composite: false
  states_wrapper: false
  deprecated: false
  deprecated_message: ''
  id: language_select
  api: 'https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!LanguageSelect.php/class/LanguageSelect'
  label: 'Language select'
  class: Drupal\webform\Plugin\WebformElement\LanguageSelect
  provider: webform
  input: true
  container: false
  root: false
  multiple: false
machine_name:
  dependencies: {  }
  default_key: ''
  category: ''
  description: 'Provides a form element to enter a machine name, which is validated to ensure that the name is unique and does not contain disallowed characters.'
  hidden: true
  multiline: false
  composite: false
  states_wrapper: false
  deprecated: false
  deprecated_message: ''
  id: machine_name
  api: 'https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!MachineName.php/class/MachineName'
  label: 'Machine name'
  class: Drupal\webform\Plugin\WebformElement\MachineName
  provider: webform
  input: true
  container: false
  root: false
  multiple: false
managed_file:
  dependencies: {  }
  default_key: ''
  category: 'File upload elements'
  description: 'Provides a form element for uploading and saving a file.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: true
  deprecated: false
  deprecated_message: ''
  id: managed_file
  api: 'https://api.drupal.org/api/drupal/core!modules!file!src!Element!ManagedFile.php/class/ManagedFile'
  label: File
  class: Drupal\webform\Plugin\WebformElement\ManagedFile
  provider: webform
  input: true
  container: false
  root: false
  multiple: true
number:
  dependencies: {  }
  default_key: ''
  category: 'Advanced elements'
  description: 'Provides a form element for numeric input, with special numeric validation.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: false
  deprecated: false
  deprecated_message: ''
  id: number
  api: 'https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Number.php/class/Number'
  label: Number
  class: Drupal\webform\Plugin\WebformElement\Number
  provider: webform
  input: true
  container: false
  root: false
  multiple: true
processed_text:
  dependencies: {  }
  default_key: processed_text
  category: 'Markup elements'
  description: 'Provides an element to render advanced HTML markup and processed text.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: true
  deprecated: false
  deprecated_message: ''
  id: processed_text
  label: 'Advanced HTML/Text'
  class: Drupal\webform\Plugin\WebformElement\ProcessedText
  provider: webform
  input: false
  container: false
  root: false
  multiple: false
radios:
  dependencies: {  }
  default_key: ''
  category: 'Options elements'
  description: 'Provides a form element for a set of radio buttons.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: false
  deprecated: false
  deprecated_message: ''
  id: radios
  api: 'https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Radios.php/class/Radios'
  label: Radios
  class: Drupal\webform\Plugin\WebformElement\Radios
  provider: webform
  input: true
  container: false
  root: false
  multiple: false
range:
  dependencies: {  }
  default_key: ''
  category: 'Advanced elements'
  description: 'Provides a form element for input of a number within a specific range using a slider.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: false
  deprecated: false
  deprecated_message: ''
  id: range
  api: 'https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Range.php/class/Range'
  label: Range
  class: Drupal\webform\Plugin\WebformElement\Range
  provider: webform
  input: true
  container: false
  root: false
  multiple: false
search:
  dependencies: {  }
  default_key: ''
  category: 'Advanced elements'
  description: 'Provides form element for entering a search phrase.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: false
  deprecated: false
  deprecated_message: ''
  id: search
  api: 'https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Search.php/class/Search'
  label: Search
  class: Drupal\webform\Plugin\WebformElement\Search
  provider: webform
  input: true
  container: false
  root: false
  multiple: false
select:
  dependencies: {  }
  default_key: ''
  category: 'Options elements'
  description: 'Provides a form element for a drop-down menu or scrolling selection box.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: false
  deprecated: false
  deprecated_message: ''
  id: select
  api: 'https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Select.php/class/Select'
  label: Select
  class: Drupal\webform\Plugin\WebformElement\Select
  provider: webform
  input: true
  container: false
  root: false
  multiple: true
table:
  dependencies: {  }
  default_key: ''
  category: ''
  description: 'Provides an element to render a table.'
  hidden: true
  multiline: false
  composite: false
  states_wrapper: false
  deprecated: false
  deprecated_message: ''
  id: table
  api: 'https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Table.php/class/Table'
  label: Table
  class: Drupal\webform\Plugin\WebformElement\Table
  provider: webform
  input: false
  container: true
  root: false
  multiple: false
tableselect:
  dependencies: {  }
  default_key: ''
  category: 'Options elements'
  description: 'Provides a form element for a table with radios or checkboxes in left column.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: false
  deprecated: false
  deprecated_message: ''
  id: tableselect
  api: 'https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Tableselect.php/class/Tableselect'
  label: 'Table select'
  class: Drupal\webform\Plugin\WebformElement\TableSelect
  provider: webform
  input: true
  container: false
  root: false
  multiple: true
tel:
  dependencies: {  }
  default_key: ''
  category: 'Advanced elements'
  description: "Provides a form element for entering a telephone number."
  hidden: false
  multiline: false
  composite: false
  states_wrapper: false
  deprecated: false
  deprecated_message: ''
  id: tel
  api: 'https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Tel.php/class/Tel'
  label: Telephone
  class: Drupal\webform\Plugin\WebformElement\Telephone
  provider: webform
  input: true
  container: false
  root: false
  multiple: true
text_format:
  dependencies: {  }
  default_key: ''
  category: 'Advanced elements'
  description: 'Provides a text format form element.'
  hidden: false
  multiline: true
  composite: true
  states_wrapper: true
  deprecated: false
  deprecated_message: ''
  id: text_format
  api: 'https://api.drupal.org/api/drupal/core!modules!filter!src!Element!TextFormat.php/class/TextFormat'
  label: 'Text format'
  class: Drupal\webform\Plugin\WebformElement\TextFormat
  provider: webform
  input: true
  container: false
  root: false
  multiple: false
textarea:
  dependencies: {  }
  default_key: ''
  category: 'Basic elements'
  description: 'Provides a form element for input of multiple-line text.'
  hidden: false
  multiline: true
  composite: false
  states_wrapper: false
  deprecated: false
  deprecated_message: ''
  id: textarea
  api: 'https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Textarea.php/class/Textarea'
  label: Textarea
  class: Drupal\webform\Plugin\WebformElement\Textarea
  provider: webform
  input: true
  container: false
  root: false
  multiple: true
textfield:
  dependencies: {  }
  default_key: ''
  category: 'Basic elements'
  description: 'Provides a form element for input of a single-line text.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: false
  deprecated: false
  deprecated_message: ''
  id: textfield
  api: 'https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Textfield.php/class/Textfield'
  label: 'Text field'
  class: Drupal\webform\Plugin\WebformElement\TextField
  provider: webform
  input: true
  container: false
  root: false
  multiple: true
url:
  dependencies: {  }
  default_key: ''
  category: 'Advanced elements'
  description: 'Provides a form element for input of a URL.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: false
  deprecated: false
  deprecated_message: ''
  id: url
  api: 'https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Url.php/class/Url'
  label: URL
  class: Drupal\webform\Plugin\WebformElement\Url
  provider: webform
  input: true
  container: false
  root: false
  multiple: true
value:
  dependencies: {  }
  default_key: ''
  category: 'Advanced elements'
  description: 'Provides a form element for storage of internal information.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: false
  deprecated: false
  deprecated_message: ''
  id: value
  api: 'https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Value.php/class/Value'
  label: Value
  class: Drupal\webform\Plugin\WebformElement\Value
  provider: webform
  input: true
  container: false
  root: false
  multiple: false
view:
  dependencies: {  }
  default_key: ''
  category: 'Markup elements'
  description: 'Provides a view embed element. Only users who can ''Administer views'' or ''Edit webform source code'' can create and update this element.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: true
  deprecated: false
  deprecated_message: ''
  id: view
  label: View
  class: Drupal\webform\Plugin\WebformElement\View
  provider: webform
  input: false
  container: false
  root: false
  multiple: false
webform_actions:
  dependencies: {  }
  default_key: actions
  category: Buttons
  description: 'Provides an element that contains a Webform''s submit, draft, wizard, and/or preview buttons.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: false
  deprecated: false
  deprecated_message: ''
  id: webform_actions
  label: 'Submit button(s)'
  class: Drupal\webform\Plugin\WebformElement\WebformActions
  provider: webform
  input: false
  container: false
  root: false
  multiple: false
webform_address:
  dependencies: {  }
  default_key: ''
  category: 'Composite elements'
  description: 'Provides a form element to collect address information (street, city, state, zip).'
  hidden: false
  multiline: true
  composite: true
  states_wrapper: true
  deprecated: false
  deprecated_message: ''
  id: webform_address
  label: Address
  class: Drupal\webform\Plugin\WebformElement\WebformAddress
  provider: webform
  input: true
  container: false
  root: false
  multiple: true
webform_attachment_token:
  dependencies: {  }
  default_key: ''
  category: 'File attachment elements'
  description: 'Generates an attachment using tokens.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: false
  deprecated: false
  deprecated_message: ''
  id: webform_attachment_token
  label: 'Attachment token'
  class: Drupal\webform_attachment\Plugin\WebformElement\WebformAttachmentToken
  provider: webform_attachment
  input: true
  container: false
  root: false
  multiple: false
webform_attachment_twig:
  dependencies: {  }
  default_key: ''
  category: 'File attachment elements'
  description: 'Generates an attachment using Twig.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: false
  deprecated: false
  deprecated_message: ''
  id: webform_attachment_twig
  label: 'Attachment Twig'
  class: Drupal\webform_attachment\Plugin\WebformElement\WebformAttachmentTwig
  provider: webform_attachment
  input: true
  container: false
  root: false
  multiple: false
webform_attachment_url:
  dependencies: {  }
  default_key: ''
  category: 'File attachment elements'
  description: 'Generates an attachment using a URL.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: false
  deprecated: false
  deprecated_message: ''
  id: webform_attachment_url
  label: 'Attachment URL'
  class: Drupal\webform_attachment\Plugin\WebformElement\WebformAttachmentUrl
  provider: webform_attachment
  input: true
  container: false
  root: false
  multiple: false
webform_audio_file:
  dependencies:
    - file
  default_key: ''
  category: 'File upload elements'
  description: 'Provides a form element for uploading and saving an audio file.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: true
  deprecated: false
  deprecated_message: ''
  id: webform_audio_file
  label: 'Audio file'
  class: Drupal\webform\Plugin\WebformElement\WebformAudioFile
  provider: webform
  input: true
  container: false
  root: false
  multiple: true
webform_autocomplete:
  dependencies: {  }
  default_key: ''
  category: 'Advanced elements'
  description: 'Provides a text field element with auto completion.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: false
  deprecated: false
  deprecated_message: ''
  id: webform_autocomplete
  label: Autocomplete
  class: Drupal\webform\Plugin\WebformElement\WebformAutocomplete
  provider: webform
  input: true
  container: false
  root: false
  multiple: true
webform_checkboxes_other:
  dependencies: {  }
  default_key: ''
  category: 'Options elements'
  description: 'Provides a form element for a set of checkboxes, with the ability to enter a custom value.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: false
  deprecated: false
  deprecated_message: ''
  id: webform_checkboxes_other
  label: 'Checkboxes other'
  class: Drupal\webform\Plugin\WebformElement\WebformCheckboxesOther
  provider: webform
  input: true
  container: false
  root: false
  multiple: true
webform_codemirror:
  dependencies: {  }
  default_key: ''
  category: 'Advanced elements'
  description: 'Provides a form element for editing code in a number of programming languages and markup.'
  hidden: false
  multiline: true
  composite: false
  states_wrapper: false
  deprecated: false
  deprecated_message: ''
  id: webform_codemirror
  label: CodeMirror
  class: Drupal\webform\Plugin\WebformElement\WebformCodeMirror
  provider: webform
  input: true
  container: false
  root: false
  multiple: false
webform_computed_token:
  dependencies: {  }
  default_key: ''
  category: 'Computed Elements'
  description: 'Provides an item to display computed webform submission values using tokens.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: false
  deprecated: false
  deprecated_message: ''
  id: webform_computed_token
  label: 'Computed token'
  class: Drupal\webform\Plugin\WebformElement\WebformComputedToken
  provider: webform
  input: true
  container: false
  root: false
  multiple: false
webform_computed_twig:
  dependencies: {  }
  default_key: ''
  category: 'Computed Elements'
  description: 'Provides an item to display computed webform submission values using Twig.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: false
  deprecated: false
  deprecated_message: ''
  id: webform_computed_twig
  label: 'Computed Twig'
  class: Drupal\webform\Plugin\WebformElement\WebformComputedTwig
  provider: webform
  input: true
  container: false
  root: false
  multiple: false
webform_contact:
  dependencies: {  }
  default_key: ''
  category: 'Composite elements'
  description: 'Provides a form element to collect contact information (name, address, phone, email).'
  hidden: false
  multiline: true
  composite: true
  states_wrapper: true
  deprecated: false
  deprecated_message: ''
  id: webform_contact
  label: Contact
  class: Drupal\webform\Plugin\WebformElement\WebformContact
  provider: webform
  input: true
  container: false
  root: false
  multiple: true
webform_custom_composite:
  dependencies: {  }
  default_key: ''
  category: 'Composite elements'
  description: 'Provides a form element to create custom composites using a grid/table layout.'
  hidden: false
  multiline: true
  composite: true
  states_wrapper: true
  deprecated: false
  deprecated_message: ''
  id: webform_custom_composite
  label: 'Custom composite'
  class: Drupal\webform\Plugin\WebformElement\WebformCustomComposite
  provider: webform
  input: true
  container: false
  root: false
  multiple: true
webform_document_file:
  dependencies:
    - file
  default_key: ''
  category: 'File upload elements'
  description: 'Provides a form element for uploading and saving a document.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: true
  deprecated: false
  deprecated_message: ''
  id: webform_document_file
  label: 'Document file'
  class: Drupal\webform\Plugin\WebformElement\WebformDocumentFile
  provider: webform
  input: true
  container: false
  root: false
  multiple: true
webform_element:
  dependencies: {  }
  default_key: ''
  category: ''
  description: 'Provides a generic form element.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: false
  deprecated: false
  deprecated_message: ''
  id: webform_element
  label: 'Generic element'
  class: Drupal\webform\Plugin\WebformElement\WebformElement
  provider: webform
  input: true
  container: false
  root: false
  multiple: false
webform_email_confirm:
  dependencies: {  }
  default_key: ''
  category: 'Advanced elements'
  description: 'Provides a form element for double-input of email addresses.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: true
  deprecated: false
  deprecated_message: ''
  id: webform_email_confirm
  label: 'Email confirm'
  class: Drupal\webform\Plugin\WebformElement\WebformEmailConfirm
  provider: webform
  input: true
  container: false
  root: false
  multiple: false
webform_email_multiple:
  dependencies: {  }
  default_key: ''
  category: 'Advanced elements'
  description: 'Provides a form element for multiple email addresses.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: false
  deprecated: false
  deprecated_message: ''
  id: webform_email_multiple
  label: 'Email multiple'
  class: Drupal\webform\Plugin\WebformElement\WebformEmailMultiple
  provider: webform
  input: true
  container: false
  root: false
  multiple: false
webform_entity_checkboxes:
  dependencies: {  }
  default_key: ''
  category: 'Entity reference elements'
  description: 'Provides a form element to select multiple entity references using checkboxes.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: false
  deprecated: false
  deprecated_message: ''
  id: webform_entity_checkboxes
  label: 'Entity checkboxes'
  class: Drupal\webform\Plugin\WebformElement\WebformEntityCheckboxes
  provider: webform
  input: true
  container: false
  root: false
  multiple: true
'webform_entity_print_attachment:pdf':
  dependencies: {  }
  default_key: ''
  category: 'File attachment elements'
  description: 'Generates a PDF attachment.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: false
  deprecated: false
  deprecated_message: ''
  id: webform_entity_print_attachment
  label: 'Attachment PDF'
  deriver: \Drupal\webform_entity_print_attachment\Plugin\Derivative\WebformEntityPrintAttachmentDeriver
  class: Drupal\webform_entity_print_attachment\Plugin\WebformElement\WebformEntityPrintAttachment
  provider: webform_entity_print_attachment
  input: true
  container: false
  root: false
  multiple: false
webform_entity_radios:
  dependencies: {  }
  default_key: ''
  category: 'Entity reference elements'
  description: 'Provides a form element to select a single entity reference using radio buttons.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: false
  deprecated: false
  deprecated_message: ''
  id: webform_entity_radios
  label: 'Entity radios'
  class: Drupal\webform\Plugin\WebformElement\WebformEntityRadios
  provider: webform
  input: true
  container: false
  root: false
  multiple: false
webform_entity_select:
  dependencies: {  }
  default_key: ''
  category: 'Entity reference elements'
  description: 'Provides a form element to select a single or multiple entity references using a select menu.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: false
  deprecated: false
  deprecated_message: ''
  id: webform_entity_select
  label: 'Entity select'
  class: Drupal\webform\Plugin\WebformElement\WebformEntitySelect
  provider: webform
  input: true
  container: false
  root: false
  multiple: true
webform_flexbox:
  dependencies: {  }
  default_key: flexbox
  category: Containers
  description: 'Provides a flex(ible) box container used to layout elements in multiple columns.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: false
  deprecated: false
  deprecated_message: ''
  id: webform_flexbox
  api: 'http://www.w3schools.com/css/css3_flexbox.asp'
  label: 'Flexbox layout'
  class: Drupal\webform\Plugin\WebformElement\WebformFlexbox
  provider: webform
  input: false
  container: true
  root: false
  multiple: false
webform_horizontal_rule:
  dependencies: {  }
  default_key: horizontal_rule
  category: 'Markup elements'
  description: 'Provides a horizontal rule element.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: true
  deprecated: false
  deprecated_message: ''
  id: webform_horizontal_rule
  label: 'Horizontal rule'
  class: Drupal\webform\Plugin\WebformElement\WebformHorizontalRule
  provider: webform
  input: false
  container: false
  root: false
  multiple: false
webform_image_file:
  dependencies:
    - file
  default_key: ''
  category: 'File upload elements'
  description: 'Provides a form element for uploading and saving an image file.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: true
  deprecated: false
  deprecated_message: ''
  id: webform_image_file
  label: 'Image file'
  class: Drupal\webform\Plugin\WebformElement\WebformImageFile
  provider: webform
  input: true
  container: false
  root: false
  multiple: true
webform_image_select:
  dependencies: {  }
  default_key: ''
  category: 'Options elements'
  description: 'Provides a form element for selecting images.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: false
  deprecated: false
  deprecated_message: ''
  id: webform_image_select
  label: 'Image select'
  class: Drupal\webform_image_select\Plugin\WebformElement\WebformImageSelect
  provider: webform_image_select
  input: true
  container: false
  root: false
  multiple: true
webform_likert:
  dependencies: {  }
  default_key: ''
  category: 'Options elements'
  description: 'Provides a form element where users can respond to multiple questions using a <a href="https://en.wikipedia.org/wiki/Likert_scale">Likert</a> scale.'
  hidden: false
  multiline: true
  composite: true
  states_wrapper: false
  deprecated: false
  deprecated_message: ''
  id: webform_likert
  label: Likert
  class: Drupal\webform\Plugin\WebformElement\WebformLikert
  provider: webform
  input: true
  container: false
  root: false
  multiple: false
webform_link:
  dependencies: {  }
  default_key: ''
  category: 'Composite elements'
  description: 'Provides a form element to display a link.'
  hidden: false
  multiline: false
  composite: true
  states_wrapper: true
  deprecated: false
  deprecated_message: ''
  id: webform_link
  label: Link
  class: Drupal\webform\Plugin\WebformElement\WebformLink
  provider: webform
  input: true
  container: false
  root: false
  multiple: true
webform_location_geocomplete:
  dependencies: {  }
  default_key: ''
  category: 'Composite elements'
  description: 'Provides a form element to collect valid location information (address, longitude, latitude, geolocation) using Google Maps API''s Geocoding and Places Autocomplete.'
  hidden: false
  multiline: true
  composite: true
  states_wrapper: true
  deprecated: true
  deprecated_message: 'The jQuery: Geocoding and Places Autocomplete Plugin library is not being maintained. It has been <a href="https://www.drupal.org/node/2991275">deprecated</a> and will be removed before Webform 8.x-5.0.'
  id: webform_location_geocomplete
  label: 'Location (Geocomplete)'
  class: Drupal\webform_location_geocomplete\Plugin\WebformElement\WebformLocationGeocomplete
  provider: webform_location_geocomplete
  input: true
  container: false
  root: false
  multiple: true
webform_location_places:
  dependencies: {  }
  default_key: ''
  category: 'Composite elements'
  description: 'Provides a form element to collect valid location information (address, longitude, latitude, geolocation) using Algolia Places.'
  hidden: false
  multiline: true
  composite: true
  states_wrapper: true
  deprecated: false
  deprecated_message: ''
  id: webform_location_places
  label: 'Location (Algolia Places)'
  class: Drupal\webform\Plugin\WebformElement\WebformLocationPlaces
  provider: webform
  input: true
  container: false
  root: false
  multiple: true
webform_mapping:
  dependencies: {  }
  default_key: ''
  category: 'Advanced elements'
  description: 'Provides a form element where source values can mapped to destination values.'
  hidden: false
  multiline: true
  composite: true
  states_wrapper: false
  deprecated: false
  deprecated_message: ''
  id: webform_mapping
  label: Mapping
  class: Drupal\webform\Plugin\WebformElement\WebformMapping
  provider: webform
  input: true
  container: false
  root: false
  multiple: false
webform_markup:
  dependencies: {  }
  default_key: markup
  category: 'Markup elements'
  description: 'Provides an element to render basic HTML markup.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: true
  deprecated: false
  deprecated_message: ''
  id: webform_markup
  label: 'Basic HTML'
  class: Drupal\webform\Plugin\WebformElement\WebformMarkup
  provider: webform
  input: false
  container: false
  root: false
  multiple: false
webform_message:
  dependencies: {  }
  default_key: ''
  category: 'Markup elements'
  description: 'Provides an element to render custom, dismissible, inline status messages.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: false
  deprecated: false
  deprecated_message: ''
  id: webform_message
  label: Message
  class: Drupal\webform\Plugin\WebformElement\WebformMessage
  provider: webform
  input: false
  container: false
  root: false
  multiple: false
webform_more:
  dependencies: {  }
  default_key: ''
  category: 'Markup elements'
  description: 'Provides a more slideout element.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: false
  deprecated: false
  deprecated_message: ''
  id: webform_more
  label: More
  class: Drupal\webform\Plugin\WebformElement\WebformMore
  provider: webform
  input: false
  container: false
  root: false
  multiple: false
webform_name:
  dependencies: {  }
  default_key: ''
  category: 'Composite elements'
  description: 'Provides a form element to collect a person''s full name.'
  hidden: false
  multiline: true
  composite: true
  states_wrapper: true
  deprecated: false
  deprecated_message: ''
  id: webform_name
  label: Name
  class: Drupal\webform\Plugin\WebformElement\WebformName
  provider: webform
  input: true
  container: false
  root: false
  multiple: true
'webform_options_custom:buttons':
  dependencies: {  }
  default_key: ''
  category: 'Custom elements'
  description: 'An example of custom buttons.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: false
  deprecated: false
  deprecated_message: ''
  id: webform_options_custom
  label: Buttons
  deriver: Drupal\webform_options_custom\Plugin\Derivative\WebformOptionsCustomDeriver
  class: Drupal\webform_options_custom\Plugin\WebformElement\WebformOptionsCustom
  provider: webform_options_custom
  input: true
  container: false
  root: false
  multiple: true
'webform_options_custom:us_states':
  dependencies: {  }
  default_key: ''
  category: 'Custom elements'
  description: 'A clickable map of U.S. states.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: false
  deprecated: false
  deprecated_message: ''
  id: webform_options_custom
  label: 'U.S. states'
  deriver: Drupal\webform_options_custom\Plugin\Derivative\WebformOptionsCustomDeriver
  class: Drupal\webform_options_custom\Plugin\WebformElement\WebformOptionsCustom
  provider: webform_options_custom
  input: true
  container: false
  root: false
  multiple: true
webform_radios_other:
  dependencies: {  }
  default_key: ''
  category: 'Options elements'
  description: 'Provides a form element for a set of radio buttons, with the ability to enter a custom value.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: false
  deprecated: false
  deprecated_message: ''
  id: webform_radios_other
  label: 'Radios other'
  class: Drupal\webform\Plugin\WebformElement\WebformRadiosOther
  provider: webform
  input: true
  container: false
  root: false
  multiple: false
webform_rating:
  dependencies: {  }
  default_key: ''
  category: 'Advanced elements'
  description: 'Provides a form element to rate something using an attractive voting widget.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: false
  deprecated: false
  deprecated_message: ''
  id: webform_rating
  label: Rating
  class: Drupal\webform\Plugin\WebformElement\WebformRating
  provider: webform
  input: true
  container: false
  root: false
  multiple: false
webform_same:
  dependencies: {  }
  default_key: ''
  category: 'Advanced elements'
  description: 'Provides a form element for syncing the value of two elements.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: false
  deprecated: false
  deprecated_message: ''
  id: webform_same
  label: 'Same as…'
  class: Drupal\webform\Plugin\WebformElement\WebformSame
  provider: webform
  input: true
  container: false
  root: false
  multiple: false
webform_section:
  dependencies: {  }
  default_key: ''
  category: Containers
  description: 'Provides an element for a section/group of form elements.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: false
  deprecated: false
  deprecated_message: ''
  id: webform_section
  label: Section
  class: Drupal\webform\Plugin\WebformElement\WebformSection
  provider: webform
  input: false
  container: true
  root: false
  multiple: false
webform_select_other:
  dependencies: {  }
  default_key: ''
  category: 'Options elements'
  description: 'Provides a form element for a drop-down menu or scrolling selection box, with the ability to enter a custom value.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: false
  deprecated: false
  deprecated_message: ''
  id: webform_select_other
  label: 'Select other'
  class: Drupal\webform\Plugin\WebformElement\WebformSelectOther
  provider: webform
  input: true
  container: false
  root: false
  multiple: true
webform_signature:
  dependencies: {  }
  default_key: ''
  category: 'Advanced elements'
  description: 'Provides a form element to collect electronic signatures from users.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: false
  deprecated: false
  deprecated_message: ''
  id: webform_signature
  label: Signature
  class: Drupal\webform\Plugin\WebformElement\WebformSignature
  provider: webform
  input: true
  container: false
  root: false
  multiple: false
webform_table_sort:
  dependencies: {  }
  default_key: ''
  category: 'Options elements'
  description: 'Provides a form element for a table of values that can be sorted.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: false
  deprecated: false
  deprecated_message: ''
  id: webform_table_sort
  label: 'Table sort'
  class: Drupal\webform\Plugin\WebformElement\WebformTableSort
  provider: webform
  input: true
  container: false
  root: false
  multiple: true
webform_tableselect_sort:
  dependencies: {  }
  default_key: ''
  category: 'Options elements'
  description: 'Provides a form element for a table with radios or checkboxes in left column that can be sorted.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: false
  deprecated: false
  deprecated_message: ''
  id: webform_tableselect_sort
  label: 'Tableselect sort'
  class: Drupal\webform\Plugin\WebformElement\WebformTableSelectSort
  provider: webform
  input: true
  container: false
  root: false
  multiple: true
webform_telephone:
  dependencies: {  }
  default_key: ''
  category: 'Composite elements'
  description: 'Provides a form element to display a telephone number with type and extension.'
  hidden: false
  multiline: false
  composite: true
  states_wrapper: true
  deprecated: false
  deprecated_message: ''
  id: webform_telephone
  label: 'Telephone advanced'
  class: Drupal\webform\Plugin\WebformElement\WebformTelephone
  provider: webform
  input: true
  container: false
  root: false
  multiple: true
webform_term_checkboxes:
  dependencies:
    - taxonomy
  default_key: ''
  category: 'Entity reference elements'
  description: 'Provides a form element to select a single or multiple terms displayed as hierarchical tree or as breadcrumbs using checkboxes.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: false
  deprecated: false
  deprecated_message: ''
  id: webform_term_checkboxes
  label: 'Term checkboxes'
  class: Drupal\webform\Plugin\WebformElement\WebformTermCheckboxes
  provider: webform
  input: true
  container: false
  root: false
  multiple: true
webform_term_select:
  dependencies:
    - taxonomy
  default_key: ''
  category: 'Entity reference elements'
  description: 'Provides a form element to select a single or multiple terms displayed as hierarchical tree or as breadcrumbs using a select menu.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: false
  deprecated: false
  deprecated_message: ''
  id: webform_term_select
  label: 'Term select'
  class: Drupal\webform\Plugin\WebformElement\WebformTermSelect
  provider: webform
  input: true
  container: false
  root: false
  multiple: true
webform_terms_of_service:
  dependencies: {  }
  default_key: terms_of_service
  category: 'Advanced elements'
  description: 'Provides a terms of service element.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: false
  deprecated: false
  deprecated_message: ''
  id: webform_terms_of_service
  label: 'Terms of service'
  class: Drupal\webform\Plugin\WebformElement\WebformTermsOfService
  provider: webform
  input: true
  container: false
  root: false
  multiple: false
webform_time:
  dependencies: {  }
  default_key: ''
  category: 'Date/time elements'
  description: 'Provides a form element for time selection.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: false
  deprecated: false
  deprecated_message: ''
  id: webform_time
  api: 'http://www.w3schools.com/tags/tag_time.asp'
  label: Time
  class: Drupal\webform\Plugin\WebformElement\WebformTime
  provider: webform
  input: true
  container: false
  root: false
  multiple: true
webform_toggle:
  dependencies: {  }
  default_key: ''
  category: 'Advanced elements'
  description: 'Provides a form element for toggling a single on/off state.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: false
  deprecated: true
  deprecated_message: 'The Toogles library is not being maintained and has major accessibility issues. It has been <a href="https://www.drupal.org/project/webform/issues/2890861">deprecated</a> and will be removed before Webform 8.x-5.0.'
  id: webform_toggle
  label: Toggle
  class: Drupal\webform_toggles\Plugin\WebformElement\WebformToggle
  provider: webform_toggles
  input: true
  container: false
  root: false
  multiple: false
webform_toggles:
  dependencies: {  }
  default_key: ''
  category: 'Options elements'
  description: 'Provides a form element for toggling multiple on/off states.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: false
  deprecated: true
  deprecated_message: 'The Toogles library is not being maintained and has major accessibility issues. It has been <a href="https://www.drupal.org/project/webform/issues/2890861">deprecated</a> and will be removed before Webform 8.x-5.0.'
  id: webform_toggles
  label: Toggles
  class: Drupal\webform_toggles\Plugin\WebformElement\WebformToggles
  provider: webform_toggles
  input: true
  container: false
  root: false
  multiple: true
webform_variant:
  dependencies: {  }
  default_key: ''
  category: 'Advanced elements'
  description: 'Provides a form element for enabling and tracking webform variants.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: false
  deprecated: false
  deprecated_message: ''
  id: webform_variant
  label: 'Variant [EXPERIMENTAL]'
  class: Drupal\webform\Plugin\WebformElement\WebformVariant
  provider: webform
  input: true
  container: false
  root: false
  multiple: false
webform_video_file:
  dependencies:
    - file
  default_key: ''
  category: 'File upload elements'
  description: 'Provides a form element for uploading and saving a video file.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: true
  deprecated: false
  deprecated_message: ''
  id: webform_video_file
  label: 'Video file'
  class: Drupal\webform\Plugin\WebformElement\WebformVideoFile
  provider: webform
  input: true
  container: false
  root: false
  multiple: true
webform_wizard_page:
  dependencies: {  }
  default_key: ''
  category: Wizard
  description: 'Provides an element to display multiple form elements as a page in a multi-step form wizard.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: false
  deprecated: false
  deprecated_message: ''
  id: webform_wizard_page
  label: 'Wizard page'
  class: Drupal\webform\Plugin\WebformElement\WebformWizardPage
  provider: webform
  input: false
  container: true
  root: true
  multiple: false
webform_table:
  dependencies: {  }
  default_key: ''
  category: Containers
  description: 'Provides an element to render a table.'
  hidden: false
  multiline: false
  composite: false
  states_wrapper: false
  deprecated: false
  deprecated_message: ''
  id: webform_table
  label: Table
  class: Drupal\webform\Plugin\WebformElement\WebformTable
  provider: webform
  input: false
  container: true
  root: false
  multiple: false
webform_table_row:
  dependencies: {  }
  default_key: ''
  category: 'Containers'
  description: 'Provides an element to render a table row.'
  hidden: true
  multiline: false
  composite: false
  states_wrapper: false
  deprecated: false
  deprecated_message: ''
  id: webform_table_row
  label: 'Table row'
  class: Drupal\webform\Plugin\WebformElement\WebformTableRow
  provider: webform
  input: false
  container: true
  root: false
  multiple: false
webform_scale:
  dependencies: {  }
  default_key: ''
  category: 'Advanced elements'
  description: 'Provides a form element for input of a numeric scale.'
  id: webform_scale
  label: Scale
  class: Drupal\webform\Plugin\WebformElement\WebformScale
  provider: webform
  input: true
  container: false
  root: false
  multiple: false
  hidden: false
  multiline: false
  composite: false
  states_wrapper: false
  deprecated: false
  deprecated_message: ''
YAML;

    return Yaml::decode($yaml);
  }

}
