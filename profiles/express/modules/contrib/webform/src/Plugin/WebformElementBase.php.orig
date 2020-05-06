<?php

namespace Drupal\webform\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\OptGroup;
use Drupal\Core\Link;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\webform\Element\WebformCompositeFormElementTrait;
use Drupal\webform\Element\WebformHtmlEditor;
use Drupal\webform\Element\WebformMessage;
use Drupal\webform\Entity\WebformOptions;
use Drupal\webform\Plugin\WebformElement\Checkbox;
use Drupal\webform\Plugin\WebformElement\Checkboxes;
use Drupal\webform\Plugin\WebformElement\ContainerBase;
use Drupal\webform\Plugin\WebformElement\Details;
use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;
use Drupal\webform\Twig\WebformTwigExtension;
use Drupal\webform\Utility\WebformArrayHelper;
use Drupal\webform\Utility\WebformElementHelper;
use Drupal\webform\Utility\WebformFormHelper;
use Drupal\webform\Utility\WebformHtmlHelper;
use Drupal\webform\Utility\WebformOptionsHelper;
use Drupal\webform\Utility\WebformReflectionHelper;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformLibrariesManagerInterface;
use Drupal\webform\WebformSubmissionConditionsValidator;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\WebformTokenManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base class for a webform element.
 *
 * @see \Drupal\webform\Plugin\WebformElementInterface
 * @see \Drupal\webform\Plugin\WebformElementManager
 * @see \Drupal\webform\Plugin\WebformElementManagerInterface
 * @see plugin_api
 */
class WebformElementBase extends PluginBase implements WebformElementInterface {

  use StringTranslationTrait;
  use MessengerTrait;
  use WebformCompositeFormElementTrait;
  use WebformEntityInjectionTrait;

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
   * @var \Drupal\webform\Plugin\WebformElementManagerInterface
   */
  protected $elementManager;

  /**
   * The token manager.
   *
   * @var \Drupal\webform\WebformTokenManagerInterface
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
   * An associative array of an element's default properties names and values.
   *
   * @var array
   */
  protected $defaultProperties;

  /**
   * An indexed array of an element's translated properties.
   *
   * @var array
   */
  protected $translatableProperties;

  /**
   * Constructs a WebformElementBase object.
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
   * @param \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager
   *   The webform element manager.
   * @param \Drupal\webform\WebformTokenManagerInterface $token_manager
   *   The webform token manager.
   * @param \Drupal\webform\WebformLibrariesManagerInterface $libraries_manager
   *   The webform libraries manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerInterface $logger, ConfigFactoryInterface $config_factory, AccountInterface $current_user, EntityTypeManagerInterface $entity_type_manager, ElementInfoManagerInterface $element_info, WebformElementManagerInterface $element_manager, WebformTokenManagerInterface $token_manager, WebformLibrariesManagerInterface $libraries_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->logger = $logger;
    $this->configFactory = $config_factory;
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->elementInfo = $element_info;
    $this->elementManager = $element_manager;
    $this->tokenManager = $token_manager;
    $this->librariesManager = $libraries_manager;
    $this->submissionStorage = $entity_type_manager->getStorage('webform_submission');
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
      $container->get('webform.libraries_manager')
    );
  }

  /****************************************************************************/
  // Property definitions.
  /****************************************************************************/

  /**
   * Define an element's default properties.
   *
   * @return array
   *   An associative array contain an the element's default properties.
   */
  protected function defineDefaultProperties() {
    $properties = [
      // Element settings.
      'title' => '',
      'default_value' => '',
      // Description/Help.
      'help' => '',
      'help_title' => '',
      'description' => '',
      'more' => '',
      'more_title' => '',
      // Form display.
      'title_display' => '',
      'description_display' => '',
      'help_display' => '',
      'field_prefix' => '',
      'field_suffix' => '',
      'disabled' => FALSE,
      // Form validation.
      'required' => FALSE,
      'required_error' => '',
      // Attributes.
      'wrapper_attributes' => [],
      'label_attributes' => [],
      'attributes' => [],
      // Submission display.
      'format' => $this->getItemDefaultFormat(),
      'format_html' => '',
      'format_text' => '',
      'format_items' => $this->getItemsDefaultFormat(),
      'format_items_html' => '',
      'format_items_text' => '',
      'format_attributes' => [],
    ];

    // Unique validation.
    if (!$this->isComposite()) {
      $properties += [
        'unique' => FALSE,
        'unique_user' => FALSE,
        'unique_entity' => FALSE,
        'unique_error' => '',
      ];
    }

    $properties += $this->defineDefaultBaseProperties();

    return $properties;
  }

  /**
   * Define default multiple properties used by most elements.
   *
   * @return array
   *   An associative array containing default multiple properties.
   */
  protected function defineDefaultMultipleProperties() {
    return [
      'multiple' => FALSE,
      'multiple__header_label' => '',
      'multiple__min_items' => NULL,
      'multiple__empty_items' => 1,
      'multiple__add_more' => TRUE,
      'multiple__add_more_items' => 1,
      'multiple__add_more_button_label' => (string) $this->t('Add'),
      'multiple__add_more_input' => TRUE,
      'multiple__add_more_input_label' => (string) $this->t('more items'),
      'multiple__no_items_message' => (string) $this->t('No items entered. Please add items below.'),
      'multiple__sorting' => TRUE,
      'multiple__operations' => TRUE,
    ];
  }

  /**
   * Define default base properties used by all elements.
   *
   * @return array
   *   An associative array containing base properties used by all elements.
   */
  protected function defineDefaultBaseProperties() {
    return [
      // Administration.
      'admin_title' => '',
      'prepopulate' => FALSE,
      'private' => FALSE,
      // Flexbox.
      'flex' => 1,
      // Conditional logic.
      'states' => [],
      'states_clear' => TRUE,
      // Element access.
      'access_create_roles' => ['anonymous', 'authenticated'],
      'access_create_users' => [],
      'access_create_permissions' => [],
      'access_update_roles' => ['anonymous', 'authenticated'],
      'access_update_users' => [],
      'access_update_permissions' => [],
      'access_view_roles' => ['anonymous', 'authenticated'],
      'access_view_users' => [],
      'access_view_permissions' => [],
    ];
  }

  /**
   * Define an element's translatable properties.
   *
   * @return array
   *   An array containing an element's translatable properties.
   */
  protected function defineTranslatableProperties() {
    return [
      'title',
      'label',
      'help',
      'help_title',
      'more',
      'more_title',
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
      'header_label',
      'add_more_button_label',
      'add_more_input_label',
      'no_items_message',
    ];
  }

