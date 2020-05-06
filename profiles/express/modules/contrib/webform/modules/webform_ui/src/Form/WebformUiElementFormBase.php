<?php

namespace Drupal\webform_ui\Form;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\webform\Element\WebformMessage;
use Drupal\webform\Plugin\WebformElement\WebformTable;
use Drupal\webform\Plugin\WebformElement\WebformTableRow;
use Drupal\webform\Plugin\WebformElementVariantInterface;
use Drupal\webform\Utility\WebformDialogHelper;
use Drupal\webform\Form\WebformDialogFormTrait;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Drupal\webform\Utility\WebformYaml;
use Drupal\webform\WebformEntityElementsValidatorInterface;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformTokenManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base class for webform element webforms.
 *
 * The basic workflow for handling webform elements.
 *
 * - Read the element.
 * - Build element's properties webform.
 * - Set the property values.
 * - Alter the element's properties webform.
 * - Process the element's properties webform.
 * - Validate the element's properties webform.
 * - Submit the element's properties webform.
 * - Get property values from the webform state's values.
 * - Remove default properties from the element's properties.
 * - Update element properties.
 */
abstract class WebformUiElementFormBase extends FormBase implements WebformUiElementFormInterface {

  use WebformDialogFormTrait;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Webform element manager.
   *
   * @var \Drupal\webform\Plugin\WebformElementManagerInterface
   */
  protected $elementManager;

  /**
   * Webform element validator.
   *
   * @var \Drupal\webform\WebformEntityElementsValidatorInterface
   */
  protected $elementsValidator;

  /**
   * The token manager.
   *
   * @var \Drupal\webform\WebformTokenManagerInterface
   */
  protected $tokenManager;

  /**
   * The webform.
   *
   * @var \Drupal\webform\WebformInterface
   */
  protected $webform;

  /**
   * The webform element.
   *
   * @var array
   */
  protected $element = [];

  /**
   * The webform element key.
   *
   * @var string
   */
  protected $key;

  /**
   * The webform element parent key.
   *
   * @var string
   */
  protected $parentKey;

  /**
   * The webform element's original element type.
   *
   * @var string
   */
  protected $originalType;

  /**
   * The operation of the current webform.
   *
   * @var string
   */
  protected $operation;

