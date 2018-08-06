<?php

namespace Drupal\webform;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\OptGroup;
use Drupal\Core\Link;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\webform\Entity\WebformOptions;
use Drupal\webform\Utility\WebformArrayHelper;
use Drupal\webform\Utility\WebformElementHelper;
use Drupal\webform\Utility\WebformReflectionHelper;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base class for a webform element.
 *
 * @see \Drupal\webform\WebformElementInterface
 * @see \Drupal\webform\WebformElementManager
 * @see \Drupal\webform\WebformElementManagerInterface
 * @see plugin_api
 */
class WebformElementBase extends PluginBase implements WebformElementInterface {

  use StringTranslationTrait;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * A element info manager.
   *
   * @var \Drupal\Core\Render\ElementInfoManagerInterface
   */
  protected $elementInfo;

  /**
   * The webform element manager.
   *
   * @var \Drupal\webform\WebformElementManagerInterface
   */
  protected $elementManager;

  /**
   * The token manager.
   *
   * @var \Drupal\webform\WebformTranslationManagerInterface
   */
  protected $tokenManager;

  /**
   * The libraries manager.
   *
   * @var \Drupal\webform\WebformLibrariesManagerInterface
   */
  protected $librariesManager;

  /**
   * The webform submission storage.
   *
   * @var \Drupal\webform\WebformSubmissionStorageInterface
   */
  protected $submissionStorage;

  /**
   * Constructs a WebformElementBast object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Render\ElementInfoManagerInterface $element_info
   *   The element info manager.
   * @param \Drupal\webform\WebformElementManagerInterface $element_manager
   *   The webform element manager.
   * @param \Drupal\webform\WebformTokenManagerInterface $token_manager
   *   The token manager.
   * @param \Drupal\webform\WebformLibrariesManagerInterface $libraries_manager
   *   The libraries manager.
   * @param \Drupal\webform\WebformSubmissionStorageInterface $webform_submission_storage
   *   The webform submission storage.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerInterface $logger, ConfigFactoryInterface $config_factory, AccountInterface $current_user, EntityTypeManagerInterface $entity_type_manager, ElementInfoManagerInterface $element_info, WebformElementManagerInterface $element_manager, WebformTokenManagerInterface $token_manager, WebformLibrariesManagerInterface $libraries_manager, WebformSubmissionStorageInterface $webform_submission_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->logger = $logger;
    $this->configFactory = $config_factory;
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->elementInfo = $element_info;
    $this->elementManager = $element_manager;
    $this->tokenManager = $token_manager;
    $this->librariesManager = $libraries_manager;
    $this->submissionStorage = $webform_submission_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory')->get('webform'),
      $container->get('config.factory'),
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.element_info'),
      $container->get('plugin.manager.webform.element'),
      $container->get('webform.token_manager'),
      $container->get('webform.libraries_manager'),
      $container->get('entity_type.manager')->getStorage('webform_submission')
    );
  }

  /****************************************************************************/
  // Property methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   *
   * Only a few elements don't inherit these default properties.
   *
   * @see \Drupal\webform\Plugin\WebformElement\Textarea
   * @see \Drupal\webform\Plugin\WebformElement\WebformLikert
   * @see \Drupal\webform\Plugin\WebformElement\WebformCompositeBase
   * @see \Drupal\webform\Plugin\WebformElement\ContainerBase
   */
  public function getDefaultProperties() {
    $properties = [
      // Element settings.
      'title' => '',
      'description' => '',
      'default_value' => '',
      // Form display.
      'title_display' => '',
      'description_display' => '',
      'field_prefix' => '',
      'field_suffix' => '',
      // Form validation.
      'required' => FALSE,
      'required_error' => '',
      'unique' => FALSE,
      'unique_error' => '',
      // Attributes.
      'wrapper_attributes' => [],
      'attributes' => [],
      // Submission display.
      'format' => $this->getItemDefaultFormat(),
      'format_items' => $this->getItemsDefaultFormat(),
    ];

    $properties += $this->getDefaultBaseProperties();

    return $properties;
  }

