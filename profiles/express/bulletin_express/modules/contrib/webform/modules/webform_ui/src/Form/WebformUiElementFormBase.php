<?php

namespace Drupal\webform_ui\Form;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\webform\Utility\WebformDialogHelper;
use Drupal\webform\WebformDialogTrait;
use Drupal\webform\WebformElementManagerInterface;
use Drupal\webform\WebformEntityElementsValidator;
use Drupal\webform\WebformInterface;
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

  use WebformDialogTrait;

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
   * @var \Drupal\webform\WebformElementManagerInterface
   */
  protected $elementManager;

  /**
   * Webform element validator.
   *
   * @var \Drupal\webform\WebformEntityElementsValidator
   */
  protected $elementsValidator;

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
  protected $parent_key;

  /**
   * The webform element's original element type.
   *
   * @var string
   */
  protected $originalType;

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
   * @param \Drupal\webform\WebformElementManagerInterface $element_manager
   *   The webform element manager.
   * @param \Drupal\webform\WebformEntityElementsValidator $elements_validator
   *   Webform element validator.
   */
  public function __construct(RendererInterface $renderer, EntityFieldManagerInterface $entity_field_manager, WebformElementManagerInterface $element_manager, WebformEntityElementsValidator $elements_validator) {
    $this->renderer = $renderer;
    $this->entityFieldManager = $entity_field_manager;
    $this->elementManager = $element_manager;
    $this->elementsValidator = $elements_validator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer'),
      $container->get('entity_field.manager'),
      $container->get('plugin.manager.webform.element'),
      $container->get('webform.elements_validator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, WebformInterface $webform = NULL, $key = NULL, $parent_key = '') {
    $this->webform = $webform;
    $this->key = $key;
    $this->parent_key = $parent_key;

    $webform_element = $this->getWebformElement();

    $form['properties'] = $webform_element->buildConfigurationForm([], $form_state);

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
        '#markup' => $webform_element->getPluginLabel(),
      ],
      '#weight' => -100,
      '#parents' => ['type'],
    ];

    // Set change element type.
    if ($key && $webform_element->getRelatedTypes($this->element)) {
      $route_parameters = ['webform' => $webform->id(), 'key' => $key];
      if ($this->originalType) {
        $original_webform_element = $this->elementManager->createInstance($this->originalType);
        $route_parameters = ['webform' => $webform->id(), 'key' => $key];
        $form['properties']['element']['type']['cancel'] = [
          '#type' => 'link',
          '#title' => $this->t('Cancel'),
          '#url' => new Url('entity.webform_ui.element.edit_form', $route_parameters),
          '#attributes' => WebformDialogHelper::getModalDialogAttributes(800, ['button', 'button--small']),
        ];
        $form['properties']['element']['type']['#description'] = '(' . $this->t('Changing from %type', ['%type' => $original_webform_element->getPluginLabel()]) . ')';
      }
      else {
        $form['properties']['element']['type']['change_type'] = [
          '#type' => 'link',
          '#title' => $this->t('Change'),
          '#url' => new Url('entity.webform_ui.change_element', $route_parameters),
          '#attributes' => WebformDialogHelper::getModalDialogAttributes(800, ['button', 'button--small']),
        ];
      }
    }

    // Set element key reserved word warning message.
    if (!$key) {
      $reserved_keys = ['form_build_id', 'form_token', 'form_id', 'data', 'op'];
      $reserved_keys = array_merge($reserved_keys, array_keys($this->entityFieldManager->getBaseFieldDefinitions('webform_submission')));
      $form['#attached']['drupalSettings']['webform_ui']['reserved_keys'] = $reserved_keys;
      $form['#attached']['library'][] = 'webform_ui/webform_ui.element';
      $form['properties']['element']['key_warning'] = [
        '#type' => 'webform_message',
        '#message_type' => 'warning',
        '#message_message' => $this->t("Please avoid using the reserved word '@key' as the element's key."),
        '#weight' => -99,
        '#attributes' => ['style' => 'display:none'],
      ];
    }

    // Set element key.
    $form['properties']['element']['key'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Key'),
      '#machine_name' => [
        'label' => $this->t('Key'),
        'exists' => [$this, 'exists'],
        'source' => ['title'],
      ],
      '#required' => TRUE,
      '#parents' => ['key'],
      '#disabled' => ($key) ? TRUE : FALSE,
      '#default_value' => $key,
      '#weight' => -98,
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

    // Set flex.
    // Hide #flex property if parent element is not a 'webform_flexbox'.
    if (isset($form['properties']['flex']) && !$this->isParentElementFlexbox($key, $parent_key)) {
      $form['properties']['flex']['#access'] = FALSE;
    }

    // Set actions.
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
      '#_validate_form' => TRUE,
    ];

    return $this->buildFormDialog($form, $form_state);
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

    // The webform element configuration is stored in the 'properties' key in
    // the webform, pass that through for validation.
    $element_form_state = clone $form_state;
    $element_form_state->setValues($form_state->getValue('properties'));

    // Validate configuration webform.
    $webform_element = $this->getWebformElement();
    $webform_element->validateConfigurationForm($form, $element_form_state);

    // Get errors for element validation.
    $element_errors = $element_form_state->getErrors();
    foreach ($element_errors as $element_error) {
      $form_state->setErrorByName(NULL, $element_error);
    }

    // Stop validation is the element properties has any errors.
    if ($form_state->hasAnyErrors()) {
      return;
    }

    // Set element properties.
    $properties = $webform_element->getConfigurationFormProperties($form, $element_form_state);
    $parent_key = $form_state->getValue('parent_key');
    $key = $form_state->getValue('key');
    if ($key) {
      $this->key = $key;
      $this->webform->setElementProperties($key, $properties, $parent_key);

      // Validate elements.
      if ($messages = $this->elementsValidator->validate($this->webform)) {
        $t_args = [':href' => Url::fromRoute('entity.webform.source_form', ['webform' => $this->webform->id()])->toString()];
        $form_state->setErrorByName('elements', $this->t('There has been error validating the elements. You may need to edit the <a href=":href">YAML source</a> to resolve the issue.', $t_args));
        foreach ($messages as $message) {
          drupal_set_message($message, 'error');
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $webform_element = $this->getWebformElement();

    // The webform element configuration is stored in the 'properties' key in
    // the webform, pass that through for submission.
    $element_form_state = clone $form_state;
    $element_form_state->setValues($form_state->getValue('properties'));

    // Submit element configuration.
    // Generally, elements will not be processing any submitted properties.
    // It is possible that a custom element might need to call a third-party API
    // to 'register' the element.
    $webform_element->submitConfigurationForm($form, $element_form_state);

    // Save the webform with its updated element.
    $this->webform->save();

    // Display status message.
    $properties = $form_state->getValue('properties');
    $t_args = [
      '%title' => (!empty($properties['title'])) ? $properties['title'] : $form_state->getValue('key'),
      '@action' => $this->action,
    ];
    drupal_set_message($this->t('%title has been @action.', $t_args));

    $form_state->setRedirectUrl($this->getRedirectUrl());
  }

  /**
   * {@inheritdoc}
   */
  protected function getRedirectUrl() {
    if ($url = $this->getRedirectDestinationUrl()) {
      return $url;
    }
    return $this->webform->toUrl('edit-form', ['query' => ['element-update' => $this->key]]);
  }

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
  public function getWebformElement() {
    return $this->elementManager->getElementInstance($this->element);
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
    return $this->parent_key;
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

}
