<?php

namespace Drupal\webform\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Defines the interface for webform elements.
 *
 * @see \Drupal\webform\Annotation\WebformElement
 * @see \Drupal\webform\Plugin\WebformElementBase
 * @see \Drupal\webform\Plugin\WebformElementManager
 * @see \Drupal\webform\Plugin\WebformElementManagerInterface
 * @see plugin_api
 */
interface WebformElementInterface extends PluginInspectionInterface, PluginFormInterface, ContainerFactoryPluginInterface, WebformEntityInjectionInterface {

  /****************************************************************************/
  // Property methods.
  /****************************************************************************/

  /**
   * Get default properties.
   *
   * @return array
   *   An associative array containing default element properties.
   */
  public function getDefaultProperties();

  /**
   * Get translatable properties.
   *
   * @return array
   *   An associative array containing translatable element properties.
   */
  public function getTranslatableProperties();

  /**
   * Get an element's default property value.
   *
   * @param string $property_name
   *   An element's property name.
   *
   * @return mixed
   *   An element's default property value or NULL if default property does not
   *   exist.
   */
  public function getDefaultProperty($property_name);

  /**
   * Get an element's property value.
   *
   * @param array $element
   *   An element.
   * @param string $property_name
   *   An element's property name.
   *
   * @return mixed
   *   An element's property value, default value, or NULL if
   *   property does not exist.
   */
  public function getElementProperty(array $element, $property_name);

  /**
   * Determine if the element supports a specified property.
   *
   * @param string $property_name
   *   An element's property name.
   *
   * @return bool
   *   TRUE if the element supports a specified property.
   */
  public function hasProperty($property_name);

  /****************************************************************************/
  // Definition and meta data methods.
  /****************************************************************************/

  /**
   * Get the Webform element's form element class definition.
   *
   * We use the plugin's base id here to support plugin derivatives.
   *
   * @return string
   *   A form element class definition.
   */
  public function getFormElementClassDefinition();

  /**
   * Get the URL for the element's API documentation.
   *
   * @return \Drupal\Core\Url|null
   *   The URL for the element's API documentation.
   */
  public function getPluginApiUrl();

  /**
   * Get link to element's API documentation.
   *
   * @return \Drupal\Core\GeneratedLink|string
   *   A link to element's API documentation.
   */
  public function getPluginApiLink();

  /**
   * Gets the label of the plugin instance.
   *
   * @return string
   *   The label of the plugin instance.
   */
  public function getPluginLabel();

  /**
   * Gets the description of the plugin instance.
   *
   * @return string
   *   The description of the plugin instance.
   */
  public function getPluginDescription();

  /**
   * Gets the category of the plugin instance.
   *
   * @return string
   *   The category of the plugin instance.
   */
  public function getPluginCategory();

  /**
   * Gets the type name (aka id) of the plugin instance with the 'webform_' prefix.
   *
   * @return string
   *   The type name of the plugin instance.
   */
  public function getTypeName();

  /**
   * Gets the element's default key.
   *
   * @return string
   *   The element's default key.
   */
  public function getDefaultKey();

  /**
   * Checks if the element carries a value.
   *
   * @param array $element
   *   An element.
   *
   * @return bool
   *   TRUE if the element carries a value.
   */
  public function isInput(array $element);

  /**
   * Checks if the element has a wrapper.
   *
   * @param array $element
   *   An element.
   *
   * @return bool
   *   TRUE if the element has a wrapper.
   */
  public function hasWrapper(array $element);

  /**
   * Checks if the element is a container that can contain elements.
   *
   * @param array $element
   *   An element.
   *
   * @return bool
   *   TRUE if the element is a container that can contain elements.
   */
  public function isContainer(array $element);

  /**
   * Checks if the element is a root element.
   *
   * @return bool
   *   TRUE if the element is a root element.
   */
  public function isRoot();

  /**
   * Checks if the element value could contain multiple lines.
   *
   * @param array $element
   *   An element.
   *
   * @return bool
   *   TRUE if the element value could contain multiple lines.
   */
  public function isMultiline(array $element);

  /**
   * Checks if the element is a composite element.
   *
   * @return bool
   *   TRUE if the element is a composite element.
   */
  public function isComposite();

  /**
   * Checks if the element is hidden.
   *
   * @return bool
   *   TRUE if the element is hidden.
   */
  public function isHidden();

  /**
   * Checks if the element is excluded via webform.settings.
   *
   * @return bool
   *   TRUE if the element is excluded.
   */
  public function isExcluded();