  /**
   * Get default base properties used by all elements.
   *
   * @return array
   *   An associative array containing base properties used by all elements.
   */
  protected function getDefaultBaseProperties() {
    return [
      // Administration.
      'admin_title' => '',
      'private' => FALSE,
      // Flexbox.
      'flex' => 1,
      // Conditional logic.
      'states' => [],
      // Element access.
      'access_create_roles' => ['anonymous', 'authenticated'],
      'access_create_users' => [],
      'access_update_roles' => ['anonymous', 'authenticated'],
      'access_update_users' => [],
      'access_view_roles' => ['anonymous', 'authenticated'],
      'access_view_users' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslatableProperties() {
    return [
      'title',
      'description',
      'field_prefix',
      'field_suffix',
      'required_error',
      'unique_error',
      'admin_title',
      'placeholder',
      'markup',
      'test',
      'default_value',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function hasProperty($property_name) {
    $default_properties = $this->getDefaultProperties();
    return isset($default_properties[$property_name]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperty($property_name) {
    $default_properties = $this->getDefaultProperties();
    return (isset($default_properties[$property_name])) ? $default_properties[$property_name] : NULL;
  }

  /****************************************************************************/
  // Definition and meta data methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function getPluginApiUrl() {
    return (!empty($this->pluginDefinition['api'])) ? Url::fromUri($this->pluginDefinition['api']) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginApiLink() {
    $api_url = $this->getPluginApiUrl();
    return ($api_url) ? Link::fromTextAndUrl($this->getPluginLabel(), $api_url) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginLabel() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginDescription() {
    return $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function getTypeName() {
    return $this->pluginDefinition['id'];
  }

  /**
   * {@inheritdoc}
   */
  public function isInput(array $element) {
    return (!empty($element['#type'])) ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function hasWrapper(array $element) {
    return $this->hasProperty('wrapper_attributes');
  }

  /**
   * {@inheritdoc}
   */
  public function isContainer(array $element) {
    return ($this->isInput($element)) ? FALSE : TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function isRoot() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsMultipleValues() {
    return $this->hasProperty('multiple');
  }

  /**
   * {@inheritdoc}
   */
  public function hasMultipleWrapper() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function hasMultipleValues(array $element) {
    if ($this->hasProperty('multiple')) {
      if (isset($element['#multiple'])) {
        return $element['#multiple'];
      }
      else {
        $default_property = $this->getDefaultProperties();
        return $default_property['multiple'];
      }
    }
    else {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isMultiline(array $element) {
    $format = $this->getItemsFormat($element);
    if ($this->hasMultipleValues($element) && in_array($format, ['ol', 'ul', 'hr'])) {
      return TRUE;
    }
    else {
      return $this->pluginDefinition['multiline'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isComposite() {
    return $this->pluginDefinition['composite'];
  }

  /**
   * {@inheritdoc}
   */
  public function isHidden() {
    return $this->pluginDefinition['hidden'];
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return \Drupal::config('webform.settings')->get('elements.excluded_types.' . $this->pluginDefinition['id']) ? FALSE : TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function isDisabled() {
    return !$this->isEnabled();
  }

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return $this->elementInfo->getInfo($this->getPluginId());
  }

  /****************************************************************************/
  // Element relationship methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function getRelatedTypes(array $element) {
    $types = [];

    $parent_classes = WebformReflectionHelper::getParentClasses($this, 'WebformElementBase');

    $plugin_id = $this->getPluginId();
    $is_container = $this->isContainer($element);
    $has_multiple_values = $this->hasMultipleValues($element);
    $is_multiline = $this->isMultiline($element);

    $elements = $this->elementManager->getInstances();
    foreach ($elements as $element_name => $element_instance) {
      // Skip self.
      if ($plugin_id == $element_instance->getPluginId()) {
        continue;
      }

      // Skip disable or hidden.
      if (!$element_instance->isEnabled() || $element_instance->isHidden()) {
        continue;
      }

      // Compare element base (abstract) class.
      $element_instance_parent_classes = WebformReflectionHelper::getParentClasses($element_instance, 'WebformElementBase');
      if ($parent_classes[1] != $element_instance_parent_classes[1]) {
        continue;
      }

      // Compare container, supports/has multiple values, and multiline.
      if ($is_container != $element_instance->isContainer($element)) {
        continue;
      }
      if ($has_multiple_values != $element_instance->hasMultipleValues($element)) {
        continue;
      }
      if ($is_multiline != $element_instance->isMultiline($element)) {
        continue;
      }

      $types[$element_name] = $element_instance->getPluginLabel();
    }

    asort($types);
    return $types;
  }

  /****************************************************************************/
  // Element rendering methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function initialize(array &$element) {
    // Set element options.
    if (isset($element['#options'])) {
      $element['#options'] = WebformOptions::getElementOptions($element);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission) {
    // Add webform and webform_submission IDs to every element.
    $element['#webform'] = $webform_submission->getWebform()->id();
    $element['#webform_submission'] = $webform_submission->id();

    $attributes_property = ($this->hasWrapper($element)) ? '#wrapper_attributes' : '#attributes';

    // Check is the element is disabled and hide it.
    if ($this->isDisabled()) {
      if ($webform_submission->getWebform()->access('edit')) {
        $this->displayDisabledWarning($element);
      }
      $element['#access'] = FALSE;
    }

    // Apply element specific access rules.
    $operation = ($webform_submission->isCompleted()) ? 'update' : 'create';
    $element['#access'] = $this->checkAccessRules($operation, $element);

    // Add #allowed_tags.
    $allowed_tags = $this->configFactory->get('webform.settings')->get('elements.allowed_tags');
    switch ($allowed_tags) {
      case 'admin':
        $element['#allowed_tags'] = Xss::getAdminTagList();
        break;

      case 'html':
        $element['#allowed_tags'] = Xss::getHtmlTagList();
        break;

      default:
        $element['#allowed_tags'] = preg_split('/ +/', $allowed_tags);
        break;
    }

    // Add autocomplete attribute.
    if (isset($element['#autocomplete'])) {
      $element['#attributes']['autocomplete'] = $element['#autocomplete'];
    }

    // Add inline title display support.
    if (isset($element['#title_display']) && $element['#title_display'] == 'inline') {
      unset($element['#title_display']);
      $element['#wrapper_attributes']['class'][] = 'webform-element--title-inline';
    }

    // Add default description display.
    $default_description_display = $this->configFactory->get('webform.settings')->get('elements.default_description_display');
    if ($default_description_display && !isset($element['#description_display']) && $this->hasProperty('description_display')) {
      $element['#description_display'] = $default_description_display;
    }

    // Add tooltip description display support.
    if (isset($element['#description_display']) && $element['#description_display'] === 'tooltip') {
      $element['#description_display'] = 'invisible';
      $element[$attributes_property]['class'][] = 'js-webform-tooltip-element';
      $element[$attributes_property]['class'][] = 'webform-tooltip-element';
      $element['#attached']['library'][] = 'webform/webform.tooltip';
    }

    // Add iCheck support.
    if ($this->hasProperty('icheck') && $this->librariesManager->isIncluded('jquery.icheck')) {
      $icheck = NULL;
      $icheck_skin = NULL;
      if (isset($element['#icheck'])) {
        if ($element['#icheck'] != 'none') {
          $icheck = $element['#icheck'];
          $icheck_skin = strtok($element['#icheck'], '-');
        }
      }
      elseif ($default_icheck = $this->configFactory->get('webform.settings')->get('elements.default_icheck')) {
        $icheck = $default_icheck;
        $icheck_skin = strtok($default_icheck, '-');
      }
      if ($icheck) {
        if ($this->hasProperty('wrapper_attributes')) {
          $element['#wrapper_attributes']['data-webform-icheck'] = $icheck;
        }
        else {
          $element['#attributes']['data-webform-icheck'] = $icheck;
        }
        $element['#attached']['library'][] = 'webform/webform.element.icheck';
        $element['#attached']['library'][] = 'webform/libraries.jquery.icheck.' . $icheck_skin;
      }
    }

    // Add .webform-has-field-prefix and .webform-has-field-suffix class.
    if (!empty($element['#field_prefix'])) {
      $element[$attributes_property]['class'][] = 'webform-has-field-prefix';
    }
    if (!empty($element['#field_suffix'])) {
      $element[$attributes_property]['class'][] = 'webform-has-field-suffix';
    }

    if ($this->isInput($element)) {
      $type = $element['#type'];

      // Get and set the element's default #element_validate property so that
      // it is not skipped when custom callbacks are added to #element_validate.
      // @see \Drupal\Core\Render\Element\Color
      // @see \Drupal\Core\Render\Element\Number
      // @see \Drupal\Core\Render\Element\Email
      // @see \Drupal\Core\Render\Element\MachineName
      // @see \Drupal\Core\Render\Element\Url
      $element_validate = $this->elementInfo->getInfoProperty($type, '#element_validate', [])
        ?: $this->elementInfo->getInfoProperty("webform_$type", '#element_validate', []);
      if (!empty($element['#element_validate'])) {
        $element['#element_validate'] = array_merge($element_validate, $element['#element_validate']);
      }
      else {
        $element['#element_validate'] = $element_validate;
      }

      // Add webform element #minlength, #unique, and/or #multiple validation handler.
      if (isset($element['#minlength'])) {
        $element['#element_validate'][] = [get_class($this), 'validateMinlength'];
      }
      if (isset($element['#unique'])) {
        $element['#element_validate'][] = [get_class($this), 'validateUnique'];
      }
      if (isset($element['#multiple']) && $element['#multiple'] > 1) {
        $element['#element_validate'][] = [get_class($this), 'validateMultiple'];
      }
    }

    // Prepare Flexbox and #states wrapper.
    $this->prepareWrapper($element);

    // Replace tokens for all properties.
    $element = $this->tokenManager->replace($element, $webform_submission);
  }

  /**
   * {@inheritdoc}
   */
  public function finalize(array &$element, WebformSubmissionInterface $webform_submission) {
    // Prepare multiple element.
    $this->prepareMultipleWrapper($element);
  }

  /**
   * {@inheritdoc}
   */
  public function checkAccessRules($operation, array $element, AccountInterface $account = NULL) {
    // Respect elements that already have their #access set to FALSE.
    if (isset($element['#access']) && $element['#access'] === FALSE) {
      return FALSE;
    }

    if (!$account) {
      $account = $this->currentUser;
    }

    if (isset($element['#access_' . $operation . '_roles']) && !array_intersect($element['#access_' . $operation . '_roles'], $account->getRoles())) {
      return FALSE;
    }
    elseif (isset($element['#access_' . $operation . '_users']) && !in_array($account->id(), $element['#access_' . $operation . '_users'])) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Set an elements Flexbox and #states wrapper.
   *
   * @param array $element
   *   An element.
   */
  protected function prepareWrapper(array &$element) {
    // Fix #states wrapper.
    if ($this->pluginDefinition['states_wrapper']) {
      WebformElementHelper::fixStatesWrapper($element);
    }

    // Add flex(box) wrapper.
    if (!empty($element['#webform_parent_flexbox'])) {
      $flex = (isset($element['#flex'])) ? $element['#flex'] : 1;
      $element += ['#prefix' => '', '#suffix' => ''];
      $element['#prefix'] = '<div class="webform-flex webform-flex--' . $flex . '"><div class="webform-flex--container">' . $element['#prefix'];
      $element['#suffix'] = $element['#suffix'] . '</div></div>';
    }
  }

  /**
   * Set multiple element wrapper.
   *
   * @param array $element
   *   An element.
   */
  protected function prepareMultipleWrapper(array &$element) {
    if (!$this->hasMultipleValues($element) || !$this->hasMultipleWrapper() || empty($element['#multiple'])) {
      return;
    }

    // Set the multiple element.
    $element['#element'] = $element;
    // Remove properties that should only be applied to the parent element.
    // NOTE: #multiple is not removed because it is used by during validation.
    // @see \Drupal\webform\Plugin\WebformElement\DateBase::preValidateDate
    $element['#element'] = array_diff_key($element['#element'], array_flip(['#default_value', '#description', '#description_display', '#required', '#required_error', '#states', '#wrapper_attributes', '#prefix', '#suffix', '#element', '#tags']));
    // Always make the title invisible.
    $element['#element']['#title_display'] = 'invisible';

    // Change the element to a multiple element.
    $element['#type'] = 'webform_multiple';
    $element['#webform_multiple'] = TRUE;
    if ($element['#multiple'] > 1) {
      $element['#cardinality'] = $element['#multiple'];
    }
    $element['#empty_items'] = 0;
    if (!empty($element['#multiple__header_label'])) {
      $element['#header'] = $element['#multiple__header_label'];
    }

    // Remove properties that should only be applied to the child element.
    $element = array_diff_key($element, array_flip(['#attributes', '#field_prefix', '#field_suffix', '#pattern', '#placeholder', '#maxlength', '#element_validate']));
  }

  /**
   * {@inheritdoc}
   */
  public function displayDisabledWarning(array $element) {
    $t_args = [
      '%title' => $this->getLabel($element),
      '%type' => $this->getPluginLabel(),
      ':href' => Url::fromRoute('webform.settings')->setOption('fragment', 'edit-elements')->toString(),
    ];
    if ($this->currentUser->hasPermission('administer webform')) {
      $message = $this->t('%title is a %type element, which has been disabled and will not be rendered. Go to the <a href=":href">admin settings</a> page to enable this element.', $t_args);
    }
    else {
      $message = $this->t('%title is a %type element, which has been disabled and will not be rendered. Please contact a site administrator.', $t_args);
    }
    drupal_set_message($message, 'warning');

    $context = [
      '@title' => $this->getLabel($element),
      '@type' => $this->getPluginLabel(),
      'link' => Link::fromTextAndUrl($this->t('Edit'), Url::fromRoute('<current>'))->toString(),
    ];
    $this->logger->notice("'@title' is a '@type' element, which has been disabled and will not be rendered.", $context);
  }

  /**
   * {@inheritdoc}
   */
  public function setDefaultValue(array &$element) {}

  /**
   * {@inheritdoc}
   */
  public function getLabel(array $element) {
    return $element['#title'] ?: $element['#webform_key'];
  }

  /**
   * {@inheritdoc}
   */
  public function getAdminLabel(array $element) {
    return $element['#admin_title'] ?: $element['#title'] ?: $element['#webform_key'];
  }

  /**
   * {@inheritdoc}
   */
  public function getKey(array $element) {
    return $element['#webform_key'];
  }

  /****************************************************************************/
  // Display submission value methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function buildHtml(array $element, $value, array $options = []) {
    return $this->build('html', $element, $value, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function buildText(array $element, $value, array $options = []) {
    return $this->build('text', $element, $value, $options);
  }

  /**
   * Build an element as text or HTML.
   *
   * @param string $format
   *   Format of the element, text or html.
   * @param array $element
   *   An element.
   * @param array|mixed $value
   *   A value.
   * @param array $options
   *   An array of options.
   *
   * @return array
   *   A render array representing an element as text or HTML.
   */
  protected function build($format, array &$element, $value, array $options = []) {
    $options['multiline'] = $this->isMultiline($element);
    $format_function = 'format' . ucfirst($format);
    $formatted_value = $this->$format_function($element, $value, $options);

    // Return NULL for empty formatted value.
    if ($formatted_value === '') {
      return NULL;
    }

    // Convert string to renderable #markup.
    if (is_string($formatted_value)) {
      $formatted_value = ['#markup' => $formatted_value];
    }

    return [
      '#theme' => 'webform_element_base_' . $format,
      '#element' => $element,
      '#value' => $formatted_value,
      '#options' => $options,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function formatHtml(array $element, $value, array $options = []) {
    return $this->format('Html', $element, $value, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function formatText(array $element, $value, array $options = []) {
    return $this->format('Text', $element, $value, $options);
  }

  /**
   * Format an element's value as HTML or plain text.
   *
   * @param string $type
   *   The format type, HTML or Text.
   * @param array $element
   *   An element.
   * @param array|mixed $value
   *   A value.
   * @param array $options
   *   An array of options.
   *
   * @return array|string
   *   The element's value formatted as plain text or a render array.
   */
  protected function format($type, array &$element, $value, array $options = []) {
    // Return empty value.
    if ($value === '' || $value === NULL || (is_array($value) && empty($value))) {
      return '';
    }

    $item_function = 'format' . $type . 'Item';
    $items_function = 'format' . $type . 'Items';
    if ($this->hasMultipleValues($element)) {
      $items = [];
      foreach ($value as $item) {
        $items[] = $this->$item_function($element, $item, $options);
      }
      return $this->$items_function($element, $items, $options);
    }
    else {
      return $this->$item_function($element, $value, $options);
    }
  }

  /**
   * Format an element's items as HTML.
   *
   * @param array $element
   *   An element.
   * @param array $items
   *   An array of items to be displayed as HTML.
   * @param array $options
   *   An array of options.
   *
   * @return string|array
   *   The element's items as HTML.
   */
  protected function formatHtmlItems(array &$element, array $items, array $options = []) {
    $format = $this->getItemsFormat($element);
    switch ($format) {
      case 'ol':
      case 'ul':
        return [
          '#theme' => 'item_list',
          '#items' => $items,
          '#list_type' => $format,
        ];

      case 'and':
        $total = count($items);
        if ($total === 1) {
          $item = current($items);
          return is_array($item) ? $item : ['#markup' => $item];
        }

        $build = [];
        foreach ($items as $index => &$item) {
          $build[] = (is_array($item)) ? $item : ['#markup' => $item];
          if ($total === 2 && $index === 0) {
            $build[] = ['#markup' => t(' and ')];
          }
          elseif ($index !== ($total - 1)) {
            if ($index === ($total - 2)) {
              $build[] = ['#markup' => t(', and ')];
            }
            else {
              $build[] = ['#markup' => t(', ')];
            }
          }
        }
        return $build;

      default:
      case 'br':
      case 'semicolon':
      case 'comma':
      case 'space':
      case 'hr':
        $delimiters = [
          'hr' => '<hr class="webform-horizontal-rule" />',
          'br' => '<br />',
          'semicolon' => '; ',
          'comma' => ', ',
          'space' => ' ',
        ];
        $delimiter = (isset($delimiters[$format])) ? $delimiters[$format] : $format;

        $total = count($items);

        $build = [];
        foreach ($items as $index => &$item) {
          $build[] = (is_array($item)) ? $item : ['#markup' => $item];
          if ($index !== ($total - 1)) {
            $build[] = ['#markup' => $delimiter];
          }
        }
        return $build;
    }
  }

  /**
   * Format an element's items as text.
   *
   * @param array $element
   *   An element.
   * @param array $items
   *   An array of items to be displayed as text.
   * @param array $options
   *   An array of options.
   *
   * @return string
   *   The element's items as text.
   */
  protected function formatTextItems(array &$element, array $items, array $options = []) {
    $format = $this->getItemsFormat($element);
    switch ($format) {
      case 'ol':
        $list = [];
        $index = 1;
        foreach ($items as $item) {
          $prefix = ($index++) . '. ';
          $list[] = $prefix . str_replace(PHP_EOL, PHP_EOL . str_repeat(' ', strlen($prefix)), $item);
        }
        return implode(PHP_EOL, $list);

      case 'ul':
        $list = [];
        foreach ($items as $index => $item) {
          $list[] = '- ' . str_replace(PHP_EOL, PHP_EOL . '  ', $item);
        }
        return implode(PHP_EOL, $list);

      case 'and':
        return WebformArrayHelper::toString($items);

      default:
      case 'br':
      case 'semicolon':
      case 'comma':
      case 'space':
      case 'hr':
        $delimiters = [
          'hr' => PHP_EOL . '---' . PHP_EOL,
          'br' => PHP_EOL,
          'semicolon' => '; ',
          'comma' => ', ',
          'space' => ' ',
        ];
        $delimiter = (isset($delimiters[$format])) ? $delimiters[$format] : $format;
        return implode($delimiter, $items);
    }
  }

  /**
   * Format an element's value as HTML.
   *
   * @param array $element
   *   An element.
   * @param array|mixed $value
   *   A value.
   * @param array $options
   *   An array of options.
   *
   * @return array|string
   *   The element's value formatted as HTML or a render array.
   */
  protected function formatHtmlItem(array $element, $value, array $options = []) {
    return $this->formatTextItem($element, $value, $options);
  }

  /**
   * Format an element's value as text.
   *
   * @param array $element
   *   An element.
   * @param array|mixed $value
   *   A value.
   * @param array $options
   *   An array of options.
   *
   * @return string
   *   The element's value formatted as text.
   */
  protected function formatTextItem(array $element, $value, array $options = []) {
    // Apply XSS filter to value that contains HTML tags and is not formatted as
    // raw.
    $format = $this->getItemFormat($element);
    if ($format != 'raw' && is_string($value) && strpos($value, '<') !== FALSE) {
      $value = Xss::filter($value);
    }

    // Apply #field prefix and #field_suffix to value.
    if (isset($element['#type'])) {
      if (isset($element['#field_prefix'])) {
        $value = $element['#field_prefix'] . $value;
      }
      if (isset($element['#field_suffix'])) {
        $value .= $element['#field_suffix'];
      }
    }

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function getItemFormats() {
    return [
      'value' => $this->t('Value'),
      'raw' => $this->t('Raw value'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getItemDefaultFormat() {
    return 'value';
  }

  /**
   * {@inheritdoc}
   */
  public function getItemFormat(array $element) {
    if (isset($element['#format'])) {
      return $element['#format'];
    }
    elseif ($default_format = $this->configFactory->get('webform.settings')->get('format.' . $this->getPluginId() . '.item')) {
      return $default_format;
    }
    else {
      return $this->getItemDefaultFormat();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getItemsDefaultFormat() {
    return 'ul';
  }

  /**
   * {@inheritdoc}
   */
  public function getItemsFormats() {
    return [
      'comma' => $this->t('Comma'),
      'semicolon' => $this->t('Semicolon'),
      'and' => $this->t('And'),
      'ol' => $this->t('Ordered list'),
      'ul' => $this->t('Unordered list'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getItemsFormat(array $element) {
    if (isset($element['#format_items'])) {
      return $element['#format_items'];
    }
    elseif ($default_format = $this->configFactory->get('webform.settings')->get('format.' . $this->getPluginId() . '.items')) {
      return $default_format;
    }
    else {
      return $this->getItemsDefaultFormat();
    }
  }

  /****************************************************************************/
  // Test methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function getTestValues(array $element, WebformInterface $webform, array $options = []) {
    return FALSE;
  }

  /****************************************************************************/
  // Table methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function getTableColumn(array $element) {
    $key = $element['#webform_key'];
    return [
      'element__' . $key => [
        'title' => $this->getAdminLabel($element),
        'sort' => TRUE,
        'key' => $key,
        'property_name' => NULL,
        'element' => $element,
        'plugin' => $this,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function formatTableColumn(array $element, $value, array $options = []) {
    return $this->formatHtml($element, $value);
  }

  /****************************************************************************/
  // Export methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function getExportDefaultOptions() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildExportOptionsForm(array &$form, FormStateInterface $form_state, array $export_options) {}

  /**
   * {@inheritdoc}
   */
  public function buildExportHeader(array $element, array $export_options) {
    if ($export_options['header_format'] == 'label') {
      return [$this->getAdminLabel($element)];
    }
    else {
      return [$element['#webform_key']];
    }
  }

  /**
   * Prefix an element's export header.
   *
   * @param array $header
   *   An element's export header.
   * @param array $element
   *   An element.
   * @param array $export_options
   *   An associative array of export options.
   *
   * @return array
   *   An element's export header with prefix.
   */
  protected function prefixExportHeader(array $header, array $element, array $export_options) {
    if (empty($export_options['header_prefix'])) {
      return $header;
    }

    if ($export_options['header_format'] == 'label') {
      $prefix = $this->getAdminLabel($element) . $export_options['header_prefix_label_delimiter'];
    }
    else {
      $prefix = $this->getKey($element) . $export_options['header_prefix_key_delimiter'];;
    }

    foreach ($header as $index => $column) {
      $header[$index] = $prefix . $column;
    }

    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildExportRecord(array $element, $value, array $export_options) {
    $element['#format_items'] = $export_options['multiple_delimiter'];
    return [$this->formatText($element, $value, $export_options)];
  }

  /****************************************************************************/
  // Validation methods.
  /****************************************************************************/

  /**
   * Form API callback. Validate element #minlength value.
   */
  public static function validateMinlength(&$element, FormStateInterface &$form_state) {
    if (!isset($element['#minlength'])) {
      return;
    }

    if (Unicode::strlen($element['#value']) < $element['#minlength']) {
      $t_args = [
        '%name' => empty($element['#title']) ? $element['#parents'][0] : $element['#title'],
        '%min' => $element['#minlength'],
        '%length' => Unicode::strlen($element['#value']),
      ];
      $form_state->setError($element, t('%name cannot be less than %min characters but is currently %length characters long.', $t_args));
    }
  }

  /**
   * Form API callback. Validate element #unique value.
   */
  public static function validateUnique(array &$element, FormStateInterface $form_state) {
    if (!isset($element['#unique'])) {
      return;
    }

    $webform_id = $element['#webform'];
    $sid = $element['#webform_submission'];
    $name = $element['#name'];
    $value = NestedArray::getValue($form_state->getValues(), $element['#parents']);

    // Skip empty unique fields or arrays (aka #multiple).
    if ($value === '' || is_array($value)) {
      return;
    }

    // Using range() is more efficient than using countQuery() for data checks.
    $query = Database::getConnection()->select('webform_submission_data')
      ->fields('webform_submission_data', ['sid'])
      ->condition('webform_id', $webform_id)
      ->condition('name', $name)
      ->condition('value', $value)
      ->range(0, 1);
    if ($sid) {
      $query->condition('sid', $sid, '<>');
    }
    $count = $query->execute()->fetchField();
    if ($count) {
      if (isset($element['#unique_error'])) {
        $form_state->setError($element, $element['#unique_error']);
      }
      elseif (isset($element['#title'])) {
        $t_args = [
          '%name' => empty($element['#title']) ? $element['#parents'][0] : $element['#title'],
          '%value' => $value,
        ];
        $form_state->setError($element, t('The value %value has already been submitted once for the %name element. You may have already submitted this webform, or you need to use a different value.', $t_args));
      }
      else {
        $form_state->setError($element);
      }
    }
  }

  /**
   * Form API callback. Validate element #multiple > 1 value.
   */
  public static function validateMultiple(array &$element, FormStateInterface $form_state) {
    if (!isset($element['#multiple'])) {
      return;
    }

    // IMPORTANT: Must get values from the $form_states since sub-elements
    // may call $form_state->setValueForElement() via their validation hook.
    // @see \Drupal\webform\Element\WebformEmailConfirm::validateWebformEmailConfirm
    // @see \Drupal\webform\Element\WebformOtherBase::validateWebformOther
    $values = NestedArray::getValue($form_state->getValues(), $element['#parents']);

    // Skip empty values or values that are not an array.
    if (empty($values) || !is_array($values)) {
      return;
    }

    if (count($values) > $element['#multiple']) {
      if (isset($element['#multiple_error'])) {
        $form_state->setError($element, $element['#multiple_error']);
      }
      elseif (isset($element['#title'])) {
        $t_args = [
          '%name' => empty($element['#title']) ? $element['#parents'][0] : $element['#title'],
          '@count' => $element['#multiple'],
        ];
        $form_state->setError($element, t('%name: this element cannot hold more than @count values.', $t_args));
      }
      else {
        $form_state->setError($element);
      }
    }
  }

  /****************************************************************************/
  // #states API methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function getElementStateOptions() {
    $states = [];

    // Set default states that apply to the element/container and sub elements.
    $states += [
      'visible' => $this->t('Visible'),
      'invisible' => $this->t('Invisible'),
      'enabled' => $this->t('Enabled'),
      'disabled' => $this->t('Disabled'),
      'required' => $this->t('Required'),
      'optional' => $this->t('Optional'),
    ];

    // Set element type specific states.
    switch ($this->getPluginId()) {
      case 'checkbox':
        $states += [
          'checked' => $this->t('Checked'),
          'unchecked' => $this->t('Unchecked'),
        ];
        break;

      case 'details':
        $states += [
          'expanded' => $this->t('Expanded'),
          'collapsed' => $this->t('Collapsed'),
        ];
        break;
    }

    return $states;
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

    $title = $this->getAdminLabel($element) . ' [' . $this->getPluginLabel() . ']';
    $name = $element['#webform_key'];

    if ($inputs = $this->getElementSelectorInputsOptions($element)) {
      $selectors = [];
      foreach ($inputs as $input_name => $input_title) {
        $selectors[":input[name=\"{$name}[{$input_name}]\"]"] = $input_title;
      }
      return [$title => $selectors];
    }
    else {
      return [":input[name=\"$name\"]" => $title];
    }
  }

  /**
   * Get an element's (sub)inputs selectors as options.
   *
   * @param array $element
   *   An element.
   *
   * @return array
   *   An array of element (sub)input selectors.
   */
  protected function getElementSelectorInputsOptions(array $element) {
    return [];
  }

  /****************************************************************************/
  // Operation methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function preCreate(array &$element, array $values) {}

  /**
   * {@inheritdoc}
   */
  public function postCreate(array &$element, WebformSubmissionInterface $webform_submission) {}

  /**
   * {@inheritdoc}
   */
  public function postLoad(array &$element, WebformSubmissionInterface $webform_submission) {}

  /**
   * {@inheritdoc}
   */
  public function preDelete(array &$element, WebformSubmissionInterface $webform_submission) {}

  /**
   * {@inheritdoc}
   */
  public function postDelete(array &$element, WebformSubmissionInterface $webform_submission) {}

  /**
   * {@inheritdoc}
   */
  public function preSave(array &$element, WebformSubmissionInterface $webform_submission) {}

  /**
   * {@inheritdoc}
   */
  public function postSave(array &$element, WebformSubmissionInterface $webform_submission, $update = TRUE) {}

  /****************************************************************************/
  // Element configuration methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webform_ui\Form\WebformUiElementFormInterface $form_object */
    $form_object = $form_state->getFormObject();
    $webform = $form_object->getWebform();

    /* Element settings */

    $form['element'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Element settings'),
      '#access' => TRUE,
      '#weight' => -50,
    ];
    $form['element']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#description' => $this->t('This is used as a descriptive label when displaying this webform element.'),
      '#required' => TRUE,
      '#attributes' => ['autofocus' => 'autofocus'],
    ];
    $form['element']['description'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Description'),
      '#description' => $this->t('A short description of the element used as help for the user when he/she uses the webform.'),
    ];
    if ($this->isComposite()) {
      $form['element']['default_value'] = [
        '#type' => 'webform_codemirror',
        '#mode' => 'yaml',
        '#title' => $this->t('Default value'),
        '#description' => $this->t('The default value of the webform element.'),
      ];
    }
    else {
      $form['element']['default_value'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Default value'),
        '#description' => $this->t('The default value of the webform element.'),
      ];
    }
    $form['element']['value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Value'),
      '#description' => $this->t('The value of the webform element.'),
    ];
    $form['element']['multiple'] = [
      '#title' => $this->t('Allowed number of values'),
      '#type' => 'webform_element_multiple',
    ];
    $form['element']['multiple_error'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom allowed number of values error message'),
      '#description' => $this->t('If set, this message will be used when an element\'s allowed number of values is exceeded, instead of the default "@message" message.', ['@message' => $this->t('%name: this element cannot hold more than @count values.')]),
      '#states' => [
        'visible' => [
          ':input[name="properties[multiple][container][cardinality]"]' => ['value' => 'number'],
          ':input[name="properties[multiple][container][cardinality_number]"]' => ['!value' => 1],
        ],
      ],
    ];
    $form['element']['multiple__header'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display elements in table columns'),
      '#description' => $this->t("If checked composite elements titles will be displayed in table column headers."),
      '#return_value' => TRUE,
      '#states' => [
        'invisible' => [
          ':input[name="properties[multiple][container][cardinality]"]' => ['!value' => -1],
          ':input[name="properties[multiple][container][cardinality_number]"]' => ['value' => 1],
        ],
      ],
    ];
    $form['element']['multiple__header_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Table header label'),
      '#description' => $this->t('This is used as the table header for this webform element when display multiple values.'),
      '#states' => [
        'invisible' => [
          ':input[name="properties[multiple][container][cardinality]"]' => ['!value' => -1],
          ':input[name="properties[multiple][container][cardinality_number]"]' => ['value' => 1],
        ],
      ],
    ];
    if ($this->hasProperty('multiple')) {
      $form['element']['default_value']['#description'] .= ' ' . $this->t('For multiple options, use commas to separate multiple defaults.');
    }

    /* Form display */

    $form['form'] = [
      '#type' => 'details',
      '#title' => $this->t('Form display'),
    ];
    $form['form']['title_display'] = [
      '#type' => 'select',
      '#title' => $this->t('Title display'),
      '#options' => [
        '' => '',
        'before' => $this->t('Before'),
        'after' => $this->t('After'),
        'inline' => $this->t('Inline'),
        'invisible' => $this->t('Invisible'),
        'attribute' => $this->t('Attribute'),
      ],
      '#description' => $this->t('Determines the placement of the title.'),
    ];
    $form['form']['description_display'] = [
      '#type' => 'select',
      '#title' => $this->t('Description display'),
      '#options' => [
        '' => '',
        'before' => $this->t('Before'),
        'after' => $this->t('After'),
        'invisible' => $this->t('Invisible'),
        'tooltip' => $this->t('Tooltip'),
      ],
      '#description' => $this->t('Determines the placement of the description.'),
    ];
    $form['form']['field_prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Field prefix'),
      '#description' => $this->t('Text or code that is placed directly in front of the input. This can be used to prefix an input with a constant string. Examples: $, #, -.'),
      '#size' => 10,
    ];
    $form['form']['field_suffix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Field suffix'),
      '#description' => $this->t('Text or code that is placed directly after the input. This can be used to add a unit to an input. Examples: lb, kg, %.'),
      '#size' => 10,
    ];
    $form['form']['size'] = [
      '#type' => 'number',
      '#title' => $this->t('Size'),
      '#description' => $this->t('Leaving blank will use the default size.'),
      '#min' => 1,
      '#size' => 4,
    ];
    $form['form']['maxlength'] = [
      '#type' => 'number',
      '#title' => $this->t('Maxlength'),
      '#description' => $this->t('Leaving blank will use the default maxlength.'),
      '#min' => 1,
      '#size' => 4,
    ];
    $form['form']['minlength'] = [
      '#type' => 'number',
      '#title' => $this->t('Minlength'),
      '#description' => $this->t('The element may still be empty unless it is required.'),
      '#min' => 1,
      '#size' => 4,
    ];
    $form['form']['rows'] = [
      '#type' => 'number',
      '#title' => $this->t('Rows'),
      '#description' => $this->t('Leaving blank will use the default rows.'),
      '#min' => 1,
      '#size' => 4,
    ];
    $form['form']['placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder'),
      '#description' => $this->t('The placeholder will be shown in the element until the user starts entering a value.'),
    ];
    $form['form']['autocomplete'] = [
      '#type' => 'select',
      '#title' => $this->t('Autocomplete'),
      '#options' => [
        'on' => $this->t('On'),
        'off' => $this->t('Off'),
      ],
      '#description' => $this->t('Setting autocomplete to off will disable autocompletion for this element.'),
    ];
    $form['form']['open'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Open'),
      '#description' => $this->t('Contents should be visible (open) to the user.'),
      '#return_value' => TRUE,
    ];
    $default_icheck = $this->configFactory->get('webform.settings')->get('elements.default_icheck');
    $form['form']['icheck'] = [
      '#type' => 'select',
      '#title' => 'Enhance using iCheck',
      '#description' => $this->t('Replaces @type element with jQuery <a href=":href">iCheck</a> boxes.', ['@type' => Unicode::strtolower($this->getPluginLabel()), ':href' => 'http://icheck.fronteed.com/']),
      '#options' => [
        '' => '',
        (string) $this->t('Minimal') => [
          'minimal' => $this->t('Minimal: Black'),
          'minimal-grey' => $this->t('Minimal: Grey'),
          'minimal-yellow' => $this->t('Minimal: Yellow'),
          'minimal-orange' => $this->t('Minimal: Orange'),
          'minimal-red' => $this->t('Minimal: Red'),
          'minimal-pink' => $this->t('Minimal: Pink'),
          'minimal-purple' => $this->t('Minimal: Purple'),
          'minimal-blue' => $this->t('Minimal: Blue'),
          'minimal-green' => $this->t('Minimal: Green'),
          'minimal-aero' => $this->t('Minimal: Aero'),
        ],
        (string) $this->t('Square') => [
          'square' => $this->t('Square: Black'),
          'square-grey' => $this->t('Square: Grey'),
          'square-yellow' => $this->t('Square: Yellow'),
          'square-orange' => $this->t('Square: Orange'),
          'square-red' => $this->t('Square: Red'),
          'square-pink' => $this->t('Square: Pink'),
          'square-purple' => $this->t('Square: Purple'),
          'square-blue' => $this->t('Square: Blue'),
          'square-green' => $this->t('Square: Green'),
          'square-aero' => $this->t('Square: Aero'),
        ],
        (string) $this->t('Flat') => [
          'flat' => $this->t('Flat: Black'),
          'flat-grey' => $this->t('Flat: Grey'),
          'flat-yellow' => $this->t('Flat: Yellow'),
          'flat-orange' => $this->t('Flat: Orange'),
          'flat-red' => $this->t('Flat: Red'),
          'flat-pink' => $this->t('Flat: Pink'),
          'flat-purple' => $this->t('Flat: Purple'),
          'flat-blue' => $this->t('Flat: Blue'),
          'flat-green' => $this->t('Flat: Green'),
          'flat-aero' => $this->t('Flat: Aero'),
        ],
      ],
      '#access' => $this->librariesManager->isIncluded('jquery.icheck'),
    ];
    if ($default_icheck) {
      $icheck_options = OptGroup::flattenOptions($form['form']['icheck']['#options']);
      $form['form']['icheck']['#description'] .= '<br/>' . $this->t("Leave blank to use the default iCheck style. Select 'None' to display the default HTML element.");
      $form['form']['icheck']['#description'] .= '<br/>' . $this->t('Defaults to: %value', ['%value' => $icheck_options[$default_icheck]]);
      $form['form']['icheck']['#options']['none'] = $this->t('None');
    }

    /* Flexbox item */

    $form['flex'] = [
      '#type' => 'details',
      '#title' => $this->t('Flexbox item'),
      '#description' => $this->t('Learn more about using <a href=":href">flexbox layouts</a>.', [':href' => 'http://www.w3schools.com/css/css3_flexbox.asp']),
    ];
    $flex_range = range(0, 12);
    $form['flex']['flex'] = [
      '#type' => 'select',
      '#title' => $this->t('Flex'),
      '#description' => $this->t('The flex property specifies the length of the item, relative to the rest of the flexible items inside the same container.') . '<br/>' .
      $this->t('Defaults to: %value', ['%value' => 1]),
      '#options' => [0 => $this->t('0 (none)')] + array_combine($flex_range, $flex_range),
    ];

    /* Wrapper and element attributes */

    $form['wrapper_attributes'] = [
      '#type' => 'details',
      '#title' => $this->t('Wrapper attributes'),
    ];
    $form['wrapper_attributes']['wrapper_attributes'] = [
      '#type' => 'webform_element_attributes',
      '#title' => $this->t('Wrapper'),
      '#class__description' => $this->t("Apply classes to the element's wrapper around both the field and its label. Select 'custom...' to enter custom classes."),
      '#style__description' => $this->t("Apply custom styles to the element's wrapper around both the field and its label."),
      '#attributes__description' => $this->t("Enter additional attributes to be added the element's wrapper."),
      '#classes' => $this->configFactory->get('webform.settings')->get('elements.wrapper_classes'),
    ];
    $form['element_attributes'] = [
      '#type' => 'details',
      '#title' => $this->t('Element attributes'),
    ];
    $form['element_attributes']['attributes'] = [
      '#type' => 'webform_element_attributes',
      '#title' => $this->t('Element'),
      '#classes' => $this->configFactory->get('webform.settings')->get('elements.classes'),
    ];

    /* Validation */

    // Placeholder webform elements with #options.
    // @see \Drupal\webform\Plugin\WebformElement\OptionsBase::form
    $form['options'] = [];
    $form['options_other'] = [];

    $form['validation'] = [
      '#type' => 'details',
      '#title' => $this->t('Form validation'),
    ];
    $form['validation']['required'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Required'),
      '#description' => $this->t('Check this option if the user must enter a value.'),
      '#return_value' => TRUE,
    ];
    $form['validation']['required_error'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom required error message'),
      '#description' => $this->t('If set, this message will be used when a required webform element is empty, instead of the default "Field x is required." message.'),
      '#states' => [
        'visible' => [
          ':input[name="properties[required]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['validation']['unique'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Unique'),
      '#description' => $this->t('Check that all entered values for this element are unique. The same value is not allowed to be used twice.'),
      '#return_value' => TRUE,
    ];
    $form['validation']['unique_error'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom unique error message'),
      '#description' => $this->t('If set, this message will be used when an element\'s value is not unique, instead of the default "@message" message.', ['@message' => $this->t('The value %value has already been submitted once for the %name element. You may have already submitted this webform, or you need to use a different value.')]),
      '#states' => [
        'visible' => [
          ':input[name="properties[unique]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    /* Conditional logic */

    $form['conditional'] = [
      '#type' => 'details',
      '#title' => $this->t('Conditional logic'),
    ];
    $form['conditional']['states'] = [
      '#type' => 'webform_element_states',
      '#state_options' => $this->getElementStateOptions(),
      '#selector_options' => $webform->getElementsSelectorOptions(),
    ];

    /* Submission display */

    $form['display'] = [
      '#type' => 'details',
      '#title' => $this->t('Submission display'),
    ];
    $form['display']['format'] = [
      '#type' => 'select',
      '#title' => $this->t('Item format'),
      '#description' => $this->t('Select how a single value is displayed.'),
      '#options' => $this->getItemFormats(),
    ];
    $form['display']['format_items'] = [
      '#type' => 'select',
      '#title' => $this->t('Items format'),
      '#description' => $this->t('Select how multiple values are displayed.'),
      '#options' => $this->getItemsFormats(),
      '#states' => [
        'visible' => [
          [':input[name="properties[multiple][container][cardinality]"]' => ['value' => '-1']],
          'or',
          [':input[name="properties[multiple][container][cardinality_number]"]' => ['!value' => 1]],
        ],
      ],
    ];

    /* Element access */

    $operations = [
      'create' => [
        '#title' => $this->t('Create webform submission'),
        '#description' => $this->t('Select roles and users that should be able to populate this element when creating a new submission.'),
      ],
      'update' => [
        '#title' => $this->t('Update webform submission'),
        '#description' => $this->t('Select roles and users that should be able to update this element when updating an existing submission.'),
      ],
      'view' => [
        '#title' => $this->t('View webform submission'),
        '#description' => $this->t('Select roles and users that should be able to view this element when viewing a submission.'),
      ],
    ];

    $form['access'] = [
      '#type' => 'details',
      '#title' => $this->t('Element access'),
    ];
    if (!$this->currentUser->hasPermission('administer webform') && !$this->currentUser->hasPermission('administer webform element access')) {
      $form['access'] = FALSE;
    }
    foreach ($operations as $operation => $operation_element) {
      $form['access']['access_' . $operation] = $operation_element + [
        '#type' => 'details',
      ];
      $form['access']['access_' . $operation]['access_' . $operation . '_roles'] = [
        '#type' => 'webform_roles',
        '#title' => $this->t('Roles'),
      ];
      $form['access']['access_' . $operation]['access_' . $operation . '_users'] = [
        '#type' => 'webform_users',
        '#title' => $this->t('Users'),
      ];
    }

    /* Administration */

    $form['admin'] = [
      '#type' => 'details',
      '#title' => $this->t('Administration'),
    ];
    $form['admin']['private'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Private'),
      '#description' => $this->t('Private elements are shown only to users with results access.'),
      '#weight' => 50,
      '#return_value' => TRUE,
    ];
    $form['admin']['admin_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Admin title'),
      '#description' => $this->t('The admin title will be displayed when managing elements and viewing & downloading submissions.'),
    ];

    // Disable #multiple if the element has submission data.
    if (!$form_object->isNew() && $this->hasProperty('multiple')) {
      $element_key = $form_object->getKey();
      if ($this->submissionStorage->hasSubmissionValue($webform, $element_key)) {
        $form['element']['multiple']['#disabled'] = TRUE;
        $form['element']['multiple']['#description'] = '<em>' . $this->t('There is data for this element in the database. This setting can no longer be changed.') . '</em>';
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $default_properties = $this->getDefaultProperties();

    // Unset 'format_items' if the element does not support multiple values.
    if (!$this->supportsMultipleValues()) {
      unset($default_properties['format_items']);
    }

    $element_properties = WebformArrayHelper::removePrefix($this->configuration) + $default_properties;

    // Set default and element properties.
    // Note: Storing this information in the webform's state allows modules to view
    // and alter this information using webform alteration hooks.
    $form_state->set('default_properties', $default_properties);
    $form_state->set('element_properties', $element_properties);

    $form = $this->form($form, $form_state);

    // Get element properties which can be altered by WebformElementHandlers.
    // @see \Drupal\webform\Plugin\WebformElement\WebformEntityReferenceTrait::form
    $element_properties = $form_state->get('element_properties');

    // Copy element properties to custom properties which will be determined
    // as the default values are set.
    $custom_properties = $element_properties;

    // Populate the webform.
    $this->setConfigurationFormDefaultValueRecursive($form, $custom_properties);

    // Set fieldset weights so that they appear first.
    foreach ($form as &$element) {
      if (is_array($element) && !isset($element['#weight']) && isset($element['#type']) && $element['#type'] == 'fieldset') {
        $element['#weight'] = -20;
      }
    }

    // Store 'type' as a hardcoded value and make sure it is always first.
    // Also always remove the 'webform_*' prefix from the type name.
    if (isset($custom_properties['type'])) {
      $form['type'] = [
        '#type' => 'value',
        '#value' => $custom_properties['type'],
        '#parents' => ['properties', 'type'],
      ];
      unset($custom_properties['type']);
    }

    // Allow custom properties (ie #attributes) to be added to the element.
    $form['custom'] = [
      '#type' => 'details',
      '#title' => $this->t('Custom settings'),
      '#open' => $custom_properties ? TRUE : FALSE,
      '#access' => $this->currentUser->hasPermission('edit webform source'),
    ];
    if ($api_url = $this->getPluginApiUrl()) {
      $t_args = [
        ':href' => $api_url->toString(),
        '%label' => $this->getPluginLabel(),
      ];
      $form['custom']['#description'] = $this->t('Read the %label element\'s <a href=":href">API documentation</a>.', $t_args);
    }

    $form['custom']['properties'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'yaml',
      '#title' => $this->t('Custom properties'),
      '#description' => $this->t('Properties do not have to be prepended with a hash (#) character, the hash character will be automatically added upon submission.') .
      '<br/>' .
      $this->t('These properties and callbacks are not allowed: @properties', ['@properties' => WebformArrayHelper::toString(WebformArrayHelper::addPrefix(WebformElementHelper::$ignoredProperties))]),
      '#default_value' => $custom_properties ,
      '#parents' => ['properties', 'custom'],
    ];

    $form['token_tree_link'] = $this->tokenManager->buildTreeLink();

    // Set custom properties.
    // Note: Storing this information in the webform's state allows modules to view
    // and alter this information using webform alteration hooks.
    $form_state->set('custom_properties', $custom_properties);

    return $form;
  }

  /**
   * Set configuration webform default values recursively.
   *
   * @param array $form
   *   A webform render array.
   * @param array $element_properties
   *   The element's properties without hash prefix. Any property that is found
   *   in the webform will be populated and unset from $element_properties array.
   *
   * @return bool
   *   TRUE is the webform has any inputs.
   */
  protected function setConfigurationFormDefaultValueRecursive(array &$form, array &$element_properties) {
    $has_input = FALSE;

    foreach ($form as $property_name => &$property_element) {
      // Skip all properties.
      if (Element::property($property_name)) {
        continue;
      }

      // Skip Entity reference element 'selection_settings'.
      // @see \Drupal\webform\Plugin\WebformElement\WebformEntityReferenceTrait::form
      // @todo Fix entity reference AJAX and move code WebformEntityReferenceTrait.
      if (!empty($property_element['#tree']) && $property_name == 'selection_settings') {
        unset($element_properties[$property_name]);
        $property_element['#parents'] = ['properties', $property_name];
        $has_input = TRUE;
        continue;
      }

      // Determine if the property element is an input using the webform element
      // manager.
      $is_input = $this->elementManager->getElementInstance($property_element)->isInput($property_element);
      if ($is_input) {
        if (isset($element_properties[$property_name])) {
          // If this property exists, then set its default value.
          $this->setConfigurationFormDefaultValue($form, $element_properties, $property_element, $property_name);
          $has_input = TRUE;
        }
        else {
          // Else completely remove the property element from the webform.
          unset($form[$property_name]);
        }
      }
      else {
        // Recurse down this container and see if it's children have inputs.
        // Note: #access is used to protect containers that should always
        // be visible.
        $container_has_input = $this->setConfigurationFormDefaultValueRecursive($property_element, $element_properties);
        if ($container_has_input) {
          $has_input = TRUE;
        }
        elseif (empty($form[$property_name]['#access'])) {
          unset($form[$property_name]);
        }
      }
    }

    return $has_input;
  }

  /**
   * Set an element's configuration webform element default value.
   *
   * @param array $form
   *   An element's configuration webform.
   * @param array $element_properties
   *   The element's properties without hash prefix.
   * @param array $property_element
   *   The webform input used to set an element's property.
   * @param string $property_name
   *   THe property's name.
   */
  protected function setConfigurationFormDefaultValue(array &$form, array &$element_properties, array &$property_element, $property_name) {
    $default_value = $element_properties[$property_name];
    $type = (isset($property_element['#type'])) ? $property_element['#type'] : NULL;

    switch ($type) {
      case 'entity_autocomplete':
        $target_type = $property_element['#target_type'];
        $target_storage = $this->entityTypeManager->getStorage($target_type);
        if (!empty($property_element['#tags'])) {
          $property_element['#default_value'] = ($default_value) ? $target_storage->loadMultiple($default_value) : [];
        }
        else {
          $property_element['#default_value'] = ($default_value) ? $target_storage->load($default_value) : NULL;
        }
        break;

      case 'radios':
      case 'select':
        // Handle invalid default_value throwing
        // "An illegal choice has been detected..." error.
        if (!is_array($default_value) && isset($property_element['#options'])) {
          $flattened_options = OptGroup::flattenOptions($property_element['#options']);
          if (!isset($flattened_options[$default_value])) {
            $default_value = NULL;
          }
        }
        $property_element['#default_value'] = $default_value;
        break;

      default:
        // Convert default_value array into a comma delimited list.
        // This is applicable to elements that support #multiple #options.
        if (is_array($default_value) && $property_name == 'default_value' && !$this->isComposite()) {
          $property_element['#default_value'] = implode(', ', $default_value);
        }
        else {
          $property_element['#default_value'] = $default_value;
        }
        break;

    }

    $property_element['#parents'] = ['properties', $property_name];
    unset($element_properties[$property_name]);
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $properties = $this->getConfigurationFormProperties($form, $form_state);
    if ($ignored_properties = WebformElementHelper::getIgnoredProperties($properties)) {
      $t_args = [
        '@properties' => WebformArrayHelper::toString($ignored_properties),
      ];
      $form_state->setErrorByName('custom', $this->t('Element contains ignored/unsupported properties: @properties.', $t_args));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Generally, elements will not be processing any submitted properties.
    // It is possible that a custom element might need to call a third-party API
    // to 'register' the element.
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigurationFormProperties(array &$form, FormStateInterface $form_state) {
    $element_properties = $form_state->getValues();

    // Get default properties so that they can be unset below.
    $default_properties = $form_state->get('default_properties');

    // Get custom properties.
    if (isset($element_properties['custom'])) {
      if (is_array($element_properties['custom'])) {
        $element_properties += $element_properties['custom'];
      }
      unset($element_properties['custom']);
    }

    // Remove all hash prefixes so that we can filter out any default
    // properties.
    WebformArrayHelper::removePrefix($element_properties);

    // Build a temp element used to see if multiple value and/or composite
    // elements need to be supported.
    $element = WebformArrayHelper::addPrefix($element_properties);
    foreach ($element_properties as $property_name => $property_value) {
      if (!isset($default_properties[$property_name])) {
        continue;
      }

      $this->getConfigurationFormProperty($element_properties, $property_name, $property_value, $element);

      // Unset element property that matched the default property.
      if ($default_properties[$property_name] == $element_properties[$property_name]) {
        unset($element_properties[$property_name]);
      }
    }

    // Make sure #type is always first.
    if (isset($element_properties['type'])) {
      $element_properties = ['type' => $element_properties['type']] + $element_properties;
    }

    return WebformArrayHelper::addPrefix($element_properties);
  }

  /**
   * Get configuration property value.
   *
   * @param array $properties
   *   An associative array of submitted properties.
   * @param string $property_name
   *   The property's name.
   * @param mixed $property_value
   *   The property's value.
   * @param array $element
   *   The element whose properties are being updated.
   */
  protected function getConfigurationFormProperty(array &$properties, $property_name, $property_value, array $element) {
    if ($property_name == 'default_value' && is_string($property_value) && $property_value && $this->hasMultipleValues($element)) {
      $properties[$property_name] = preg_split('/\s*,\s*/', $property_value);
    }
  }

}