  /****************************************************************************/
  // Property methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    if (!isset($this->defaultProperties)) {
      $properties = $this->defineDefaultProperties();
      $definition = $this->getPluginDefinition();
      \Drupal::moduleHandler()->alter(
        'webform_element_default_properties',
        $properties,
        $definition
      );
      $this->defaultProperties = $properties;
    }
    return $this->defaultProperties;
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslatableProperties() {
    if (!isset($this->translatableProperties)) {
      $properties = $this->defineTranslatableProperties();
      $definition = $this->getPluginDefinition();
      \Drupal::moduleHandler()->alter(
        'webform_element_translatable_properties',
        $properties,
        $definition
      );
      $this->translatableProperties = array_unique($properties);
    }
    return $this->translatableProperties;
  }

  /**
   * Get default multiple properties used by most elements.
   *
   * @return array
   *   An associative array containing default multiple properties.
   *
   * @deprecated Scheduled for removal in Webform 8.x-6.x
   *   Use \Drupal\webform\Plugin\WebformElementBase::defineDefaultBaseProperties instead.
   */
  protected function getDefaultMultipleProperties() {
    @trigger_error('\Drupal\webform\Plugin\WebformElementBase::getDefaultMultipleProperties is scheduled for removal in Webform 8.x-6.x. Use \Drupal\webform\Plugin\WebformElementBase::defineDefaultBaseProperties instead.', E_USER_DEPRECATED);
    return $this->defineDefaultBaseProperties();
  }

  /**
   * Get default base properties used by all elements.
   *
   * @return array
   *   An associative array containing base properties used by all elements.
   *
   * @deprecated Scheduled for removal in Webform 8.x-6.x
   *   Use \Drupal\webform\Plugin\WebformElementBase::defineDefaultBaseProperties instead.
   */
  protected function getDefaultBaseProperties() {
    @trigger_error('\Drupal\webform\Plugin\WebformElementBase::getDefaultBaseProperties is scheduled for removal in Webform 8.x-6.x. Use \Drupal\webform\Plugin\WebformElementBase::defineDefaultBaseProperties instead.', E_USER_DEPRECATED);
    return $this->defineDefaultBaseProperties();
  }

  /**
   * {@inheritdoc}
   */
  public function hasProperty($property_name) {
    $default_properties = $this->getDefaultProperties();
    return array_key_exists($property_name, $default_properties);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperty($property_name) {
    $default_properties = $this->getDefaultProperties();
    return (array_key_exists($property_name, $default_properties)) ? $default_properties[$property_name] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getElementProperty(array $element, $property_name) {
    return (isset($element["#$property_name"])) ? $element["#$property_name"] : $this->getDefaultProperty($property_name);
  }

  /**
   * Set element's default callback.
   *
   * This makes sure that an element's default callback is not clobbered by
   * any additional callbacks.
   *
   * @param array $element
   *   A render element.
   * @param string $callback_name
   *   A render element's callback.
   */
  protected function setElementDefaultCallback(array &$element, $callback_name) {
    $callback_name = ($callback_name[0] !== '#') ? '#' . $callback_name : $callback_name;
    $callback_value = $this->getElementInfoDefaultProperty($element, $callback_name) ?: [];
    if (!empty($element[$callback_name])) {
      $element[$callback_name] = array_merge($callback_value, $element[$callback_name]);
    }
    else {
      $element[$callback_name] = $callback_value;
    }
  }

  /**
   * Get a render element's default property.
   *
   * @param array $element
   *   A render element.
   * @param string $property_name
   *   An element's property name.
   *
   * @return mixed
   *   A render element's default value, or NULL if
   *   property does not exist.
   */
  protected function getElementInfoDefaultProperty(array $element, $property_name) {
    if (!isset($element['#type'])) {
      return NULL;
    }
    $property_name = ($property_name[0] !== '#') ? '#' . $property_name : $property_name;
    $type = $element['#type'];
    return $this->elementInfo->getInfoProperty($type, $property_name, NULL)
      ?: $this->elementInfo->getInfoProperty("webform_$type", $property_name, NULL);
  }

  /****************************************************************************/
  // Definition and meta data methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function getFormElementClassDefinition() {
    $definition = $this->elementInfo->getDefinition($this->getBaseId());
    return $definition['class'];
  }

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
  public function getPluginCategory() {
    return $this->pluginDefinition['category'] ?: $this->t('Other elements');
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
  public function getDefaultKey() {
    return (isset($this->pluginDefinition['default_key'])) ? $this->pluginDefinition['default_key'] : NULL;
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
  public function hasManagedFiles(array $element) {
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
    $item_format = $this->getItemFormat($element);
    $items_format = $this->getItemsFormat($element);

    // Check is multi value element.
    if ($this->hasMultipleValues($element) && in_array($items_format, ['ol', 'ul', 'hr', 'custom'])) {
      return TRUE;
    }

    // Check if custom items template has HTML block tags.
    if ($items_format == 'custom' && isset($element['#format_items_html']) && WebformHtmlHelper::hasBlockTags($element['#format_items_html'])) {
      return TRUE;
    }

    // Check if custom item template has HTML block tags.
    if ($item_format == 'custom' && isset($element['#format_html']) && WebformHtmlHelper::hasBlockTags($element['#format_html'])) {
      return TRUE;
    }

    return $this->pluginDefinition['multiline'];
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
  public function isExcluded() {
    return $this->configFactory->get('webform.settings')->get('element.excluded_elements.' . $this->pluginDefinition['id']) ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return !$this->isExcluded();
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
    return $this->elementInfo->getInfo($this->getBaseId());
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

      // Skip disabled or hidden.
      if ($element_instance->isDisabled() || $element_instance->isHidden()) {
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

    // Set #admin_title to #title without any HTML markup.
    if (!empty($element['#title']) && empty($element['#admin_title'])) {
      $element['#admin_title'] = strip_tags($element['#title']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    $attributes_property = ($this->hasWrapper($element)) ? '#wrapper_attributes' : '#attributes';
    if ($webform_submission) {
      // Add webform and webform_submission IDs to every element.
      $element['#webform'] = $webform_submission->getWebform()->id();
      $element['#webform_submission'] = $webform_submission->id();

      // Check is the element is disabled and hide it.
      if ($this->isDisabled()) {
        if ($webform_submission->getWebform()->access('edit')) {
          $this->displayDisabledWarning($element);
        }
        $element['#access'] = FALSE;
      }

      // Apply element specific access rules.
      $operation = ($webform_submission->isCompleted()) ? 'update' : 'create';
      // Make sure the webform and submission is set before
      // checking access rules.
      $this->setEntities($webform_submission);
      $element['#access'] = $this->checkAccessRules($operation, $element);
    }

    // Enable webform template preprocessing enhancements.
    // @see \Drupal\webform\Utility\WebformElementHelper::isWebformElement
    $element['#webform_element'] = TRUE;

    // Add #allowed_tags.
    $allowed_tags = $this->configFactory->get('webform.settings')->get('element.allowed_tags');
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
    // Inline fieldset layout is handled via webform_preprocess_fieldset().
    // @see webform_preprocess_fieldset()
    if (isset($element['#title_display']) && $element['#title_display'] == 'inline') {
      // Store reference to unset #title_display.
      $element['#_title_display'] = $element['#title_display'];
      unset($element['#title_display']);
      $element['#wrapper_attributes']['class'][] = 'webform-element--title-inline';
    }

    // Check markup properties.
    $markup_properties = [
      '#description',
      '#help',
      '#more',
      '#multiple__no_items_message',
    ];
    foreach ($markup_properties as $markup_property) {
      if (isset($element[$markup_property]) && !is_array($element[$markup_property])) {
        $element[$markup_property] = WebformHtmlEditor::checkMarkup($element[$markup_property]);
      }
    }

    // Add default description display.
    $default_description_display = $this->configFactory->get('webform.settings')->get('element.default_description_display');
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

    // Add .webform-has-field-prefix and .webform-has-field-suffix class.
    if (!empty($element['#field_prefix'])) {
      $element[$attributes_property]['class'][] = 'webform-has-field-prefix';
    }
    if (!empty($element['#field_suffix'])) {
      $element[$attributes_property]['class'][] = 'webform-has-field-suffix';
    }

    // Add 'data-webform-states-no-clear' attribute if #states_clear is FALSE.
    if (isset($element['#states_clear']) && $element['#states_clear'] === FALSE) {
      $element[$attributes_property]['data-webform-states-no-clear'] = TRUE;
    }

    // Set element's #element_validate callback so that is not replaced when
    // we append additional #element_validate callbacks.
    $this->setElementDefaultCallback($element, 'element_validate');
    $this->prepareElementValidateCallbacks($element, $webform_submission);

    if ($this->isInput($element)) {
      // Handle #readonly support.
      // @see \Drupal\Core\Form\FormBuilder::handleInputElement
      if (!empty($element['#readonly'])) {
        $element['#attributes']['readonly'] = 'readonly';
        if ($this->hasProperty('wrapper_attributes')) {
          $element['#wrapper_attributes']['class'][] = 'webform-readonly';
        }
      }

      // Set custom required error message as 'data-required-error' attribute.
      // @see Drupal.behaviors.webformRequiredError
      // @see webform.form.js
      if (!empty($element['#required_error'])) {
        $element['#attributes']['data-webform-required-error'] = WebformHtmlHelper::toPlainText($element['#required_error']);
        $element['#required_error'] = WebformHtmlHelper::toHtmlMarkup($element['#required_error']);
      }
    }

    // Replace tokens for all properties.
    if ($webform_submission) {
      $this->replaceTokens($element, $webform_submission);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function finalize(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    // Set element's #element_validate callback so that is not replaced when
    // we append additional #pre_render callbacks.
    $this->setElementDefaultCallback($element, 'pre_render');
    $this->prepareElementPreRenderCallbacks($element, $webform_submission);

    // Prepare composite element.
    $this->prepareCompositeFormElement($element);

    // Prepare multiple element.
    $this->prepareMultipleWrapper($element);

    // Prepare #states and flexbox wrapper.
    $this->prepareWrapper($element);

    // Set hidden element #after_build handler.
    $this->setElementDefaultCallback($element, 'after_build');
    $element['#after_build'][] = [get_class($this), 'hiddenElementAfterBuild'];
  }

  /**
   * {@inheritdoc}
   */
  public function alterForm(array &$element, array &$form, FormStateInterface $form_state) {
    // Do nothing.
  }

  /**
   * Webform element #after_build callback.
   *
   * Wrap #element_validate so that we suppress element validation errors.
   */
  public static function hiddenElementAfterBuild(array $element, FormStateInterface $form_state) {
    if (!isset($element['#access']) || $element['#access']) {
      return $element;
    }

    // Disabled #required validation for hidden elements.
    $element['#required'] = FALSE;

    return WebformElementHelper::setElementValidate($element);
  }

  /**
   * {@inheritdoc}
   */
  public function checkAccessRules($operation, array $element, AccountInterface $account = NULL) {
    // Respect elements that already have their #access set to FALSE.
    if (isset($element['#access']) && $element['#access'] === FALSE) {
      return FALSE;
    }

    // Get the current user, webform, and webform submission.
    $account = $account ?: $this->currentUser;
    $webform = $this->getWebform();
    $webform_submission = $this->getWebformSubmission();

    // If webform is missing, throw an exception.
    if (!$webform) {
      throw new \Exception("Webform entity is required to check and element's access (rules).");
    }

    // If #private, check that the current user can 'view any submission'.
    if (!empty($element['#private']) && !$webform->access('submission_view_any', $account)) {
      return FALSE;
    }

    // Check webform and other modules access results.
    $access_result = $this->checkAccessRule($element, $operation, $account)
      ? AccessResult::allowed()
      : AccessResult::neutral();

    // Allow webform handlers to adjust the access and/or directly set an
    // element's #access to FALSE.
    $handler_result = $webform->invokeHandlers('accessElement', $element, $operation, $account, $webform_submission);
    $access_result = $access_result->orIf($handler_result);

    // Allow modules to adjust the element's access.
    $context = [
      'webform' => $webform,
      'webform_submission' => $webform_submission,
    ];
    $modules = \Drupal::moduleHandler()
      ->getImplementations('webform_element_access');
    foreach ($modules as $module) {
      $hook = $module . '_webform_element_access';
      $hook_result = $hook($operation, $element, $account, $context);
      $access_result = $access_result->orIf($hook_result);
    }

    // Grant access as provided by webform, webform handler(s) and/or
    // hook_webform_element_access() implementation.
    return $access_result->isAllowed();
  }

  /**
   * Checks an access rule against a user account's roles and id.
   *
   * @param array $element
   *   The element.
   * @param string $operation
   *   The operation.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user session for which to check access.
   *
   * @return bool
   *   The access result. Returns a TRUE if access is allowed.
   *
   * @see \Drupal\webform\Entity\Webform::checkAccessRule
   */
  protected function checkAccessRule(array $element, $operation, AccountInterface $account) {
    // If no access rules are set return NULL (no opinion).
    // @see \Drupal\webform\Plugin\WebformElementBase::defaultBaseProperties
    if (!isset($element['#access_' . $operation . '_roles'])
      && !isset($element['#access_' . $operation . '_users'])
      && !isset($element['#access_' . $operation . '_permissions'])) {
      return TRUE;
    }

    // If access roles are not set then use the anonymous and authenticated
    // roles from the element's default properties.
    // @see \Drupal\webform\Plugin\WebformElementBase::defaultBaseProperties
    if (!isset($element['#access_' . $operation . '_roles'])) {
      $element['#access_' . $operation . '_roles'] = $this->getDefaultProperty('access_' . $operation . '_roles') ?: [];
    }
    if (array_intersect($element['#access_' . $operation . '_roles'], $account->getRoles())) {
      return TRUE;
    }

    if (isset($element['#access_' . $operation . '_users']) && in_array($account->id(), $element['#access_' . $operation . '_users'])) {
      return TRUE;
    }

    if (isset($element['#access_' . $operation . '_permissions'])) {
      foreach ($element['#access_' . $operation . '_permissions'] as $permission) {
        if ($account->hasPermission($permission)) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function replaceTokens(array &$element, EntityInterface $entity = NULL) {
    foreach ($element as $key => $value) {
      // Only replace tokens in properties.
      if (Element::child($key)) {
        continue;
      }

      // Ignore tokens in #template and #format_* properties.
      if (in_array($key, ['#template', '#format_html', '#format_text', 'format_items_html', 'format_items_text'])) {
        continue;
      }

      $element[$key] = $this->tokenManager->replaceNoRenderContext($value, $entity);
    }
  }

  /**
   * Prepare an element's validation callbacks.
   *
   * @param array $element
   *   An element.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   */
  protected function prepareElementValidateCallbacks(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    // Validation callbacks are only applicable to inputs.
    if (!$this->isInput($element)) {
      return;
    }

    // Add webform element #minlength, #multiple, and/or #unique
    // validation handler.
    if (isset($element['#minlength'])) {
      $element['#element_validate'][] = [get_class($this), 'validateMinlength'];
    }
    if (isset($element['#multiple']) && $element['#multiple'] > 1) {
      $element['#element_validate'][] = [get_class($this), 'validateMultiple'];
    }
    if (isset($element['#unique']) && $webform_submission) {
      $element['#element_validate'][] = [get_class($this), 'validateUnique'];
    }
  }

  /**
   * Prepare an element's pre render callbacks.
   *
   * @param array $element
   *   An element.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   */
  protected function prepareElementPreRenderCallbacks(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    // Do nothing.
  }

  /**
   * Replace Core's composite #pre_render with Webform's composite #pre_render.
   *
   * @param array $element
   *   An element.
   *
   * @see \Drupal\Core\Render\Element\CompositeFormElementTrait
   * @see \Drupal\webform\Element\WebformCompositeFormElementTrait
   */
  protected function prepareCompositeFormElement(array &$element) {
    if (empty($element['#pre_render'])) {
      return;
    }

    // Replace preRenderCompositeFormElement with
    // preRenderWebformCompositeFormElement.
    foreach ($element['#pre_render'] as $index => $pre_render) {
      if (is_array($pre_render) && $pre_render[1] === 'preRenderCompositeFormElement') {
        $element['#pre_render'][$index] = [get_called_class(), 'preRenderWebformCompositeFormElement'];
      }
    }
  }

  /**
   * Set an elements #states and flexbox wrapper.
   *
   * @param array $element
   *   An element.
   */
  protected function prepareWrapper(array &$element) {
    $has_states_wrapper = $this->pluginDefinition['states_wrapper'];
    $has_flexbox_wrapper = !empty($element['#webform_parent_flexbox']);
    if (!$has_states_wrapper && !$has_flexbox_wrapper) {
      return;
    }

    $class = get_class($this);

    // Fix #states wrapper.
    if ($has_states_wrapper) {
      $element['#pre_render'][] = [$class, 'preRenderFixStatesWrapper'];
    }

    // Add flex(box) wrapper.
    if ($has_flexbox_wrapper) {
      $element['#pre_render'][] = [$class, 'preRenderFixFlexboxWrapper'];
    }
  }

  /**
   * Fix state wrapper.
   *
   * Notes:
   * - Certain elements don't support #states so a workaround is to adds a
   *   wrapper that renders the #states in a #prefix and #suffix div tag.
   * - Composite elements tend not to properly handle #states.
   * - Composite elements need propagate a visible/hidden #states to
   *   sub-element required #state.
   *
   * @param array $element
   *   An element.
   *
   * @return array
   *   An element with #states added to the #prefix and #suffix.
   */
  public static function preRenderFixStatesWrapper(array $element) {
    WebformElementHelper::fixStatesWrapper($element);
    return $element;
  }

  /**
   * Fix flexbox wrapper.
   *
   * @param array $element
   *   An element.
   *
   * @return array
   *   An element with flexbox wrapper added to the #prefix and #suffix.
   */
  public static function preRenderFixFlexboxWrapper(array $element) {
    $flex = (isset($element['#flex'])) ? $element['#flex'] : 1;
    $element += ['#prefix' => '', '#suffix' => ''];
    $element['#prefix'] = '<div class="webform-flex webform-flex--' . $flex . '"><div class="webform-flex--container">' . $element['#prefix'];
    $element['#suffix'] = $element['#suffix'] . '</div></div>';
    return $element;
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
    $element['#element'] = array_diff_key($element['#element'], array_flip(['#access', '#default_value', '#description', '#description_display', '#required', '#required_error', '#states', '#wrapper_attributes', '#prefix', '#suffix', '#element', '#tags', '#multiple']));

    // Propagate #states to sub element.
    // @see \Drupal\webform\Element\WebformCompositeBase::processWebformComposite
    if (!empty($element['#states'])) {
      $element['#element']['#_webform_states'] = $element['#states'];
    }

    // Always make the title invisible.
    $element['#element']['#title_display'] = 'invisible';

    // Set hidden element #after_build handler.
    $element['#element']['#after_build'][] = [get_class($this), 'hiddenElementAfterBuild'];

    // Remove 'for' from the main element's label.
    // This must be done after the $element['#element' is defined.
    $element['#label_attributes']['webform-remove-for-attribute'] = TRUE;

    // Change the element to a multiple element.
    $element['#type'] = 'webform_multiple';
    $element['#webform_multiple'] = TRUE;

    // Set cardinality from #multiple.
    if ($element['#multiple'] > 1) {
      $element['#cardinality'] = $element['#multiple'];
    }

    // Apply multiple properties.
    $multiple_properties = $this->defineDefaultMultipleProperties();
    foreach ($multiple_properties as $multiple_property => $multiple_value) {
      if (strpos($multiple_property, 'multiple__') === 0) {
        $property_name = str_replace('multiple__', '', $multiple_property);
        $element["#$property_name"] = (isset($element["#$multiple_property"])) ? $element["#$multiple_property"] : $multiple_value;
      }
    }

    // If header label is defined use it for the #header.
    if (!empty($element['#multiple__header_label'])) {
      $element['#header'] = $element['#multiple__header_label'];
    }

    // Remove properties that should only be applied to the child element.
    $element = array_diff_key($element, array_flip(['#attributes', '#field_prefix', '#field_suffix', '#pattern', '#placeholder', '#maxlength', '#element_validate', '#pre_render']));

    // Apply #unique multiple validation.
    if (isset($element['#unique'])) {
      $element['#element_validate'][] = [get_class($this), 'validateUniqueMultiple'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function displayDisabledWarning(array $element) {
    $t_args = [
      '%title' => $this->getLabel($element),
      '%type' => $this->getPluginLabel(),
      ':href' => Url::fromRoute('webform.config.elements')->toString(),
    ];
    if ($this->currentUser->hasPermission('administer webform')) {
      $message = $this->t('%title is a %type element, which has been disabled and will not be rendered. Go to the <a href=":href">admin settings</a> page to enable this element.', $t_args);
    }
    else {
      $message = $this->t('%title is a %type element, which has been disabled and will not be rendered. Please contact a site administrator.', $t_args);
    }
    $this->messenger()->addWarning($message);

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
    return (!empty($element['#title'])) ? $element['#title'] : $element['#webform_key'];
  }

  /**
   * {@inheritdoc}
   */
  public function getAdminLabel(array $element) {
    $element += ['#admin_title' => '', '#title' => '', '#webform_key' => ''];
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
  public function buildHtml(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    return $this->build('html', $element, $webform_submission, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function buildText(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    return $this->build('text', $element, $webform_submission, $options);
  }

  /**
   * Build an element as text or HTML.
   *
   * @param string $format
   *   Format of the element, text or html.
   * @param array $element
   *   An element.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param array $options
   *   An array of options.
   *
   * @return array
   *   A render array representing an element as text or HTML.
   */
  protected function build($format, array &$element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $options['multiline'] = $this->isMultiline($element);
    $format_function = 'format' . ucfirst($format);
    $value = $this->$format_function($element, $webform_submission, $options);

    // Handle empty value.
    if ($value === '') {
      // Return NULL if empty is excluded.
      if ($this->isEmptyExcluded($element, $options)) {
        return NULL;
      }
      // Else set the formatted value to empty message/placeholder.
      else {
        $value = $this->configFactory->get('webform.settings')->get('element.empty_message');
      }
    }

    // Convert string to renderable #markup.
    if (is_string($value)) {
      $value = ['#' . ($format === 'text' ? 'plain_text' : 'markup') => $value];
    }

    return [
      '#theme' => 'webform_element_base_' . $format,
      '#element' => $element,
      '#value' => $value,
      '#webform_submission' => $webform_submission,
      '#options' => $options,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function formatHtml(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    return $this->format('Html', $element, $webform_submission, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function formatText(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    return $this->format('Text', $element, $webform_submission, $options);
  }

  /**
   * Format an element's value as HTML or plain text.
   *
   * @param string $type
   *   The format type, HTML or Text.
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
  protected function format($type, array &$element, WebformSubmissionInterface $webform_submission, array $options = []) {
    if (!$this->hasValue($element, $webform_submission, $options) && !$this->isContainer($element)) {
      return '';
    }

    $item_function = 'format' . $type . 'Item';
    $items_function = 'format' . $type . 'Items';
    if ($this->hasMultipleValues($element)) {
      // Return $options['delta'] which is used by tokens.
      // @see _webform_token_get_submission_value()
      if (isset($options['delta'])) {
        return $this->$item_function($element, $webform_submission, $options);
      }
      elseif ($this->getItemsFormat($element) === 'custom' && !empty($element['#format_items_' . strtolower($type)])) {
        return $this->formatCustomItems($type, $element, $webform_submission, $options);
      }
      else {
        return $this->$items_function($element, $webform_submission, $options);
      }
    }
    else {
      if ($this->getItemFormat($element) === 'custom' && !empty($element['#format_' . strtolower($type)])) {
        return $this->formatCustomItem($type, $element, $webform_submission, $options);
      }
      else {
        return $this->$item_function($element, $webform_submission, $options);
      }
    }
  }

  /**
   * Format an element's items using custom HTML or plain text.
   *
   * @param string $type
   *   The format type, HTML or Text.
   * @param array $element
   *   An element.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param array $options
   *   An array of options.
   * @param array $context
   *   (optional) Context to be passed to inline Twig template.
   *
   * @return array|string
   *   The element's items formatted as plain text or a render array.
   */
  protected function formatCustomItems($type, array &$element, WebformSubmissionInterface $webform_submission, array $options = [], array $context = []) {
    $name = strtolower($type);

    // Get value.
    $value = $this->getValue($element, $webform_submission, $options);

    // Get items.
    $items = [];
    $item_function = 'format' . $type . 'Item';
    foreach (array_keys($value) as $delta) {
      $items[] = $this->$item_function($element, $webform_submission, ['delta' => $delta] + $options);
    }

    // Get template.
    $template = trim($element['#format_items_' . $name]);

    // Get context.
    $options += ['context' => []];
    $context += [
      'value' => $value,
      'items' => $items,
    ];

    return WebformTwigExtension::buildTwigTemplate($webform_submission, $template, $options, $context);
  }

  /**
   * Format an element's items as HTML.
   *
   * @param array $element
   *   An element.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param array $options
   *   An array of options.
   *
   * @return string|array
   *   The element's items as HTML.
   */
  protected function formatHtmlItems(array &$element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);

    // Get items.
    $items = [];
    foreach (array_keys($value) as $delta) {
      $items[] = $this->formatHtmlItem($element, $webform_submission, ['delta' => $delta] + $options);
    }

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
            $build[] = ['#markup' => ' ' . t('and') . ' '];
          }
          elseif ($index !== ($total - 1)) {
            if ($index === ($total - 2)) {
              $build[] = ['#markup' => ', ' . t('and') . ' '];
            }
            else {
              $build[] = ['#markup' => ', '];
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
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param array $options
   *   An array of options.
   *
   * @return string
   *   The element's items as text.
   */
  protected function formatTextItems(array &$element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);

    // Get items.
    $items = [];
    foreach (array_keys($value) as $delta) {
      $items[] = $this->formatTextItem($element, $webform_submission, ['delta' => $delta] + $options);
    }

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
   * Format an element's item using custom HTML or plain text.
   *
   * @param string $type
   *   The format type, HTML or Text.
   * @param array $element
   *   An element.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param array $options
   *   An array of options.
   * @param array $context
   *   (optional) Context to be passed to inline Twig template.
   *
   * @return array|string
   *   The element's item formatted as plain text or a render array.
   */
  protected function formatCustomItem($type, array &$element, WebformSubmissionInterface $webform_submission, array $options = [], array $context = []) {
    $name = strtolower($type);

    // Get template.
    $template = trim($element['#format_' . $name]);

    // Get context.value.
    $context['value'] = $this->getValue($element, $webform_submission, $options);

    // Get content.item.
    $context['item'] = [];
    // Parse item.format from template and add to context.
    if (preg_match_all("/item(?:\[['\"]|\.)([a-zA-Z0-9-_:]+)/", $template, $matches)) {
      $formats = array_unique($matches[1]);
      $item_function = 'format' . $type . 'Item';
      foreach ($formats as $format) {
        $context['item'][$format] = $this->$item_function(['#format' => $format] + $element, $webform_submission, $options);
      }
    }

    // Return inline template.
    if ($type === 'Text') {
      return WebformTwigExtension::renderTwigTemplate($webform_submission, $template, $options, $context);
    }
    else {
      return WebformTwigExtension::buildTwigTemplate($webform_submission, $template, $options, $context);
    }
  }

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
   *   The element's value formatted as HTML or a render array.
   */
  protected function formatHtmlItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $format = $this->getItemFormat($element);
    $value = $this->formatTextItem($element, $webform_submission, ['prefixing' => FALSE] + $options);

    if ($format === 'raw') {
      return Markup::create($value);
    }

    // Build a render that used #plain_text so that HTML characters are escaped.
    // @see \Drupal\Core\Render\Renderer::ensureMarkupIsSafe
    $build = ['#plain_text' => $value];

    $options += ['prefixing' => TRUE];
    if ($options['prefixing']) {
      if (isset($element['#field_prefix'])) {
        $build['#prefix'] = $element['#field_prefix'];
      }
      if (isset($element['#field_suffix'])) {
        $build['#suffix'] = $element['#field_suffix'];
      }
    }

    return $build;
  }

  /**
   * Format an element's value as text.
   *
   * @param array $element
   *   An element.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param array $options
   *   An array of options.
   *
   * @return string
   *   The element's value formatted as text.
   *
   * @see _webform_token_get_submission_value()
   */
  protected function formatTextItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);
    $format = $this->getItemFormat($element);

    if ($format === 'raw') {
      return $value;
    }

    $options += ['prefixing' => TRUE];
    if ($options['prefixing']) {
      if (isset($element['#field_prefix'])) {
        $value = strip_tags($element['#field_prefix']) . $value;
      }
      if (isset($element['#field_suffix'])) {
        $value .= strip_tags($element['#field_suffix']);
      }
    }

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function hasValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);
    return ($value === '' || $value === NULL || (is_array($value) && empty($value))) ? FALSE : TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    if (!isset($element['#webform_key']) && isset($element['#value'])) {
      return $element['#value'];
    }

    $webform_key = (isset($options['webform_key'])) ? $options['webform_key'] : $element['#webform_key'];
    $value = $webform_submission->getElementData($webform_key);
    // Is value is NULL and there is a #default_value, then use it.
    if ($value === NULL && isset($element['#default_value'])) {
      $value = $element['#default_value'];
    }

    // Return multiple (delta) value or composite (composite_key) value.
    if (is_array($value)) {
      // Return $options['delta'] which is used by tokens.
      // @see _webform_token_get_submission_value()
      if (isset($options['delta'])) {
        $value = (isset($value[$options['delta']])) ? $value[$options['delta']] : NULL;
      }

      // Return $options['composite_key'] which is used by composite elements.
      // @see \Drupal\webform\Plugin\WebformElement\WebformCompositeBase::formatTableColumn
      if ($value && isset($options['composite_key'])) {
        $value = (isset($value[$options['composite_key']])) ? $value[$options['composite_key']] : NULL;
      }
    }

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function getRawValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $element['#format'] = 'raw';
    return $this->getValue($element, $webform_submission, $options);
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
  // Preview method.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function preview() {
    return [
      '#type' => $this->getTypeName(),
      '#title' => $this->getPluginLabel(),
    ];
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
  public function formatTableColumn(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    return $this->formatHtml($element, $webform_submission);
  }

  /**
   * {@inheritdoc}
   */
  public function isEmptyExcluded(array $element, array $options) {
    $options += [
      'exclude_empty' => TRUE,
    ];
    return !empty($options['exclude_empty']);
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
      $prefix = $this->getKey($element) . $export_options['header_prefix_key_delimiter'];
    }

    foreach ($header as $index => $column) {
      $header[$index] = $prefix . $column;
    }

    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildExportRecord(array $element, WebformSubmissionInterface $webform_submission, array $export_options) {
    $element['#format_items'] = $export_options['multiple_delimiter'];
    return [$this->formatText($element, $webform_submission, $export_options)];
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

    if (!empty($element['#value']) && mb_strlen($element['#value']) < $element['#minlength']) {
      $t_args = [
        '%name' => empty($element['#title']) ? $element['#parents'][0] : $element['#title'],
        '%min' => $element['#minlength'],
        '%length' => mb_strlen($element['#value']),
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

    /** @var \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager */
    $element_manager = \Drupal::service('plugin.manager.webform.element');

    $name = $element['#webform_key'];
    $value = NestedArray::getValue($form_state->getValues(), $element['#parents']);

    // Skip composite elements.
    $element_plugin = $element_manager->getElementInstance($element);
    if ($element_plugin->isComposite()) {
      return;
    }

    // Skip empty values but allow for '0'.
    if ($value === '' || $value === NULL || (is_array($value) && empty($value))) {
      return;
    }

    /** @var \Drupal\webform\WebformSubmissionForm $form_object */
    $form_object = $form_state->getFormObject();
    /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
    $webform_submission = $form_object->getEntity();
    $webform = $webform_submission->getWebform();

    // Build unique query which return a single duplicate value.
    $query = \Drupal::database()->select('webform_submission', 'ws');
    $query->leftJoin('webform_submission_data', 'wsd', 'ws.sid = wsd.sid');
    $query->fields('wsd', ['value']);
    $query->condition('wsd.webform_id', $webform->id());
    $query->condition('wsd.name', $name);
    $query->condition('wsd.value', (array) $value, 'IN');
    // Unique user condition.
    if (!empty($element['#unique_user'])) {
      $query->condition('ws.uid', $webform_submission->getOwnerId());
    }
    // Unique (source) entity condition.
    if (!empty($element['#unique_entity'])) {
      if ($source_entity = $webform_submission->getSourceEntity()) {
        $query->condition('ws.entity_type', $source_entity->getEntityTypeId());
        $query->condition('ws.entity_id', $source_entity->id());
      }
      else {
        $query->isNull('ws.entity_type');
        $query->isNull('ws.entity_id');
      }
    }
    // Exclude the current webform submission.
    if ($sid = $webform_submission->id()) {
      $query->condition('ws.sid', $sid, '<>');
    }
    // Get single duplicate value.
    $query->range(0, 1);
    $duplicate_value = $query->execute()->fetchField();

    // Skip NULL or empty string value.
    if ($duplicate_value === FALSE || $duplicate_value === '') {
      return;
    }

    if (isset($element['#unique_error'])) {
      $form_state->setError($element, WebformHtmlHelper::toHtmlMarkup($element['#unique_error']));
    }
    elseif (isset($element['#title'])) {
      // Get #options display value.
      if (isset($element['#options'])) {
        $duplicate_value = WebformOptionsHelper::getOptionText($duplicate_value, $element['#options'], TRUE);
      }
      $t_args = [
        '%name' => empty($element['#title']) ? $element['#parents'][0] : $element['#title'],
        '%value' => $duplicate_value,
      ];
      $form_state->setError($element, t('The value %value has already been submitted once for the %name element. You may have already submitted this webform, or you need to use a different value.', $t_args));
    }
    else {
      $form_state->setError($element);
    }
  }

  /**
   * Form API callback. Validate element #unique multiple values.
   */
  public static function validateUniqueMultiple(array &$element, FormStateInterface $form_state) {
    if (!isset($element['#unique'])) {
      return;
    }

    $name = $element['#name'];
    $value = NestedArray::getValue($form_state->getValues(), $element['#parents']);

    if (empty($value)) {
      return;
    }

    // Compare number of values to unique number of values.
    if (count($value) != count(array_unique($value))) {
      $duplicates = WebformArrayHelper::getDuplicates($value);

      if (isset($element['#unique_error'])) {
        $form_state->setError($element, WebformHtmlHelper::toHtmlMarkup($element['#unique_error']));
      }
      elseif (isset($element['#title'])) {
        $t_args = [
          '%name' => empty($element['#title']) ? $name : $element['#title'],
          '%value' => reset($duplicates),
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
    $visibility_optgroup = (string) $this->t('Visibility');
    $state_optgroup = (string) $this->t('State');
    $validation_optgroup = (string) $this->t('Validation');
    $value_optgroup = (string) $this->t('Value');

    $states = [];

    // Set default states that apply to the element/container and sub elements.
    $states += [
      $visibility_optgroup => [
        'visible' => $this->t('Visible'),
        'invisible' => $this->t('Hidden'),
        'visible-slide' => $this->t('Visible (Slide)'),
        'invisible-slide' => $this->t('Hidden (Slide)'),
      ],
      $state_optgroup => [
        'enabled' => $this->t('Enabled'),
        'disabled' => $this->t('Disabled'),
      ],
      $validation_optgroup => [
        'required' => $this->t('Required'),
        'optional' => $this->t('Optional'),
      ],
    ];

    // Set readwrite/readonly states for any element that supports it
    // and containers.
    if ($this->hasProperty('readonly') || $this->isContainer(['#type' => $this->getPluginId()])) {
      $states[$state_optgroup] += [
        'readwrite' => $this->t('Read/write'),
        'readonly' => $this->t('Read-only'),
      ];
    }

    // Set checked/unchecked states for any element that contains checkboxes.
    if ($this instanceof Checkbox || $this instanceof Checkboxes) {
      $states[$value_optgroup] = [
        'checked' => $this->t('Checked'),
        'unchecked' => $this->t('Unchecked'),
      ];
    }

    // Set expanded/collapsed states for any details element.
    if ($this instanceof Details) {
      $states[$state_optgroup] += [
        'expanded' => $this->t('Expanded'),
        'collapsed' => $this->t('Collapsed'),
      ];
    }

    return $states;
  }

  /**
   * {@inheritdoc}
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
   * {@inheritdoc}
   */
  public function getElementSelectorSourceValues(array $element) {
    return [];
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

  /**
   * {@inheritdoc}
   */
  public function getElementSelectorInputValue($selector, $trigger, array $element, WebformSubmissionInterface $webform_submission) {
    if ($this->isComposite()) {
      $input_name = WebformSubmissionConditionsValidator::getSelectorInputName($selector);
      $composite_key = WebformSubmissionConditionsValidator::getInputNameAsArray($input_name, 1);
      if ($composite_key) {
        return $this->getRawValue($element, $webform_submission, ['composite_key' => $composite_key]);
      }
      else {
        return NULL;
      }
    }
    else {
      return $this->getRawValue($element, $webform_submission);
    }
  }

  /****************************************************************************/
  // Operation methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function preCreate(array &$element, array &$values) {}

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

    $element_properties = $form_state->get('element_properties');

    /**************************************************************************/
    // General.
    /**************************************************************************/

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
      '#maxlength' => NULL,
      '#description' => $this->t('This is used as a descriptive label when displaying this webform element.'),
      '#required' => TRUE,
      '#attributes' => ['autofocus' => 'autofocus'],
    ];
    $form['element']['value'] = [
      '#type' => 'textarea',
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

    /* Element description/help/more */

    $form['element_description'] = [
      '#type' => 'details',
      '#title' => $this->t('Element description/help/more'),
    ];
    $form['element_description']['description'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Description'),
      '#description' => $this->t('A short description of the element used as help for the user when he/she uses the webform.'),
    ];
    $form['element_description']['help'] = [
      '#type' => 'details',
      '#title' => $this->t('Help'),
      '#description' => $this->t("Displays a help tooltip after the element's title."),
      '#states' => [
        'invisible' => [
          [':input[name="properties[title_display]"]' => ['value' => 'invisible']],
        ],
      ],
    ];
    $form['element_description']['help']['help_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Help title'),
      '#description' => $this->t("The text displayed in help tooltip after the element's title.") . '<br /><br />' .
        $this->t("Defaults to the element's title"),
    ];
    $form['element_description']['help']['help'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Help text'),
      '#description' => $this->t("The text displayed in help tooltip after the element's title."),
    ];
    $form['element_description']['more'] = [
      '#type' => 'details',
      '#title' => $this->t('More'),
      '#description' => $this->t("Displays a read more hide/show widget below the element's description."),
    ];
    $form['element_description']['more']['more_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('More title'),
      '#description' => $this->t('The click-able label used to open and close more text.') . '<br /><br />' .
        $this->t('Defaults to: %value', ['%value' => $this->configFactory->get('webform.settings')->get('element.default_more_title')]),
    ];
    $form['element_description']['more']['more'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('More text'),
      '#description' => $this->t('A long description of the element that provides form additional information which can opened and closed.'),
    ];

    /* Form display */
    $form['form'] = [
      '#type' => 'details',
      '#title' => $this->t('Form display'),
    ];
    $form['form']['display_container'] = $this->getFormInlineContainer();
    $form['form']['display_container']['title_display'] = [
      '#type' => 'select',
      '#title' => $this->t('Title display'),
      '#empty_option' => $this->t('- Default -'),
      '#options' => [
        'before' => $this->t('Before'),
        'after' => $this->t('After'),
        'inline' => $this->t('Inline'),
        'invisible' => $this->t('Invisible'),
        'none' => $this->t('None'),
      ],
      '#description' => $this->t('Determines the placement of the title.'),
    ];
    // Displaying the title after the element is not supported by
    // the composite (fieldset) wrapper.
    if ($this->hasCompositeFormElementWrapper()) {
      unset($form['form']['display_container']['title_display']['#options']['after']);
    }
    $form['form']['display_container']['description_display'] = [
      '#type' => 'select',
      '#title' => $this->t('Description display'),
      '#empty_option' => $this->t('- Default -'),
      '#options' => [
        'before' => $this->t('Before'),
        'after' => $this->t('After'),
        'invisible' => $this->t('Invisible'),
        'tooltip' => $this->t('Tooltip'),
      ],
      '#description' => $this->t('Determines the placement of the description.'),
    ];
    $form['form']['display_container']['help_display'] = [
      '#type' => 'select',
      '#title' => $this->t('Help display'),
      '#empty_option' => $this->t('- Default -'),
      '#options' => [
        'title_before' => $this->t('Before title'),
        'title_after' => $this->t('After title'),
        'element_before' => $this->t('Before element'),
        'element_after' => $this->t('After element'),
      ],
      '#description' => $this->t('Determines the placement of the help tooltip.'),
    ];
    if ($this->hasProperty('title_display')) {
      $form['form']['title_display_message'] = [
        '#type' => 'webform_message',
        '#message_type' => 'warning',
        '#message_message' => $this->t("Please note: Settings the element's title display to 'none' means the title will not be rendered or accessible to screenreaders"),
        '#message_close' => TRUE,
        '#message_storage' => WebformMessage::STORAGE_LOCAL,
        '#access' => TRUE,
        '#states' => [
          'visible' => [
            ':input[name="properties[title_display]"]' => ['value' => 'none'],
          ],
        ],
      ];
    }

    // Remove unsupported title and description display from composite elements.
    if ($this->isComposite()) {
      unset($form['form']['display_container']['title_display']['#options']['inline']);
      unset($form['form']['display_container']['description_display']['#options']['tooltip']);
    }
    // Remove unsupported title display from certain element types.
    $element_types = [
      'webform_codemirror',
      'webform_email_confirm',
      'webform_htmleditor',
      'webform_mapping',
      'webform_signature',
    ];
    if (in_array($this->getPluginId(), $element_types)) {
      unset($form['form']['display_container']['title_display']['#options']['inline']);
    }
    // Remove unsupported title display from certain element types.
    $element_types = [
      'fieldset',
      'details',
      'webform_codemirror',
      'webform_email_confirm',
      'webform_htmleditor',
      'webform_image_select',
      'webform_likert',
      'webform_mapping',
      'webform_signature',
    ];
    if (in_array($this->getPluginId(), $element_types)) {
      unset($form['form']['display_container']['title_display']['#options']['inline']);
    }

    $form['form']['field_container'] = $this->getFormInlineContainer();
    $form['form']['field_container']['field_prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Field prefix'),
      '#description' => $this->t('Text or code that is placed directly in front of the input. This can be used to prefix an input with a constant string. Examples: $, #, -.'),
      '#size' => 10,
    ];
    $form['form']['field_container']['field_suffix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Field suffix'),
      '#description' => $this->t('Text or code that is placed directly after the input. This can be used to add a unit to an input. Examples: lb, kg, %.'),
      '#size' => 10,
    ];
    $form['form']['length_container'] = $this->getFormInlineContainer();
    $form['form']['length_container']['minlength'] = [
      '#type' => 'number',
      '#title' => $this->t('Minlength'),
      '#description' => $this->t('The element may still be empty unless it is required.'),
      '#min' => 1,
      '#size' => 4,
    ];    $form['form']['length_container']['maxlength'] = [
      '#type' => 'number',
      '#title' => $this->t('Maxlength'),
      '#description' => $this->t('Leaving blank will use the default maxlength.'),
      '#min' => 1,
      '#size' => 4,
    ];
    $form['form']['size_container'] = $this->getFormInlineContainer();
    $form['form']['size_container']['size'] = [
      '#type' => 'number',
      '#title' => $this->t('Size'),
      '#description' => $this->t('Leaving blank will use the default size.'),
      '#min' => 1,
      '#size' => 4,
    ];
    $form['form']['size_container']['rows'] = [
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
      '#type' => 'webform_select_other',
      '#title' => $this->t('Autocomplete'),
      '#description' => $this->t("Setting autocomplete to off will disable autocompletion for this element. Select 'Autofill' to use semantic attribute values for collecting certain types of user information."),
      '#options' => [
        'on' => $this->t('On'),
        'off' => $this->t('Off'),
      ],
      '#other__type' => 'select',
      '#other__option_label' => $this->t('Autofill…'),
      '#other__title' => $this->t('Autocomplete autofill'),
      '#other__description' => $this->t("Browsers sometimes have features for helping users fill forms in, for example prefilling the user's address based on earlier user input. The autocomplete (autofill) attribute can be used to hint to the user agent how to, or indeed whether to, provide such a feature."),
      '#other__options' => [
        (string) $this->t('Biographical attributes') => [
          "name" => $this->t('Full name'),
          "honorific-prefix" => $this->t('Honorific prefix'),
          "given-name" => $this->t('Given name'),
          "additional-name" => $this->t('Additional names'),
          "family-name" => $this->t('Family name'),
          "honorific-suffix" => $this->t('Honorific suffix'),
          "nickname" => $this->t('Nickname'),
          "username" => $this->t('Username'),
          "new-password" => $this->t('New password'),
          "current-password" => $this->t('Current password'),
          "organization-title" => $this->t('Organization job title'),
          "organization" => $this->t('Organization name'),
          "language" => $this->t('Preferred language'),
          "bday" => $this->t('Birthday'),
          "bday-day" => $this->t('Birthday day'),
          "bday-month" => $this->t('Birthday month'),
          "bday-year" => $this->t('Birthday year'),
          "sex" => $this->t('Gender'),
          "url" => $this->t('Contact URL'),
          "photo" => $this->t('Contact photo'),
          "email" => $this->t('Email'),
          "impp" => $this->t('Instant messaging URL'),
        ],
        (string) $this->t('Address attributes') => [
          "street-address" => $this->t('Street address (multiline)'),
          "address-line1" => $this->t('Address line 1'),
          "address-line2" => $this->t('Address line 2'),
          "address-line3" => $this->t('Address line 3'),
          "address-level1" => $this->t('Address level 1'),
          "address-level2" => $this->t('Address level 2'),
          "address-level3" => $this->t('Address level 3'),
          "address-level4" => $this->t('Address level 4'),
          "country" => $this->t('Country code'),
          "country-name" => $this->t('Country name'),
          "postal-code" => $this->t('Postal code / Zip code'),
        ],
        (string) $this->t('Telephone attributes') => [
          "tel" => $this->t('Telephone'),
          "home tel" => $this->t('Telephone - home'),
          "work tel" => $this->t('Telephone - work'),
          "work tel-extension" => $this->t('Telephone - work extension'),
          "mobile tel" => $this->t('Telephone - mobile'),
          "fax tel" => $this->t('Telephone - fax'),
          "pager tel" => $this->t('Telephone - pager'),
          "tel-country-code" => $this->t('Telephone country code'),
          "tel-national" => $this->t('Telephone national code'),
          "tel-area-code" => $this->t('Telephone area code'),
          "tel-local" => $this->t('Telephone local number'),
          "tel-local-prefix" => $this->t('Telephone local prefix'),
          "tel-local-suffix" => $this->t('Telephone local suffix'),
          "tel-extension" => $this->t('Telephone extension'),
        ],
        (string) $this->t('Commerce attributes') => [
          "cc-name" => $this->t('Name on card'),
          "cc-given-name" => $this->t('Given name on card'),
          "cc-additional-name" => $this->t('Additional names on card'),
          "cc-family-name" => $this->t('Family name on card'),
          "cc-number" => $this->t('Card number'),
          "cc-exp" => $this->t('Card expiry date'),
          "cc-exp-month" => $this->t('Card expiry month'),
          "cc-exp-year" => $this->t('Card expiry year'),
          "cc-csc" => $this->t('Card Security Code'),
          "cc-type" => $this->t('Card type'),
          "transaction-currency" => $this->t('Transaction currency'),
          "transaction-amount" => $this->t('Transaction amount'),
        ],
      ],
    ];
    $form['form']['disabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disabled'),
      '#description' => $this->t('Make this element non-editable with the user entered (e.g. via developer tools) value <strong>ignored</strong>. Useful for displaying default value. Changeable via JavaScript.'),
      '#return_value' => TRUE,
      '#weight' => 50,
    ];
    $form['form']['readonly'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Readonly'),
      '#description' => $this->t('Make this element non-editable with the user entered (e.g. via developer tools) value <strong>submitted</strong>. Useful for displaying default value. Changeable via JavaScript.'),
      '#return_value' => TRUE,
      '#weight' => 50,
    ];
    $form['form']['prepopulate'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Prepopulate'),
      '#description' => $this->t('Allow element to be populated using query string parameters.'),
      '#return_value' => TRUE,
      '#weight' => 50,
    ];
    // Disabled check element when prepopulate is enabled for all elements.
    if ($webform->getSetting('form_prepopulate') && $this->hasProperty('prepopulate')) {
      $form['form']['prepopulate_disabled'] = [
        '#description' => $this->t('Prepopulation is enabled for all form elements.'),
        '#value' => TRUE,
        '#disabled' => TRUE,
        '#access' => TRUE,
      ] + $form['form']['prepopulate'];
      $form['form']['prepopulate']['#value'] = FALSE;
      $form['form']['prepopulate']['#access'] = FALSE;
    }
    $form['form']['open'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Open'),
      '#description' => $this->t('Contents should be visible (open) to the user.'),
      '#return_value' => TRUE,
      '#weight' => 50,
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
    $error_messages = ['required_error', 'unique_error', 'pattern_error'];
    $validation_html_message_states = [];
    foreach ($error_messages as $error_message) {
      if ($this->hasProperty($error_message)) {
        if ($validation_html_message_states) {
          $validation_html_message_states[] = 'or';
        }
        $validation_html_message_states[] = [':input[name="properties[' . $error_message . ']"]' => ['value' => ['pattern' => '(<[a-z][^>]*>|&(?:[a-z]+|#\d+);)']]];
      }
    }
    if ($validation_html_message_states) {
      $form['validation']['html_message'] = [
        '#type' => 'webform_message',
        '#message_message' => $this->t('Validation error message contains HTML markup. HTML markup can not be display via HTML5 clientside validation and will be removed.'),
        '#message_type' => 'warning',
        '#states' => ['visible' => $validation_html_message_states],
        '#access' => TRUE,
      ];
    }
    $form['validation']['required_container'] = [
      '#type' => 'container',
    ];
    $form['validation']['required_container']['required'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Required'),
      '#description' => $this->t('Check this option if the user must enter a value.'),
      '#return_value' => TRUE,
    ];
    $form['validation']['required_container']['required_error'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Required message'),
      '#description' => $this->t('If set, this message will be used when a required webform element is empty, instead of the default "Field x is required." message.'),
      '#states' => [
        'visible' => [
          ':input[name="properties[required]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['validation']['unique_container'] = $this->getFormInlineContainer();
    $form['validation']['unique_container']['unique'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Unique'),
      '#description' => $this->t('Check that all entered values for this element are unique.'),
      '#return_value' => TRUE,
    ];
    $form['validation']['unique_container']['unique_entity'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Unique per entity'),
      '#description' => $this->t('Check that entered values for this element is unique for the current source entity.'),
      '#return_value' => TRUE,
      '#states' => [
        'visible' => [
          ['input[name="properties[unique]"]' => ['checked' => TRUE]],
        ],
      ],
    ];
    $form['validation']['unique_container']['unique_user'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Unique per user'),
      '#description' => $this->t('Check that entered values for this element are unique for the current user.'),
      '#return_value' => TRUE,
      '#states' => [
        'visible' => [
          ['input[name="properties[unique]"]' => ['checked' => TRUE]],
        ],
      ],
    ];
    $form['validation']['unique_error'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Unique message'),
      '#description' => $this->t('If set, this message will be used when an element\'s value are not unique, instead of the default "@message" message.', ['@message' => $this->t('The value %value has already been submitted once for the %name element. You may have already submitted this webform, or you need to use a different value.')]),
      '#states' => [
        'visible' => [
          [':input[name="properties[unique]"]' => ['checked' => TRUE]],
        ],
      ],
    ];

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
      '#description' => $this->t('The flex property specifies the length of the item, relative to the rest of the flexible items inside the same container.') . '<br /><br />' .
      $this->t('Defaults to: %value', ['%value' => 1]),
      '#options' => [0 => $this->t('0 (none)')] + array_combine($flex_range, $flex_range),
    ];

    /**************************************************************************/
    // Conditions.
    /**************************************************************************/

    /* Conditional logic */

    $form['conditional_logic'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Conditional logic'),
    ];
    $form['conditional_logic']['states'] = [
      '#type' => 'webform_element_states',
      '#state_options' => $this->getElementStateOptions(),
      '#selector_options' => $webform->getElementsSelectorOptions(),
      '#selector_sources' => $webform->getElementsSelectorSourceValues(),
      '#disabled_message' => TRUE,
    ];
    $form['conditional_logic']['states_clear'] = [
      '#type' => 'checkbox',
      '#title' => 'Clear value(s) when hidden',
      '#return_value' => TRUE,
      '#description' => ($this instanceof ContainerBase) ?
        $this->t("When this container is hidden all this container's subelement values will be cleared.")
        :
        $this->t("When this element is hidden, this element's value will be cleared."),
    ];
    if ($this->hasProperty('states') && $this->hasProperty('required')) {
      $form['conditional_logic']['states_required_message'] = [
        '#type' => 'webform_message',
        '#message_type' => 'warning',
        '#message_message' => $this->t('Please note when an element is hidden it will not be required.'),
        '#access' => TRUE,
        '#states' => [
          'visible' => [
            ':input[name="properties[required]"]' => ['checked' => TRUE],
          ],
        ],
      ];
    }

    /**************************************************************************/
    // Advanced.
    /**************************************************************************/

    /* Default value */

    $form['default'] = [
      '#type' => 'details',
      '#title' => $this->t('Default value'),
    ];
    if ($this->isComposite()) {
      $form['default']['default_value'] = [
        '#type' => 'webform_codemirror',
        '#mode' => 'yaml',
        '#title' => $this->t('Default value'),
      ];
    }
    else {
      $form['default']['default_value'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Default value'),
        '#maxlength' => NULL,
      ];
    }
    $form['default']['default_value']['#description'] = $this->t('The default value of the webform element.');
    if ($this->hasProperty('multiple')) {
      $form['default']['default_value']['#description'] .= ' ' . $this->t('For multiple options, use commas to separate multiple defaults.');
    }

    // Multiple.
    $form['multiple'] = [
      '#type' => 'details',
      '#title' => $this->t('Multiple settings'),
      '#states' => [
        'invisible' => [
          ':input[name="properties[multiple][container][cardinality]"]' => ['!value' => -1],
          ':input[name="properties[multiple][container][cardinality_number]"]' => ['value' => 1],
        ],
      ],
      '#attributes' => ['data-webform-states-no-clear' => TRUE],
    ];
    $form['multiple']['multiple__header'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display elements in table columns'),
      '#description' => $this->t("If checked, the composite sub-element titles will be displayed as the table header labels."),
      '#return_value' => TRUE,
    ];
    $form['multiple']['multiple__header_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Table header label'),
      '#description' => $this->t('This is used as the table header for this webform element when displaying multiple values.'),
    ];
    $form['multiple']['multiple__no_items_message'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('No items message'),
      '#description' => $this->t('This is used when there are no items entered.'),
    ];
    $form['multiple']['multiple__min_items'] = [
      '#type' => 'number',
      '#title' => $this->t('Minimum amount of items'),
      '#description' => $this->t('Minimum items defaults to 0 for optional elements and 1 for required elements.'),
      '#min' => 0,
      '#max' => 20,
    ];
    $form['multiple']['multiple__empty_items'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of empty items'),
      '#required' => TRUE,
      '#min' => 0,
      '#max' => 20,
    ];
    $form['multiple']['multiple__sorting'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow users to sort elements'),
      '#description' => $this->t('If unchecked, the elements will no longer be sortable.'),
      '#return_value' => TRUE,
    ];
    $form['multiple']['multiple__operations'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow users to add/remove elements'),
      '#description' => $this->t('If unchecked, the add/remove (+/x) buttons will be removed from each table row.'),
      '#return_value' => TRUE,
    ];
    $form['multiple']['multiple__add_more'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow users to add more items'),
      '#description' => $this->t('If checked, an add more input will be added below the multiple values.'),
      '#return_value' => TRUE,
    ];
    $form['multiple']['multiple__add_more_container'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input[name="properties[multiple__add_more]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['multiple']['multiple__add_more_container']['multiple__add_more_input'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow users to input the number of items to be added'),
      '#description' => $this->t('If checked, users will be able to input the number of items to be added.'),
      '#return_value' => TRUE,
    ];
    $form['multiple']['multiple__add_more_container']['multiple__add_more_button_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Add more button label'),
      '#description' => $this->t('This is used as the add more items button label for this webform element when displaying multiple values.'),
    ];
    $form['multiple']['multiple__add_more_container']['multiple__add_more_input_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Add more input label'),
      '#description' => $this->t('This is used as the add more items input label for this webform element when displaying multiple values.'),
      '#states' => [
        'visible' => [
          ':input[name="properties[multiple__add_more_input]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['multiple']['multiple__add_more_container']['multiple__add_more_items'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of add more items'),
      '#required' => TRUE,
      '#min' => 1,
      '#max' => 20,
    ];

    /* Wrapper attributes */

    $form['wrapper_attributes'] = [
      '#type' => 'details',
      '#title' => ($this->hasProperty('wrapper_type')) ?
        $this->t('Wrapper type and attributes') :
        $this->t('Wrapper attributes'),
    ];
    $form['wrapper_attributes']['wrapper_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Wrapper type'),
      '#options' => [
        'fieldset' => $this->t('Fieldset'),
        'form_element' => $this->t('Form element'),
        'container' => $this->t('Container'),
      ],
      '#description' => '<b>' . t('Fieldset') . ':</b> ' . t('Wraps inputs in a fieldset.') . ' <strong>' . t('Recommended') . '</strong>' .
        '<br/><br/><b>' . t('Form element') . ':</b> ' . t('Wraps inputs in a basic form element with title and description.') .
        '<br/><br/><b>' . t('Container') . ':</b> ' . t('Wraps inputs in a basic div with no title or description.'),
    ];
    // Hide element description and display when using a container wrapper.
    if ($this->hasProperty('wrapper_type')) {
      $form['element_description']['#states'] = [
        '!visible' => [
          ':input[name="properties[wrapper_type]"]' => ['value' => 'container'],
        ],
      ];
      $form['form']['display_container']['#states'] = [
        '!visible' => [
          ':input[name="properties[wrapper_type]"]' => ['value' => 'container'],
        ],
      ];
      $form['form']['field_container']['#states'] = [
        '!visible' => [
          ':input[name="properties[wrapper_type]"]' => ['value' => 'container'],
        ],
      ];
    }

    $form['wrapper_attributes']['wrapper_attributes'] = [
      '#type' => 'webform_element_attributes',
      '#title' => $this->t('Wrapper'),
      '#class__description' => $this->t("Apply classes to the element's wrapper around both the field and its label. Select 'custom…' to enter custom classes."),
      '#style__description' => $this->t("Apply custom styles to the element's wrapper around both the field and its label."),
      '#attributes__description' => $this->t("Enter additional attributes to be added to the element's wrapper."),
      '#classes' => $this->configFactory->get('webform.settings')->get('element.wrapper_classes'),
    ];

    /* Element attributes */

    $form['element_attributes'] = [
      '#type' => 'details',
      '#title' => $this->t('Element attributes'),
    ];
    $form['element_attributes']['attributes'] = [
      '#type' => 'webform_element_attributes',
      '#title' => $this->t('Element'),
      '#classes' => $this->configFactory->get('webform.settings')->get('element.classes'),
    ];

    /* Label attributes */

    $form['label_attributes'] = [
      '#type' => 'details',
      '#title' => $this->t('Label attributes'),
    ];
    $form['label_attributes']['label_attributes'] = [
      '#type' => 'webform_element_attributes',
      '#title' => $this->t('Label'),
      '#class__description' => $this->t("Apply classes to the element's label."),
      '#style__description' => $this->t("Apply custom styles to the element's label."),
      '#attributes__description' => $this->t("Enter additional attributes to be added to the element's label."),
    ];
    // Only display label attribute when the wrapper type is a form element.
    if ($this->hasProperty('wrapper_type')) {
      $form['label_attributes']['#states'] = [
        'visible' => [
          ':input[name="properties[wrapper_type]"]' => ['value' => 'form_element'],
        ],
      ];
    }

    /* Summary attributes */

    $form['summary_attributes'] = [
      '#type' => 'details',
      '#title' => $this->t('Summary attributes'),
    ];
    $form['summary_attributes']['summary_attributes'] = [
      '#type' => 'webform_element_attributes',
      '#title' => $this->t('Summary'),
      '#class__description' => $this->t("Apply classes to the details' summary around both the field and its label."),
      '#style__description' => $this->t("Apply custom styles to the details' summary."),
      '#attributes__description' => $this->t("Enter additional attributes to be added to the details' summary."),
    ];

    /* Submission display */
    $has_edit_twig_access = WebformTwigExtension::hasEditTwigAccess();

    $form['display'] = [
      '#type' => 'details',
      '#title' => $this->t('Submission display'),
    ];
    // Item.
    $form['display']['item'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Single item'),
    ];
    $form['display']['item']['format'] = [
      '#type' => 'select',
      '#title' => $this->t('Item format'),
      '#description' => $this->t('Select how a single value is displayed.'),
      '#options' => WebformOptionsHelper::appendValueToText($this->getItemFormats()),
    ];
    $format = isset($element_properties['format']) ? $element_properties['format'] : NULL;
    $format_custom = ($has_edit_twig_access || $format === 'custom');
    if ($format_custom) {
      $form['display']['item']['format']['#options'] += ['custom' => $this->t('Custom…')];
    }
    $format_custom_states = [
      'visible' => [':input[name="properties[format]"]' => ['value' => 'custom']],
      'required' => [':input[name="properties[format]"]' => ['value' => 'custom']],
    ];
    $form['display']['item']['format_html'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'twig',
      '#title' => $this->t('Item format custom HTML'),
      '#description' => $this->t('The HTML to display for a single element value. You may include HTML or <a href=":href">Twig</a>. You may enter data from the submission as per the "variables" below.', [':href' => 'http://twig.sensiolabs.org/documentation']),
      '#states' => $format_custom_states,
      '#access' => $format_custom,
    ];
    $form['display']['item']['format_text'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'twig',
      '#title' => $this->t('Item format custom Text'),
      '#description' => $this->t('The text to display for a single element value. You may include <a href=":href">Twig</a>. You may enter data from the submission as per the "variables" below.', [':href' => 'http://twig.sensiolabs.org/documentation']),
      '#states' => $format_custom_states,
      '#access' => $format_custom,
    ];
    if ($has_edit_twig_access) {
      // Containers use the 'children' variable and inputs use the
      // 'value' variable.
      $twig_variables = ($this instanceof ContainerBase) ? ['children' => '{{ children }}'] : ['value' => '{{ value }}'];

      // Composite Twig variables.
      if ($this instanceof WebformCompositeBase) {
        // Add composite elements to items.
        $composite_elements = $this->getCompositeElements();
        foreach ($composite_elements as $composite_key => $composite_element) {
          $twig_variables["element.$composite_key"] = "{{ element.$composite_key }}";
        }
      }

      $formats = $this->getItemFormats();
      foreach ($formats as $format_name => $format) {
        if (is_array($format)) {
          foreach ($format as $sub_format_name => $sub_format) {
            $twig_variables["item['$sub_format_name']"] = "{{ item['$sub_format_name'] }}";
          }
        }
        else {
          $twig_variables["item.$format_name"] = "{{ item.$format_name }}";
        }
      }
      $form['display']['item']['twig'] = WebformTwigExtension::buildTwigHelp($twig_variables);
      $form['display']['item']['twig']['#states'] = $format_custom_states;
      WebformElementHelper::setPropertyRecursive($form['display']['item']['twig'], '#access', TRUE);
    }

    // Items.
    $form['display']['items'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Multiple items'),
      '#states' => [
        'visible' => [
          [':input[name="properties[multiple][container][cardinality]"]' => ['value' => '-1']],
          'or',
          [':input[name="properties[multiple][container][cardinality_number]"]' => ['!value' => 1]],
        ],
      ],
    ];
    $form['display']['items']['format_items'] = [
      '#type' => 'select',
      '#title' => $this->t('Items format'),
      '#description' => $this->t('Select how multiple values are displayed.'),
      '#options' => WebformOptionsHelper::appendValueToText($this->getItemsFormats()),
    ];
    $format_items = isset($element_properties['format_items']) ? $element_properties['format_items'] : NULL;
    $format_items_custom = ($has_edit_twig_access || $format_items === 'custom');
    if ($format_items_custom) {
      $form['display']['items']['format_items']['#options'] += ['custom' => $this->t('Custom…')];
    }
    $format_items_custom_states = [
      'visible' => [':input[name="properties[format_items]"]' => ['value' => 'custom']],
      'required' => [':input[name="properties[format_items]"]' => ['value' => 'custom']],
    ];
    $form['display']['items']['format_items_html'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'twig',
      '#title' => $this->t('Items format custom HTML'),
      '#description' => $this->t('The HTML to display for multiple element values. You may include HTML or <a href=":href">Twig</a>. You may enter data from the submission as per the "variables" below.', [':href' => 'http://twig.sensiolabs.org/documentation']),
      '#states' => $format_items_custom_states,
      '#access' => $format_items_custom,
    ];
    $form['display']['items']['format_items_text'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'twig',
      '#title' => $this->t('Items format custom Text'),
      '#description' => $this->t('The text to display for multiple element values. You may include <a href=":href">Twig</a>. You may enter data from the submission as per the "variables" below.', [':href' => 'http://twig.sensiolabs.org/documentation']),
      '#states' => $format_items_custom_states,
      '#access' => $format_items_custom,
    ];
    if ($format_items_custom) {
      $twig_variables = [
        '{{ value }}',
        '{{ items }}',
      ];
      $form['display']['items']['twig'] = WebformTwigExtension::buildTwigHelp($twig_variables);
      $form['display']['items']['twig']['#states'] = $format_items_custom_states;
      WebformElementHelper::setPropertyRecursive($form['display']['items']['twig'], '#access', TRUE);
    }

    $form['display']['format_attributes'] = [
      '#type' => 'details',
      '#title' => $this->t('Display wrapper attributes'),
    ];
    $form['display']['format_attributes']['format_attributes'] = [
      '#type' => 'webform_element_attributes',
      '#title' => $this->t('Display'),
      '#class__description' => $this->t("Apply classes to the element's display wrapper. Select 'custom…' to enter custom classes."),
      '#style__description' => $this->t("Apply custom styles to the element's display wrapper."),
      '#attributes__description' => $this->t("Enter additional attributes to be added to the element's display wrapper."),
      '#classes' => $this->configFactory->get('webform.settings')->get('element.wrapper_classes'),
    ];

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
      '#description' => $this->t('The admin title will always be displayed when managing elements and viewing & downloading submissions.') .
        '<br/>' .
        $this->t("If an element's title is hidden, the element's admin title will be displayed when viewing a submission."),
    ];

    /**************************************************************************/
    // Access.
    /**************************************************************************/

    /* Access */

    $operations = [
      'create' => [
        '#title' => $this->t('Create submission'),
        '#description' => $this->t('Select roles and users that should be able to populate this element when creating a new submission.'),
        '#open' => TRUE,
      ],
      'update' => [
        '#title' => $this->t('Update submission'),
        '#description' => $this->t('Select roles and users that should be able to update this element when updating an existing submission.'),
        '#open' => FALSE,
      ],
      'view' => [
        '#title' => $this->t('View submission'),
        '#description' => $this->t('Select roles and users that should be able to view this element when viewing a submission.'),
        '#open' => FALSE,
      ],
    ];

    $form['access'] = [
      '#type' => 'container',
    ];
    if (!$this->currentUser->hasPermission('administer webform') && !$this->currentUser->hasPermission('administer webform element access')) {
      $form['access']['#access'] = FALSE;
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
      $form['access']['access_' . $operation]['access_' . $operation . '_permissions'] = [
        '#type' => 'webform_permissions',
        '#title' => $this->t('Permissions'),
        '#multiple' => TRUE,
        '#select2' => TRUE,
      ];
    }

    /**************************************************************************/

    // Disable #multiple if the element has submission data.
    if (!$form_object->isNew() && $this->hasProperty('multiple')) {
      $element_key = $form_object->getKey();
      if ($this->submissionStorage->hasSubmissionValue($webform, $element_key)) {
        $form['element']['multiple']['#disabled'] = TRUE;
        $form['element']['multiple']['#description'] = '<em>' . $this->t('There is data for this element in the database. This setting can no longer be changed.') . '</em>';
      }
    }

    // Add warning to all password elements that are stored in the database.
    if (strpos($this->pluginId, 'password') !== FALSE && !$webform->getSetting('results_disabled')) {
      $form['element']['password_message'] = [
        '#type' => 'webform_message',
        '#message_type' => 'warning',
        '#message_message' => $this->t('Webform submissions store passwords as plain text.') . ' ' .
          $this->t('<a href=":href">Encryption</a> should be enabled for this element.', [':href' => 'https://www.drupal.org/project/webform_encrypt']),
        '#access' => TRUE,
        '#weight' => -100,
        '#message_close' => TRUE,
        '#message_storage' => WebformMessage::STORAGE_SESSION,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $default_properties = $this->getDefaultProperties();
    $element_properties = WebformArrayHelper::removePrefix($this->configuration)
      + $default_properties;

    // Make sure 'format_items' is removed if the element does not
    // support multiple values.
    // @todo Webform 8.x-6.x: Remove and assume custom element are fixed.
    if (!$this->supportsMultipleValues()) {
      unset(
        $default_properties['format_items'],
        $default_properties['format_items_html'],
        $default_properties['format_items_text']
      );
    }

    // Set default and element properties.
    // Note: Storing this information in the webform's state allows modules to view
    // and alter this information using webform alteration hooks.
    $form_state->set('default_properties', $default_properties);
    $form_state->set('element_properties', $element_properties);

    $form = $this->form($form, $form_state);
    \Drupal::moduleHandler()->alter('webform_element_configuration_form', $form, $form_state);

    // Get default and element properties which can be altered by WebformElementHandlers.
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

    // Allow custom properties (i.e. #attributes) to be added to the element.
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
      '#description' => $this->t('Properties do not have to be prepended with a hash (#) character, the hash character will be automatically added to the custom properties.') .
        '<br /><br />' .
        $this->t('These properties and callbacks are not allowed: @properties', ['@properties' => WebformArrayHelper::toString(WebformArrayHelper::addPrefix(WebformElementHelper::$ignoredProperties))]),
      '#default_value' => $custom_properties ,
      '#parents' => ['properties', 'custom'],
    ];

    $this->tokenManager->elementValidate($form);

    // Set custom properties.
    // Note: Storing this information in the webform's state allows modules to
    // view and alter this information using webform alteration hooks.
    $form_state->set('custom_properties', $custom_properties);

    return $this->buildConfigurationFormTabs($form, $form_state);
  }

  /**
   * Get form--inline container which is used for side-by-side element layout.
   *
   * @return array
   *   A container element with .form--inline class if inline help text is
   *   enabled.
   */
  protected function getFormInlineContainer() {
    $help_enabled = $this->configFactory->get('webform.settings')->get('ui.description_help');
    return [
      '#type' => 'container',
      '#attributes' => ($help_enabled) ? ['class' => ['form--inline', 'clearfix', 'webform-ui-element-form-inline--input']] : [],
    ];
  }

  /**
   * Build configuration form tabs.
   *
   * @param array $form
   *   An associative array containing the initial structure of the plugin form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The plugin form with tabs.
   */
  protected function buildConfigurationFormTabs(array $form, FormStateInterface $form_state) {
    $tabs = [
      'conditions' => [
        'title' => $this->t('Conditions'),
        'elements' => [
          'conditional_logic',
        ],
        'weight' => 10,
      ],
      'advanced' => [
        'title' => $this->t('Advanced'),
        'elements' => [
          'default',
          'multiple',
          'wrapper_attributes',
          'element_attributes',
          'label_attributes',
          'summary_attributes',
          'display',
          'admin',
          'options_properties',
          'custom',
        ],
        'weight' => 20,
      ],
      'access' => [
        'title' => $this->t('Access'),
        'elements' => [
          'access',
        ],
        'weight' => 30,
      ],
    ];
    return WebformFormHelper::buildTabs($form, $tabs, $form_state->get('active_tab'));
  }

  /**
   * Set configuration webform default values recursively.
   *
   * @param array $form
   *   A webform render array.
   * @param array $element_properties
   *   The element's properties without hash prefix. Any property that is found
   *   in the webform will be populated and unset from
   *   $element_properties array.
   *
   * @return bool
   *   TRUE is the webform has any inputs.
   */
  protected function setConfigurationFormDefaultValueRecursive(array &$form, array &$element_properties) {
    $has_input = FALSE;

    foreach ($form as $property_name => &$property_element) {
      // Skip all properties.
      if (is_string($property_name) && Element::property($property_name)) {
        continue;
      }

      // Skip Entity reference element 'selection_settings'.
      // @see \Drupal\webform\Plugin\WebformElement\WebformEntityReferenceTrait::form
      // @todo Fix entity reference Ajax and move code WebformEntityReferenceTrait.
      if (!empty($property_element['#tree']) && $property_name == 'selection_settings') {
        unset($element_properties[$property_name]);
        $property_element['#parents'] = ['properties', $property_name];
        $has_input = TRUE;
        continue;
      }

      // Determine if the property element is an input using the webform element
      // manager.
      // Note: #access is used to protect inputs and containers that should
      // always be visible.
      $is_input = $this->elementManager->getElementInstance($property_element)->isInput($property_element);
      if ($is_input) {
        if (array_key_exists($property_name, $element_properties)) {
          // If this property exists, then set its default value.
          $this->setConfigurationFormDefaultValue($form, $element_properties, $property_element, $property_name);
          $has_input = TRUE;
        }
        elseif (empty($form[$property_name]['#access'])) {
          // Else completely remove the property element from the webform.
          unset($form[$property_name]);
        }
      }
      else {
        // Recurse down this container and see if it's children have inputs.
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
        // "An illegal choice has been detected…" error.
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
        elseif (is_bool($default_value) && $property_name == 'default_value') {
          $property_element['#default_value'] = $default_value ? 1 : 0;
        }
        elseif (is_null($default_value) && $property_name == 'default_value') {
          $property_element['#default_value'] = (string) $default_value;
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

    $ignored_properties = WebformElementHelper::getIgnoredProperties($properties);
    foreach ($ignored_properties as $ignored_property => $ignored_message) {
      // Display custom messages.
      if ($ignored_property != $ignored_message) {
        unset($ignored_properties[$ignored_property]);
        $form_state->setErrorByName('custom', $ignored_message);
      }
    }

    // Display ignored properties message.
    if ($ignored_properties) {
      $t_args = [
        '@properties' => WebformArrayHelper::toString($ignored_properties),
      ];
      $form_state->setErrorByName('custom', $this->t('Element contains ignored/unsupported properties: @properties', $t_args));
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
      if (!array_key_exists($property_name, $default_properties)) {
        continue;
      }

      $this->getConfigurationFormProperty($element_properties, $property_name, $property_value, $element);

      // Unset element property that matched the default property.
      switch ($property_name) {
        case 'multiple':
          // The #multiple property element is converted to the correct datatype
          // so we are looking for 'strict equality' (===).
          // This prevents #multiple: 2 from being interpeted as TRUE.
          // @see \Drupal\webform\Element\WebformElementMultiple::validateWebformElementMultiple
          // @see \Drupal\webform\Plugin\WebformElement\Checkboxes::defaultProperties
          if ($default_properties[$property_name] === $element_properties[$property_name]) {
            unset($element_properties[$property_name]);
          }
          break;

        default:
          // Most elements properties are strings or numbers and we need to use
          // 'type-converting equality' (==) because all numbers are posted
          // back to the server as strings.
          if ($default_properties[$property_name] == $element_properties[$property_name]) {
            unset($element_properties[$property_name]);
          }

          // Cast data types (except #multiple).
          if (isset($element_properties[$property_name])) {
            if (is_bool($default_properties[$property_name])) {
              $element_properties[$property_name] = (bool) $element_properties[$property_name];
            }
            elseif (is_null($default_properties[$property_name]) || is_numeric($default_properties[$property_name])) {
              $value = $element_properties[$property_name];
              $cast_value = ($value == (int) $value) ? (int) $value : (float) $value;
              if ($value == $cast_value) {
                $element_properties[$property_name] = $cast_value;
              }
            }
          }
          break;
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

  /**
   * Determine if the element has a composite field wrapper.
   *
   * @return bool
   *   TRUE if the element has a composite field wrapper.
   */
  protected function hasCompositeFormElementWrapper() {
    $callbacks = $this->elementInfo->getInfoProperty($this->getPluginId(), '#pre_render') ?: [];
    foreach ($callbacks as $callback) {
      if (is_array($callback)
        && in_array($callback[1], ['preRenderCompositeFormElement', 'preRenderWebformCompositeFormElement'])) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