  /**
   * Checks if the element is enabled.
   *
   * @return bool
   *   TRUE if the element is enabled.
   */
  public function isEnabled();

  /**
   * Checks if the element is disabled.
   *
   * @return bool
   *   TRUE if the element is disabled.
   */
  public function isDisabled();

  /**
   * Checks if the element supports multiple values.
   *
   * @return bool
   *   TRUE if the element supports multiple values.
   */
  public function supportsMultipleValues();

  /**
   * Checks if the element uses the 'webform_multiple' element.
   *
   * @return bool
   *   TRUE if the element supports multiple values.
   */
  public function hasMultipleWrapper();

  /**
   * Checks if the element value has multiple values.
   *
   * @param array $element
   *   An element.
   *
   * @return bool
   *   TRUE if the element value has multiple values.
   */
  public function hasMultipleValues(array $element);

  /**
   * Determine if the element is or includes a managed_file upload element.
   *
   * @param array $element
   *   An element.
   *
   * @return bool
   *   TRUE if the element is or includes a managed_file upload element.
   */
  public function hasManagedFiles(array $element);

  /**
   * Retrieves the default properties for the defined element type.
   *
   * @return array
   *   An associative array describing the element types being defined.
   *
   * @see \Drupal\Core\Render\ElementInfoManagerInterface::getInfo
   */
  public function getInfo();

  /****************************************************************************/
  // Element relationship methods.
  /****************************************************************************/

  /**
   * Get related element types.
   *
   * @param array $element
   *   The element.
   *
   * @return array
   *   An array containing related element types.
   */
  public function getRelatedTypes(array $element);

  /****************************************************************************/
  // Element rendering methods.
  /****************************************************************************/

  /**
   * Initialize an element to be displayed, rendered, or exported.
   *
   * @param array $element
   *   An element.
   */
  public function initialize(array &$element);

  /**
   * Prepare an element to be rendered within a webform.
   *
   * @param array $element
   *   An element.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission. Webform submission is optional
   *   since it is not used by composite sub elements.
   *
   * @see \Drupal\webform\Element\WebformCompositeBase::processWebformComposite
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL);

  /**
   * Finalize an element to be rendered within a webform.
   *
   * @param array $element
   *   An element.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission. Webform submission is optional
   *   since it is not used by composite sub elements.
   *
   * @see \Drupal\webform\Element\WebformCompositeBase::processWebformComposite
   */
  public function finalize(array &$element, WebformSubmissionInterface $webform_submission = NULL);

  /**
   * Alter an element's associated form.
   *
   * @param array $element
   *   An element.
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function alterForm(array &$element, array &$form, FormStateInterface $form_state);

  /**
   * Check element access (rules).
   *
   * @param string $operation
   *   The operation access should be checked for.
   *   Usually "create", "update", or "view".
   * @param array $element
   *   An element.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user session for which to check access.
   *
   * @return bool
   *   TRUE is the element can be accessed by the user.
   *
   * @throws |\Exception
   *   Throws exception when the webform entity has not been set for
   *   the element.
   *
   * @see \Drupal\webform\WebformAccessRulesManagerInterface::checkWebformAccess
   */
  public function checkAccessRules($operation, array $element, AccountInterface $account = NULL);

  /**
   * Replace tokens for all element properties.
   *
   * @param array $element
   *   An element.
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   A webform or webform submission entity.
   */
  public function replaceTokens(array &$element, EntityInterface $entity = NULL);

  /**
   * Display element disabled warning.
   *
   * @param array $element
   *   An element.
   */
  public function displayDisabledWarning(array $element);

  /**
   * Set an element's default value using saved data.
   *
   * The method allows the submission's 'saved' #default_value to be different
   * from the element's #default_value.
   *
   * @param array $element
   *   An element.
   *
   * @see \Drupal\webform\Plugin\WebformElement\DateBase::setDefaultValue
   * @see \Drupal\webform\Plugin\WebformElement\EntityAutocomplete::setDefaultValue
   */
  public function setDefaultValue(array &$element);

  /**
   * Get an element's label (#title or #webform_key).
   *
   * @param array $element
   *   An element.
   *
   * @return string
   *   An element's label (#title or #webform_key).
   */
  public function getLabel(array $element);

  /**
   * Get an element's admin label (#admin_title, #title or #webform_key).
   *
   * @param array $element
   *   An element.
   *
   * @return string
   *   An element's label (#admin_title, #title or #webform_key).
   */
  public function getAdminLabel(array $element);

