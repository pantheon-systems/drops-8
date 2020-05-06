<?php

namespace Drupal\webform\Form;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\webform\Utility\WebformElementHelper;
use Drupal\webform\Utility\WebformFormHelper;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformTokenManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base webform for webform variants.
 */
abstract class WebformVariantFormBase extends FormBase {

  use WebformDialogFormTrait;

  /**
   * Machine name maxlenght.
   */
  const MACHINE_NAME_MAXLENGHTH = 64;

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
   * The webform variant.
   *
   * @var \Drupal\webform\Plugin\WebformVariantInterface
   */
  protected $webformVariant;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_variant_form';
  }

  /**
   * Constructs a WebformVariantFormBase.
   *
   * @param \Drupal\webform\WebformTokenManagerInterface $token_manager
   *   The webform token manager.
   */
  public function __construct(WebformTokenManagerInterface $token_manager) {
    $this->tokenManager = $token_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('webform.token_manager')
    );
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\webform\WebformInterface $webform
   *   The webform.
   * @param string $webform_variant
   *   The webform variant ID.
   *
   * @return array
   *   The form structure.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Throws not found exception if the number of variant instances for this
   *   webform exceeds the variant's cardinality.
   */
  public function buildForm(array $form, FormStateInterface $form_state, WebformInterface $webform = NULL, $webform_variant = NULL) {
    $this->webform = $webform;
    try {
      $this->webformVariant = $this->prepareWebformVariant($webform_variant);
    }
    catch (PluginNotFoundException $e) {
      throw new NotFoundHttpException("Invalid variant id: '$webform_variant'.");
    }

    // Add meta data to webform variant form.
    // This information makes it a little easier to alter a variant's form.
    $form['#webform_id'] = $this->webform->id();
    $form['#webform_variant_id'] = $this->webformVariant->getVariantId();
    $form['#webform_variant_plugin_id'] = $this->webformVariant->getPluginId();

    $request = $this->getRequest();

    $form['description'] = [
      '#type' => 'container',
      'text' => [
        '#markup' => $this->webformVariant->description(),
        '#prefix' => '<p>',
        '#suffix' => '</p>',
      ],
      '#weight' => -20,
    ];

    $form['id'] = [
      '#type' => 'value',
      '#value' => $this->webformVariant->getPluginId(),
    ];

    $form['general'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('General settings'),
      '#weight' => -10,
    ];
    $form['general']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#maxlength' => 255,
      '#default_value' => $this->webformVariant->getLabel(),
      '#required' => TRUE,
      '#attributes' => ['autofocus' => 'autofocus'],
    ];
    $form['general']['variant_id'] = [
      '#type' => 'machine_name',
      '#maxlength' => static::MACHINE_NAME_MAXLENGHTH,
      '#description' => $this->t('A unique name for this variant instance. Must be alpha-numeric and underscore separated.'),
      '#default_value' => $this->webformVariant->getVariantId(),
      '#required' => TRUE,
      '#disabled' => $this->webformVariant->getVariantId() ? TRUE : FALSE,
      '#machine_name' => [
        'source' => ['general', 'label'],
        'exists' => [$this, 'exists'],
      ],
    ];
    // Only show variants select menu when there is more than
    // one variant available.
    $variant_options = $this->getVariantElementsAsOptions();
    if (count($variant_options) === 1) {
      $form['general']['element_key'] = [
        '#type' => 'value',
        '#value' => key($variant_options),
      ];
      $form['general']['element_key_item'] = [
        '#title' => $this->t('Element'),
        '#type' => 'item',
        '#markup' => reset($variant_options),
        '#access' => TRUE,
      ];
    }
    else {
      $form['general']['element_key'] = [
        '#type' => 'select',
        '#title' => $this->t('Element'),
        '#options' => $variant_options,
        '#default_value' => $this->webformVariant->getElementKey(),
        '#required' => TRUE,
      ];
    }
    $form['general']['notes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Administrative notes'),
      '#default_value' => $this->webformVariant->getNotes(),
    ];

    $form['advanced'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Advanced settings'),
      '#weight' => -10,
    ];
    $form['advanced']['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable the %name variant', ['%name' => $this->webformVariant->label()]),
      '#return_value' => TRUE,
      '#default_value' => $this->webformVariant->isEnabled(),
      // Disable broken plugins.
      '#disabled' => ($this->webformVariant->getPluginId() == 'broken'),
    ];

    $form['#parents'] = [];
    $form['settings'] = [
      '#tree' => TRUE,
      '#parents' => ['settings'],
    ];
    $subform_state = SubformState::createForSubform($form['settings'], $form, $form_state);
    $form['settings'] = $this->webformVariant->buildConfigurationForm($form['settings'], $subform_state);

    // Get $form['settings']['#attributes']['novalidate'] and apply it to the
    // $form.
    // This allows variants with hide/show logic to skip HTML5 validation.
    // @see http://stackoverflow.com/questions/22148080/an-invalid-form-control-with-name-is-not-focusable
    if (isset($form['settings']['#attributes']['novalidate'])) {
      $form['#attributes']['novalidate'] = 'novalidate';
    }
    $form['settings']['#tree'] = TRUE;

    // Check the URL for a weight, then the webform variant,
    // otherwise use default.
    $form['weight'] = [
      '#type' => 'hidden',
      '#value' => $request->query->has('weight') ? (int) $request->query->get('weight') : $this->webformVariant->getWeight(),
    ];

    // Build tabs.
    $tabs = [
      'advanced' => [
        'title' => $this->t('Advanced'),
        'elements' => [
          'advanced',
          'additional',
          'development',
        ],
        'weight' => 20,
      ],
    ];
    $form = WebformFormHelper::buildTabs($form, $tabs);

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    // Add token links below the form and on every tab.
    $form['token_tree_link'] = $this->tokenManager->buildTreeElement();
    if ($form['token_tree_link']) {
      $form['token_tree_link'] += [
        '#weight' => 101,
      ];
    }
    return $this->buildDialogForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // The webform variant configuration is stored in the 'settings' key in
    // the webform, pass that through for validation.
    $subform_state = SubformState::createForSubform($form['settings'], $form, $form_state);
    $this->webformVariant->validateConfigurationForm($form, $subform_state);

    // Process variant state webform errors.
    $this->processVariantFormErrors($subform_state, $form_state);

    // Update the original webform values.
    $form_state->setValue('settings', $subform_state->getValues());
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();

    // The webform variant configuration is stored in the 'settings' key in
    // the webform, pass that through for submission.
    $subform_state = SubformState::createForSubform($form['settings'], $form, $form_state);
    $this->webformVariant->submitConfigurationForm($form, $subform_state);

    // Update the original webform values.
    $form_state->setValue('settings', $subform_state->getValues());

    $this->webformVariant->setVariantId($form_state->getValue('variant_id'));
    $this->webformVariant->setLabel($form_state->getValue('label'));
    $this->webformVariant->setNotes($form_state->getValue('notes'));
    $this->webformVariant->setElementKey($form_state->getValue('element_key'));
    $this->webformVariant->setStatus($form_state->getValue('status'));
    $this->webformVariant->setWeight($form_state->getValue('weight'));

    if ($this instanceof WebformVariantAddForm) {
      $this->webform->addWebformVariant($this->webformVariant);
      $this->messenger()->addStatus($this->t('The webform variant was successfully added.'));
    }
    else {
      $this->webform->updateWebformVariant($this->webformVariant);
      $this->messenger()->addStatus($this->t('The webform variant was successfully updated.'));
    }

    $form_state->setRedirectUrl($this->webform->toUrl('variants', ['query' => ['update' => $this->webformVariant->getVariantId()]]));
  }

  /**
   * Determines if the webform variant ID already exists.
   *
   * @param string $variant_id
   *   The webform variant ID.
   *
   * @return bool
   *   TRUE if the webform variant ID exists, FALSE otherwise.
   */
  public function exists($variant_id) {
    $instance_ids = $this->webform->getVariants()->getInstanceIds();
    return (isset($instance_ids[$variant_id])) ? TRUE : FALSE;
  }

  /**
   * Get the webform variant's webform.
   *
   * @return \Drupal\webform\WebformInterface
   *   A webform.
   */
  public function getWebform() {
    return $this->webform;
  }

  /**
   * Get the webform variant.
   *
   * @return \Drupal\webform\Plugin\WebformVariantInterface
   *   A webform variant.
   */
  public function getWebformVariant() {
    return $this->webformVariant;
  }

  /**
   * Process variant webform errors in webform.
   *
   * @param \Drupal\Core\Form\FormStateInterface $variant_state
   *   The webform variant webform state.
   * @param \Drupal\Core\Form\FormStateInterface &$form_state
   *   The webform state.
   */
  protected function processVariantFormErrors(FormStateInterface $variant_state, FormStateInterface &$form_state) {
    foreach ($variant_state->getErrors() as $name => $message) {
      $form_state->setErrorByName($name, $message);
    }
  }

  /****************************************************************************/
  // Variant methods.
  /****************************************************************************/

  /**
   * Get key/value array of webform variant elements.
   *
   * @return array
   *   A key/value array of webform variant elements.
   */
  protected function getVariantElementsAsOptions() {
    $webform = $this->getWebform();
    $variant_plugin_id = $this->getWebformVariant()->getPluginId();
    $elements = $this->getWebform()->getElementsVariant();
    $options = [];
    foreach ($elements as $element_key) {
      $element = $webform->getElement($element_key);
      if ($element['#variant'] === $variant_plugin_id) {
        $options[$element_key] = WebformElementHelper::getAdminTitle($element);
      }
    }
    return $options;
  }

}
