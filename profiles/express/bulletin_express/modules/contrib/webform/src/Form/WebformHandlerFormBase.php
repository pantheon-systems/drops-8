<?php

namespace Drupal\webform\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\webform\WebformDialogTrait;
use Drupal\webform\WebformHandlerInterface;
use Drupal\webform\WebformInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a base webform for webform handlers.
 */
abstract class WebformHandlerFormBase extends FormBase {

  use WebformDialogTrait;

  /**
   * The webform.
   *
   * @var \Drupal\webform\WebformInterface
   */
  protected $webform;

  /**
   * The webform handler.
   *
   * @var \Drupal\webform\WebformHandlerInterface
   */
  protected $webformHandler;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_handler_form';
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
   * @param string $webform_handler
   *   The webform handler ID.
   *
   * @return array
   *   The webform structure.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Throws not found exception if the number of handler instances for this
   *   webform exceeds the handler's cardinality.
   */
  public function buildForm(array $form, FormStateInterface $form_state, WebformInterface $webform = NULL, $webform_handler = NULL) {
    $this->webform = $webform;
    try {
      $this->webformHandler = $this->prepareWebformHandler($webform_handler);
    }
    catch (PluginNotFoundException $e) {
      throw new NotFoundHttpException("Invalid handler id: '$webform_handler'.");
    }

    // Limit the number of plugin instanced allowed.
    if (!$this->webformHandler->getHandlerId()) {
      $plugin_id = $this->webformHandler->getPluginId();
      $cardinality = $this->webformHandler->cardinality();
      $number_of_instances = $webform->getHandlers($plugin_id)->count();
      if ($cardinality !== WebformHandlerInterface::CARDINALITY_UNLIMITED && $cardinality <= $number_of_instances) {
        $t_args = ['@number' => $cardinality, '@instances' => $this->formatPlural($cardinality, $this->t('instance is'), $this->t('instances are'))];
        throw new NotFoundHttpException($this->t('Only @number @instance permitted', $t_args));
      }
    }

    $request = $this->getRequest();

    $form['description'] = [
      '#markup' => $this->webformHandler->description(),
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];

    $form['id'] = [
      '#type' => 'value',
      '#value' => $this->webformHandler->getPluginId(),
    ];

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable the %name handler.', ['%name' => $this->webformHandler->label()]),
      '#return_value' => TRUE,
      '#default_value' => $this->webformHandler->isEnabled(),
      // Disable broken plugins.
      '#disabled' => ($this->webformHandler->getPluginId() == 'broken'),
    ];

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#maxlength' => 255,
      '#default_value' => $this->webformHandler->label(),
      '#required' => TRUE,
      '#attributes' => ['autofocus' => 'autofocus'],
    ];

    $form['handler_id'] = [
      '#type' => 'machine_name',
      '#maxlength' => 64,
      '#description' => $this->t('A unique name for this handler instance. Must be alpha-numeric and underscore separated.'),
      '#default_value' => $this->webformHandler->getHandlerId() ?: $this->getUniqueMachineName($this->webformHandler),
      '#required' => TRUE,
      '#disabled' => $this->webformHandler->getHandlerId() ? TRUE : FALSE,
      '#machine_name' => [
        'exists' => [$this, 'exists'],
      ],
    ];

    $form['settings'] = $this->webformHandler->buildConfigurationForm([], $form_state);
    // Get $form['settings']['#attributes']['novalidate'] and apply it to the
    // $form.
    // This allows handlers with hide/show logic to skip HTML5 validation.
    // @see http://stackoverflow.com/questions/22148080/an-invalid-form-control-with-name-is-not-focusable
    if (isset($form['settings']['#attributes']['novalidate'])) {
      $form['#attributes']['novalidate'] = 'novalidate';
    }
    $form['settings']['#tree'] = TRUE;

    // Check the URL for a weight, then the webform handler,
    // otherwise use default.
    $form['weight'] = [
      '#type' => 'hidden',
      '#value' => $request->query->has('weight') ? (int) $request->query->get('weight') : $this->webformHandler->getWeight(),
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    return $this->buildFormDialog($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // The webform handler configuration is stored in the 'settings' key in
    // the webform, pass that through for validation.
    $settings = $form_state->getValue('settings') ?: [];
    $handler_state = (new FormState())->setValues($settings);
    $this->webformHandler->validateConfigurationForm($form, $handler_state);

    // Process handler state webform errors.
    $this->processHandlerFormErrors($handler_state, $form_state);

    // Update the original webform values.
    $form_state->setValue('settings', $handler_state->getValues());
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();

    // The webform handler configuration is stored in the 'settings' key in
    // the webform, pass that through for submission.
    $handler_data = (new FormState())->setValues($form_state->getValue('settings'));

    $this->webformHandler->submitConfigurationForm($form, $handler_data);
    // Update the original webform values.
    $form_state->setValue('settings', $handler_data->getValues());

    $this->webformHandler->setHandlerId($form_state->getValue('handler_id'));
    $this->webformHandler->setLabel($form_state->getValue('label'));
    $this->webformHandler->setStatus($form_state->getValue('status'));
    $this->webformHandler->setWeight($form_state->getValue('weight'));

    if ($this instanceof WebformHandlerAddForm) {
      $this->webform->addWebformHandler($this->webformHandler);
      drupal_set_message($this->t('The webform handler was successfully added.'));
    }
    else {
      $this->webform->updateWebformHandler($this->webformHandler);
      drupal_set_message($this->t('The webform handler was successfully updated.'));
    }

    $form_state->setRedirectUrl($this->getRedirectUrl());
  }

  /**
   * {@inheritdoc}
   */
  protected function getRedirectUrl() {
    return $this->webform->toUrl('handlers-form');
  }

  /**
   * Generates a unique machine name for a webform handler instance.
   *
   * @param \Drupal\webform\WebformHandlerInterface $handler
   *   The webform handler.
   *
   * @return string
   *   Returns the unique name.
   */
  public function getUniqueMachineName(WebformHandlerInterface $handler) {
    $suggestion = $handler->getPluginId();
    $count = 1;
    $machine_default = $suggestion;
    $instance_ids = $this->webform->getHandlers()->getInstanceIds();
    while (isset($instance_ids[$machine_default])) {
      $machine_default = $suggestion . '_' . $count++;
    }
    // Only return a suggestion if it is not the default plugin id.
    return ($machine_default != $handler->getPluginId()) ? $machine_default : '';
  }

  /**
   * Determines if the webform handler ID already exists.
   *
   * @param string $handler_id
   *   The webform handler ID.
   *
   * @return bool
   *   TRUE if the webform handler ID exists, FALSE otherwise.
   */
  public function exists($handler_id) {
    $instance_ids = $this->webform->getHandlers()->getInstanceIds();

    return (isset($instance_ids[$handler_id])) ? TRUE : FALSE;
  }

  /**
   * Process handler webform errors in webform.
   *
   * @param \Drupal\Core\Form\FormStateInterface $handler_state
   *   The webform handler webform state.
   * @param \Drupal\Core\Form\FormStateInterface &$form_state
   *   The webform state.
   */
  protected function processHandlerFormErrors(FormStateInterface $handler_state, FormStateInterface &$form_state) {
    foreach ($handler_state->getErrors() as $name => $message) {
      $form_state->setErrorByName($name, $message);
    }
  }

}