  /**
   * Get an element's key/name.
   *
   * @param array $element
   *   An element.
   *
   * @return string
   *   An element's key/name.
   */
  public function getKey(array $element);

  /****************************************************************************/
  // Display submission value methods.
  /****************************************************************************/

  /**
   * Build an element as HTML element.
   *
   * @param array $element
   *   An element.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param array $options
   *   An array of options.
   *
   * @return array
   *   A render array representing an element as HTML.
   */
  public function buildHtml(array $element, WebformSubmissionInterface $webform_submission, array $options = []);

  /**
   * Build an element as text element.
   *
   * @param array $element
   *   An element.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param array $options
   *   An array of options.
   *
   * @return array
   *   A render array representing an element as text.
   */
  public function buildText(array $element, WebformSubmissionInterface $webform_submission, array $options = []);

  /**
   * Format an element's value as HTML.
   *
   * @param array $element
   *   An element.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param array $options
   *   An array of options.
   *
   * @return array|string
   *   The element's value formatted as an HTML string or a render array.
   */
  public function formatHtml(array $element, WebformSubmissionInterface $webform_submission, array $options = []);

  /**
   * Format an element's value as plain text.
   *
   * @param array $element
   *   An element.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param array $options
   *   An array of options.
   *
   * @return array|string
   *   The element's value formatted as plain text or a render array.
   */
  public function formatText(array $element, WebformSubmissionInterface $webform_submission, array $options = []);