  /**
   * The action of the current webform.
   *
   * @var string
   */
  protected $action;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_ui_element_form';
  }

  /**
   * Constructs a WebformUiElementFormBase.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager
   *   The webform element manager.
   * @param \Drupal\webform\WebformEntityElementsValidatorInterface $elements_validator
   *   Webform element validator.
   * @param \Drupal\webform\WebformTokenManagerInterface $token_manager
   *   The webform token manager.
   */
  public function __construct(RendererInterface $renderer, EntityFieldManagerInterface $entity_field_manager, WebformElementManagerInterface $element_manager, WebformEntityElementsValidatorInterface $elements_validator, WebformTokenManagerInterface $token_manager) {
    $this->renderer = $renderer;
    $this->entityFieldManager = $entity_field_manager;
    $this->elementManager = $element_manager;
    $this->elementsValidator = $elements_validator;
    $this->tokenManager = $token_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer'),
      $container->get('entity_field.manager'),
      $container->get('plugin.manager.webform.element'),
      $container->get('webform.elements_validator'),
      $container->get('webform.token_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, WebformInterface $webform = NULL, $key = NULL, $parent_key = NULL, $type = NULL) {
    // Override an element's default value using the $form_state.
    if ($form_state->get('default_value')) {
      $this->element['#default_value'] = $form_state->get('default_value');
    }

    $this->webform = $webform;
    $this->key = $key;
    $this->parentKey = $parent_key;

    $element_plugin = $this->getWebformElementPlugin();

    $form['#parents'] = [];
    $form['properties'] = ['#parents' => ['properties']];
    $subform_state = SubformState::createForSubform($form['properties'], $form, $form_state);
    $form['properties'] = $element_plugin->buildConfigurationForm($form['properties'], $subform_state);

    // Move messages to the top of the webform.
    if (isset($form['properties']['messages'])) {
      $form['messages'] = $form['properties']['messages'];
      $form['messages']['#weight'] = -100;
      unset($form['properties']['messages']);
    }

    // Set parent key.
    $form['parent_key'] = [
      '#type' => 'value',
      '#value' => $parent_key,
    ];

    // Set element type.
    $form['properties']['element']['type'] = [
      '#type' => 'item',
      '#title' => $this->t('Type'),
      'label' => [
        '#markup' => $element_plugin->getPluginLabel(),
      ],
      '#weight' => -100,
      '#parents' => ['type'],
    ];

    // Set change element type.
    if ($key && $element_plugin->getRelatedTypes($this->element)) {
      $route_parameters = ['webform' => $webform->id(), 'key' => $key];
      if ($this->originalType) {
        $original_webform_element = $this->elementManager->createInstance($this->originalType);
        $route_parameters = ['webform' => $webform->id(), 'key' => $key];
        $form['properties']['element']['type']['cancel'] = [
          '#type' => 'link',
          '#title' => $this->t('Cancel'),
          '#url' => new Url('entity.webform_ui.element.edit_form', $route_parameters),
          '#attributes' => WebformDialogHelper::getOffCanvasDialogAttributes(WebformDialogHelper::DIALOG_NORMAL, ['button', 'button--small']),
          '#prefix' => ' ',
        ];
        $form['properties']['element']['type']['#description'] = '(' . $this->t('Changing from %type', ['%type' => $original_webform_element->getPluginLabel()]) . ')';
      }
      else {
        $form['properties']['element']['type']['change_type'] = [
          '#type' => 'link',
          '#title' => $this->t('Change'),
          '#url' => new Url('entity.webform_ui.change_element', $route_parameters),
          '#attributes' => WebformDialogHelper::getModalDialogAttributes(WebformDialogHelper::DIALOG_NORMAL, ['button', 'button--small']),
          '#prefix' => ' ',
        ];
      }
    }

    // Set element key reserved word warning message.
    // @see Drupal.behaviors.webformUiElementKey
    if (!$key) {
      $reserved_keys = ['form_build_id', 'form_token', 'form_id', 'data', 'op', 'destination'];
      $reserved_keys = array_merge($reserved_keys, array_keys($this->entityFieldManager->getBaseFieldDefinitions('webform_submission')));
      $form['#attached']['drupalSettings']['webform_ui']['reserved_keys'] = $reserved_keys;
      $form['properties']['element']['key_warning'] = [
        '#type' => 'webform_message',
        '#message_type' => 'warning',
        '#message_message' => [
          '#markup' => $this->t("Please avoid using the reserved word '@key' as the element's key."),
          '#prefix' => '<div id="webform-ui-reserved-key-warning">',
          '#suffix' => '</div>',
        ],
        '#weight' => -99,
        '#attributes' => ['style' => 'display:none'],
      ];
    }

    // Set element key with custom machine name pattern.
    // @see \Drupal\webform\WebformEntityElementsValidator::validateNames
    $machine_name_pattern = $this->config('webform.settings')->get('element.machine_name_pattern') ?: 'a-z0-9_';
    switch ($machine_name_pattern) {
      case 'a-z0-9_':
        $machine_name_requirements = $this->t('lowercase letters, numbers, and underscores');
        break;

      case 'a-zA-Z0-9_':
        $machine_name_requirements = $this->t('letters, numbers, and underscores');
        break;

      case 'a-z0-9_-':
        $machine_name_requirements = $this->t('lowercase letters, numbers, and underscores');
        break;

      case 'a-zA-Z0-9_-':
        $machine_name_requirements = $this->t('letters, numbers, underscores, and dashes');
        break;
    }
    $t_args = ['@requirements' => $machine_name_requirements];

    $form['properties']['element']['key'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Key'),
      '#description' => $this->t('A unique element key. Can only contain @requirements.', $t_args),
      '#machine_name' => [
        'label' => '<br/>' . $this->t('Key'),
        'exists' => [$this, 'exists'],
        'source' => ['title'],
        'replace_pattern' => '[^' . $machine_name_pattern . ']+',
        'error' => $this->t('The element key name must contain only @requirements.', $t_args),
      ],
      '#required' => TRUE,
      '#parents' => ['key'],
      '#disabled' => ($key) ? TRUE : FALSE,
      '#default_value' => $key ?: $this->getDefaultKey(),
      '#weight' => -97,
    ];

    // Remove the key's help text (aka description) once it has been set.
    if ($key) {
      $form['properties']['element']['key']['#description'] = NULL;
    }
    // Use title for key (machine_name).
    if (isset($form['properties']['element']['title'])) {
      $form['properties']['element']['key']['#machine_name']['source'] = ['properties', 'element', 'title'];
      $form['properties']['element']['title']['#id'] = 'title';
    }

    // Prefix table row child elements with the table row key.
    if ($this->isNew()
      && $parent_prefix = $this->getParentKeyPrefix($parent_key)) {
      $form['properties']['element']['key']['#field_prefix'] = $parent_prefix . '_';
      $form['properties']['element']['table_message'] = [
        '#type' => 'webform_message',
        '#message_message' => $this->t("Element keys are automatically prefixed with parent row's key."),
        '#message_type' => 'warning',
        '#message_close' => TRUE,
        '#message_storage' => WebformMessage::STORAGE_SESSION,
        '#weight' => -98,
      ];
    }

    // Set flex.
    // Hide #flex property if parent element is not a 'webform_flexbox'.
    if (isset($form['properties']['flex']) && !$this->isParentElementFlexbox($key, $parent_key)) {
      $form['properties']['flex']['#access'] = FALSE;
    }

    $form['#attached']['library'][] = 'webform_ui/webform_ui';

    // Set actions.
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
      '#_validate_form' => TRUE,
    ];
    if ($this->operation === 'create'
      && $this->isAjax()
      && !$element_plugin instanceof WebformTable
      && !$element_plugin instanceof WebformTableRow) {
      $form['actions']['save_add_element'] = [
        '#type' => 'submit',
        '#value' => $this->t('Save + Add element'),
        '#_validate_form' => TRUE,
      ];
    }

    // Add token links below the form and on every tab.
    $form['token_tree_link'] = $this->tokenManager->buildTreeElement();
    if ($form['token_tree_link']) {
      $form['token_tree_link'] += [
        '#weight' => 101,
      ];
    }

    $form = $this->buildDefaultValueForm($form, $form_state);

    return $this->buildDialogForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Only validate the submit button.
    $button = $form_state->getTriggeringElement();
    if (empty($button['#_validate_form'])) {
      return;
    }

    // Subform state used for validation and getting the element's properties.
    $subform_state = SubformState::createForSubform($form['properties'], $form, $form_state);

    // Validate configuration form.
    $element_plugin = $this->getWebformElementPlugin();
    $element_plugin->validateConfigurationForm($form, $subform_state);

    // Get element validation errors.
    $element_errors = $subform_state->getErrors();
    foreach ($element_errors as $element_error) {
      $form_state->setErrorByName(NULL, $element_error);
    }

    // Stop validation if the element's properties has any errors.
    if ($subform_state->hasAnyErrors()) {
      return;
    }

    $parent_key = $form_state->getValue('parent_key');
    $key = $form_state->getValue('key');

    // Prefix table row child elements with the table row key.
    if ($this->isNew()
      && $parent_prefix = $this->getParentKeyPrefix($parent_key)) {
      $key = $parent_prefix . '_' . $key;
      $form_state->setValue('key', $key);
    }

    // Update key for new and duplicated elements.
    $this->key = $key;

    // Clone webform and add/update the element.
    $webform = clone $this->webform;
    $properties = $element_plugin->getConfigurationFormProperties($form, $subform_state);
    $webform->setElementProperties($key, $properties, $parent_key);

    // Validate elements.
    if ($messages = $this->elementsValidator->validate($webform)) {
      $t_args = [':href' => Url::fromRoute('entity.webform.source_form', ['webform' => $webform->id()])->toString()];
      $form_state->setErrorByName('elements', $this->t('There has been error validating the elements. You may need to edit the <a href=":href">YAML source</a> to resolve the issue.', $t_args));
      foreach ($messages as $message) {
        $this->messenger()->addError($message);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $op = $form_state->getValue('op');
    $parent_key = $form_state->getValue('parent_key');
    $key = $form_state->getValue('key');

    $element_plugin = $this->getWebformElementPlugin();

    // Submit element configuration.
    // Generally, elements will not be processing any submitted properties.
    // It is possible that a custom element might need to call a third-party API
    // to 'register' the element.
    $subform_state = SubformState::createForSubform($form['properties'], $form, $form_state);
    $element_plugin->submitConfigurationForm($form, $subform_state);

    // Add/update the element to the webform.
    $properties = $element_plugin->getConfigurationFormProperties($form, $subform_state);
    $this->webform->setElementProperties($key, $properties, $parent_key);

    // Save the webform.
    $this->webform->save();

    // Display status message.
    $properties = $form_state->getValue('properties');
    $t_args = [
      '%title' => (!empty($properties['title'])) ? $properties['title'] : $key,
      '@action' => $this->action,
    ];
    $this->messenger()->addStatus($this->t('%title has been @action.', $t_args));

    // Determine add element parent key.
    $save_and_add_element = ($op == (string) $this->t('Save + Add element')) ? TRUE : FALSE;
    $add_element = ($element_plugin->isContainer($this->getElement())) ? $key : $parent_key;
    $add_element = $add_element ? Html::getClass($add_element) : '_root_';

    // Append ?update= to (redirect) destination.
    if ($this->requestStack->getCurrentRequest()->query->get('destination')) {
      $redirect_destination = $this->getRedirectDestination();
      $destination = $redirect_destination->get();
      $destination .= (strpos($destination, '?') !== FALSE ? '&' : '?') . 'update=' . $key;
      $destination .= ($save_and_add_element) ? '&add_element=' . $add_element : '';
      $redirect_destination->set($destination);
    }

    // Still set the redirect URL just to be safe.
    // Variants require the entire page to be reloaded so that Variants tab
    // is made visible,
    if ($this->getWebformElementPlugin() instanceof WebformElementVariantInterface) {
      $query = ['reload' => 'true'];
    }
    else {
      $query = ['update' => $key];
      if ($save_and_add_element) {
        $query['add_element'] = $add_element;
      }
    }
    $form_state->setRedirectUrl($this->webform->toUrl('edit-form', ['query' => $query]));
  }

  /**
   * {@inheritdoc}
   */
  public function isNew() {
    return ($this instanceof WebformUiElementAddForm) ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getWebform() {
    return $this->webform;
  }

  /**
   * {@inheritdoc}
   */
  public function getElement() {
    return $this->element;
  }

  /**
   * {@inheritdoc}
   */
  public function getKey() {
    return $this->key;
  }

  /**
   * {@inheritdoc}
   */
  public function getParentKey() {
    return $this->parentKey;
  }

  /**
   * {@inheritdoc}
   */
  public function getWebformElementPlugin() {
    return $this->elementManager->getElementInstance($this->element, $this->getWebform());
  }

  /**
   * Determine if the parent element is a 'webform_flexbox'.
   *
   * @param string|null $key
   *   The element's key. Only applicable for existing elements.
   * @param string|null $parent_key
   *   The element's parent key. Only applicable for new elements.
   *   Parent key is set via query string parameter. (?parent={parent_key})
   *
   * @return bool
   *   TRUE if the parent element is a 'webform_flexbox'.
   */
  protected function isParentElementFlexbox($key = NULL, $parent_key = NULL) {
    $elements = $this->webform->getElementsInitializedAndFlattened();

    // Check the element #webform_parent_flexbox property.
    if ($key && isset($elements[$key])) {
      return $elements[$key]['#webform_parent_flexbox'];
    }

    // Check the parent element #type.
    if ($parent_key && isset($elements[$parent_key]) && isset($elements[$parent_key]['#type'])) {
      return ($elements[$parent_key]['#type'] == 'webform_flexbox') ? TRUE : FALSE;
    }

    return FALSE;
  }

  /**
   * Determine if parent key prefixing is enabled.
   *
   * @param string|null $parent_key
   *   The element's parent key.
   *
   * @return bool
   *   TRUE if parent key prefixing is enabled.
   */
  protected function isParentKeyPrefixEnabled($parent_key) {
    while ($parent_key) {
      $parent_element = $this->getWebform()->getElement($parent_key);
      if ($parent_element['#type'] === 'webform_table') {
        return (!isset($parent_element['#prefix_children']) || $parent_element['#prefix_children'] === TRUE);
      }
      $parent_key = $parent_element['#webform_parent_key'];
    }
    return FALSE;
  }

  /**
   * Get the parent key prefix.
   *
   * Parent key prefix only applies to elements withing a
   * 'webform_table_row'.
   *
   * @param string|null $parent_key
   *   The element's parent key.
   *
   * @return string|null
   *   The parent key prefix or NULL is no parent key prefix is applicable.
   */
  protected function getParentKeyPrefix($parent_key) {
    if (!$this->isParentKeyPrefixEnabled($parent_key)) {
      return NULL;
    }

    while ($parent_key) {
      $parent_element = $this->getWebform()->getElement($parent_key);
      if (strpos($parent_key, '01') !== FALSE
        && $parent_element['#type'] === 'webform_table_row') {
        return $parent_element['#webform_key'];
      }
      $parent_key = $parent_element['#webform_parent_key'];
    }
    return NULL;
  }

  /****************************************************************************/
  // Element key handling.
  /****************************************************************************/

  /**
   * Determines if the webform element key already exists.
   *
   * @param string $key
   *   The webform element key.
   *
   * @return bool
   *   TRUE if the webform element key, FALSE otherwise.
   */
  public function exists($key) {
    $elements = $this->webform->getElementsInitializedAndFlattened();
    return (isset($elements[$key])) ? TRUE : FALSE;
  }

  /**
   * Get the default key for the current element.
   *
   * Default key will be auto incremented when there are duplicate keys.
   *
   * @return null|string
   *   An element's default key which will be incremented to prevent duplicate
   *   keys.
   */
  public function getDefaultKey() {
    $element_plugin = $this->getWebformElementPlugin();
    if (empty($element_plugin->getDefaultKey())) {
      return NULL;
    }

    $base_key = $element_plugin->getDefaultKey();
    $elements = $this->getWebform()->getElementsDecodedAndFlattened();

    if (preg_match('/(^|_)(\d+$)($|_)/', $base_key) && !isset($elements[$base_key])) {
      return $base_key;
    }

    $increment = NULL;
    foreach ($elements as $element_key => $element) {
      if (strpos($element_key, $base_key) === 0) {
        if (preg_match('/^' . $base_key . '_(\d+)$/', $element_key, $match)) {
          $element_increment = intval($match[1]);
          if ($element_increment > $increment) {
            $increment = $element_increment;
          }
        }
        elseif ($increment === NULL) {
          $increment = 0;
        }
      }
    }

    if ($increment === NULL) {
      return $base_key;
    }
    else {
      return $base_key . '_' . str_pad(($increment + 1), 2, '0', STR_PAD_LEFT);
    }
  }

  /****************************************************************************/
  // Default value handling.
  /****************************************************************************/

  /**
   * Build update default value form elements.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form.
   */
  public function buildDefaultValueForm(array &$form, FormStateInterface $form_state) {
    if (!isset($form['properties']['default']['default_value'])) {
      return $form;
    }

    if ($element = $form_state->get('default_value_element')) {
      // Display the default value element.
      $element['#webform_key'] = $this->getWebform()->id();

      // Initialize the element.
      $this->elementManager->initializeElement($element);

      // Build the element.
      $this->elementManager->buildElement($element, $form, $form_state);

      $form['default'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Default value'),
      ];
      $form['default']['default_value'] = $element;

      // Hide properties using CSS.
      // Using #access: FALSE is causing all properties to be lost.
      $form['properties']['#type'] = 'container';
      $form['properties']['#attributes']['style'] = 'display: none';

      // Disable client-side validation.
      $form['#attributes']['novalidate'] = TRUE;

      // Replace 'Save' button with 'Update default value'.
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Update default value'),
        '#attributes' => ['data-hash' => 'webform-tab--advanced'],
        '#validate' => ['::validateDefaultValue'],
        '#submit' => ['::getDefaultValue'],
        '#button_type' => 'primary',
      ];

      // Remove 'Save + Add element'.
      unset($form['actions']['save_add_element']);

      if ($this->isAjax()) {
        $form['actions']['submit']['#ajax'] = [
          'callback' => '::submitAjaxForm',
          'event' => 'click',
        ];
      }
    }
    else {
      // Add 'Set default value' button.
      $form['properties']['default']['actions'] = ['#type' => 'container'];
      $form['properties']['default']['actions']['set_default_value'] = [
        '#type' => 'submit',
        '#value' => $this->t('Set default value'),
        '#submit' => ['::setDefaultValue'],
        '#attributes' => ['formnovalidate' => 'formnovalidate'],
        '#_validate_form' => TRUE,
      ];

      if ($this->isAjax()) {
        $form['properties']['default']['actions']['set_default_value']['#ajax'] = [
          'callback' => '::submitAjaxForm',
          'event' => 'click',
        ];
      }

      $form['#attached']['library'][] = 'webform/webform.form';
    }

    return $form;
  }

  /**
   * Get updated default value for an element.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function getDefaultValue(array &$form, FormStateInterface $form_state) {
    $default_value = $form_state->getValue('default_value');
    $form_state->unsetValue('default_value');

    // Convert composite or multiple default value array to string.
    // @see \Drupal\webform\Plugin\WebformElementBase::setConfigurationFormDefaultValue
    $element_plugin = $this->getWebformElementPlugin();
    if (is_array($default_value)) {
      if ($element_plugin->isComposite()) {
        $default_value = WebformYaml::encode($default_value);
      }
      else {
        $default_value = implode(', ', $default_value);
      }
    }

    $form_state->setValueForElement($form['properties']['default']['default_value'], $default_value);
    NestedArray::setValue($form_state->getUserInput(), ['properties', 'default_value'], $default_value);

    $form_state->set('active_tab', 'advanced');
    $form_state->set('default_value_element', NULL);
    $form_state->setRebuild(TRUE);
  }

  /**
   * Set default value to be updated.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function setDefaultValue(array &$form, FormStateInterface $form_state) {
    $element_plugin = $this->getWebformElementPlugin();
    $subform_state = SubformState::createForSubform($form['properties'], $form, $form_state);
    $properties = $element_plugin->getConfigurationFormProperties($form, $subform_state);

    if (isset($properties['#default_value'])) {
      // @see \Drupal\webform\Plugin\WebformElementBase::getConfigurationFormProperty
      if ($element_plugin->hasMultipleValues($properties) && is_string($properties['#default_value'])) {
        $properties['#default_value'] = preg_split('/\s*,\s*/', $properties['#default_value']);
      }
    }

    $form_state->set('default_value_element', $properties);
    $form_state->setRebuild(TRUE);
  }

  /**
   * Default value validation handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validateDefaultValue(array &$form, FormStateInterface $form_state) {
    // Suppress all errors to allow for tokens to be included as the default value.
    $form_state->clearErrors();
  }

}