  /**
   * Determine if an element's has a submission value.
   *
   * @param array $element
   *   An element.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param array $options
   *   An array of options.
   *
   * @return bool
   *   TRUE if them element's has a submission value.
   */
  public function hasValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []);

  /**
   * Get an element's submission value.
   *
   * @param array $element
   *   An element.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param array $options
   *   An array of options.
   *
   * @return array|string
   *   The element's submission value.
   */
  public function getValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []);

  /**
   * Get an element's submission raw value.
   *
   * @param array $element
   *   An element.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param array $options
   *   An array of options.
   *
   * @return array|string
   *   The element's submission value.
   */
  public function getRawValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []);

  /**
   * Get an element's available single value formats.
   *
   * @return array
   *   An associative array of single value formats containing name/label pairs.
   */
  public function getItemFormats();

  /**
   * Get an element's default single value format name.
   *
   * @return string
   *   An element's default single value format name.
   */
  public function getItemDefaultFormat();

  /**
   * Get element's single value format name by looking for '#format' property, global settings, and finally default settings.
   *
   * @param array $element
   *   An element.
   *
   * @return string
   *   An element's format name.
   */
  public function getItemFormat(array $element);

  /**
   * Get an element's available multiple value formats.
   *
   * @return array
   *   An associative array of multiple value formats containing name/label pairs.
   */
  public function getItemsFormats();

  /**
   * Get an element's default multiple value format name.
   *
   * @return string
   *   An element's default multiple value format name.
   */
  public function getItemsDefaultFormat();

  /**
   * Get element's multiple value format name by looking for '#format' property, global settings, and finally default settings.
   *
   * @param array $element
   *   An element.
   *
   * @return string
   *   An element's format name.
   */
  public function getItemsFormat(array $element);

  /**
   * Checks if an empty element is excluded.
   *
   * @param array $element
   *   An element.
   * @param array $options
   *   An array of options.
   *
   * @return bool
   *   TRUE if an empty element is excluded.
   */
  public function isEmptyExcluded(array $element, array $options);

  /****************************************************************************/
  // Preview method.
  /****************************************************************************/

  /**
   * Generate a renderable preview of the element.
   *
   * @return array
   *   A renderable preview of the element.
   */
  public function preview();

  /****************************************************************************/
  // Test methods.
  /****************************************************************************/

  /**
   * Get test values for an element.
   *
   * @param array $element
   *   An element.
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   * @param array $options
   *   Options used to generate a test value.
   *
   * @return mixed
   *   A test value for an element.
   */
  public function getTestValues(array $element, WebformInterface $webform, array $options);

  /****************************************************************************/
  // Table methods.
  /****************************************************************************/

  /**
   * Get element's table column(s) settings.
   *
   * @param array $element
   *   An element.
   *
   * @return array
   *   An associative array containing an element's table column(s).
   */
  public function getTableColumn(array $element);

  /**
   * Format an element's table column value.
   *
   * @param array $element
   *   An element.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param array $options
   *   An array of options returned from ::getTableColumns().
   *
   * @return array|string
   *   The element's value formatted as an HTML string or a render array.
   */
  public function formatTableColumn(array $element, WebformSubmissionInterface $webform_submission, array $options = []);

  /****************************************************************************/
  // Export methods.
  /****************************************************************************/

  /**
   * Get an element's default export options.
   *
   * @return array
   *   An associative array containing an element's default export options.
   */
  public function getExportDefaultOptions();

  /**
   * Get an element's export options webform.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $export_options
   *   An associative array of default values.
   *
   * @return array
   *   An associative array contain an element's export option webform.
   */
  public function buildExportOptionsForm(array &$form, FormStateInterface $form_state, array $export_options);

  /**
   * Get an associative array of element properties from configuration webform.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   An associative array of element properties.
   */
  public function getConfigurationFormProperties(array &$form, FormStateInterface $form_state);

  /**
   * Build an element's export header.
   *
   * @param array $element
   *   An element.
   * @param array $options
   *   An associative array of export options.
   *
   * @return array
   *   An array containing the element's export headers.
   *
   * @see \Drupal\webform\WebformSubmissionExporterInterface::getDefaultExportOptions
   */
  public function buildExportHeader(array $element, array $options);

  /**
   * Build an element's export row.
   *
   * @param array $element
   *   An element.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param array $export_options
   *   An associative array of export options.
   *
   * @return array
   *   An array containing the element's export row.
   *
   * @see \Drupal\webform\WebformSubmissionExporterInterface::getDefaultExportOptions
   */
  public function buildExportRecord(array $element, WebformSubmissionInterface $webform_submission, array $export_options);

  /****************************************************************************/
  // #states API methods.
  /****************************************************************************/

  /**
   * Get an element's supported states as options.
   *
   * @return array
   *   An array of element states.
   */
  public function getElementStateOptions();

  /**
   * Get an element's selectors as options.
   *
   * @param array $element
   *   An element.
   *
   * @return array
   *   An array of element selectors.
   *
   * @see \Drupal\webform\Entity\Webform::getElementsSelectorSourceOption
   */
  public function getElementSelectorOptions(array $element);

  /**
   * Get an element's selectors source values.
   *
   * @param array $element
   *   An element.
   *
   * @return array
   *   An array of element selectors source values.
   *
   * @see \Drupal\webform\Entity\Webform::getElementsSelectorSourceValues
   */
  public function getElementSelectorSourceValues(array $element);

  /**
   * Get an element's (sub)input selector value.
   *
   * @param string $selector
   *   CSS :input selector.
   * @param string $trigger
   *   Trigger from #states.
   * @param array $element
   *   An element.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   *
   * @return mixed
   *   The element input's value.
   */
  public function getElementSelectorInputValue($selector, $trigger, array $element, WebformSubmissionInterface $webform_submission);

  /****************************************************************************/
  // Operation methods.
  /****************************************************************************/

  /**
   * Changes the values of an entity before it is created.
   *
   * @param array $element
   *   An element.
   * @param mixed[] &$values
   *   An array of values to set, keyed by property name.
   */
  public function preCreate(array &$element, array &$values);

  /**
   * Acts on a webform submission element after it is created.
   *
   * @param array $element
   *   An element.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   */
  public function postCreate(array &$element, WebformSubmissionInterface $webform_submission);

  /**
   * Acts on loaded webform submission.
   *
   * @param array $element
   *   An element.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   */
  public function postLoad(array &$element, WebformSubmissionInterface $webform_submission);

  /**
   * Acts on a webform submission element before the presave hook is invoked.
   *
   * @param array $element
   *   An element.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   */
  public function preSave(array &$element, WebformSubmissionInterface $webform_submission);

  /**
   * Acts on a saved webform submission element before the insert or update hook is invoked.
   *
   * @param array $element
   *   An element.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param bool $update
   *   TRUE if the entity has been updated, or FALSE if it has been inserted.
   */
  public function postSave(array &$element, WebformSubmissionInterface $webform_submission, $update = TRUE);

  /**
   * Delete any additional value associated with an element.
   *
   * Currently only applicable to file uploads.
   *
   * @param array $element
   *   An element.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   */
  public function postDelete(array &$element, WebformSubmissionInterface $webform_submission);

  /****************************************************************************/
  // Element configuration methods.
  /****************************************************************************/

  /**
   * Gets the actual configuration webform array to be built.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   An associative array contain the element's configuration webform without
   *   any default values.
   */
  public function form(array $form, FormStateInterface $form_state);

}
