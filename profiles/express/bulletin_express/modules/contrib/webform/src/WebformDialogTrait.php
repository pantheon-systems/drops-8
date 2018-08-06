<?php

namespace Drupal\webform;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\webform\Ajax\ScrollTopCommand;
use Drupal\webform\Utility\WebformDialogHelper;

/**
 * Trait class webform dialogs.
 *
 * @todo Issue #2785047: In Outside In mode, messages should appear in the off-canvas tray, not the main page.
 * @see https://www.drupal.org/node/2785047
 */
trait WebformDialogTrait {

  /**
   * Is the current request for an AJAX modal dialog.
   *
   * @return bool
   *   TRUE if the current request is for an AJAX modal dialog.
   */
  protected function isModalDialog() {
    $wrapper_format = $this->getRequest()
      ->get(MainContentViewSubscriber::WRAPPER_FORMAT);
    return (in_array($wrapper_format, [
      'drupal_ajax',
      'drupal_modal',
      'drupal_dialog',
      'drupal_dialog_' . WebformDialogHelper::getOffCanvasTriggerName(),
    ])) ? TRUE : FALSE;
  }

  /**
   * Is the current request for an off canvas dialog.
   *
   * @return bool
   *   TRUE if the current request is for an off canvas dialog.
   */
  protected function isOffCanvasDialog() {
    $wrapper_format = $this->getRequest()
      ->get(MainContentViewSubscriber::WRAPPER_FORMAT);
    return (in_array($wrapper_format, [
      'drupal_dialog_' . WebformDialogHelper::getOffCanvasTriggerName(),
    ])) ? TRUE : FALSE;
  }

  /**
   * Add modal dialog support to a form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The webform with modal dialog support.
   */
  protected function buildFormDialog(array &$form, FormStateInterface $form_state) {
    if (!$this->isModalDialog()) {
      return $form;
    }

    $form['actions']['submit']['#ajax'] = [
      'callback' => '::submitFormDialog',
      'event' => 'click',
    ];
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    $form['#prefix'] = '<div id="webform-dialog">';
    $form['#suffix'] = '</div>';
    return $form;
  }

  /**
   * Add modal dialog support to a confirm form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The webform with modal dialog support.
   */
  protected function buildConfirmFormDialog(array &$form, FormStateInterface $form_state) {
    if (!$this->isModalDialog() || $this->isOffCanvasDialog()) {
      return $form;
    }

    $this->buildFormDialog($form, $form_state);

    // Replace 'Cancel' link button with a close dialog button.
    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
      '#submit' => ['::noSubmit'],
      '#limit_validation_errors' => [],
      '#weight' => 100,
      '#ajax' => [
        'callback' => '::closeDialog',
        'event' => 'click',
      ],
    ];
    return $form;
  }

  /****************************************************************************/
  // Ajax submit callbacks.
  /****************************************************************************/

  /**
   * Submit form dialog #ajax callback.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An AJAX response that display validation error messages or redirects
   *   to a URL
   */
  public function submitFormDialog(array &$form, FormStateInterface $form_state) {
    if ($form_state->hasAnyErrors()) {
      unset($form['#prefix'], $form['#suffix']);
      $form['status_messages'] = [
        '#type' => 'status_messages',
        '#weight' => -1000,
      ];
      $response = new AjaxResponse();
      $response->addCommand(new HtmlCommand('#webform-dialog', $form));
      $response->addCommand(new ScrollTopCommand('#webform-dialog'));
      return $response;
    }
    else {
      $response = new AjaxResponse();
      if ($path = $this->getRedirectDestinationPath()) {
        $response->addCommand(new RedirectCommand(base_path() . $path));
      }
      elseif ($redirect_url = $this->getRedirectUrl()) {
        $response->addCommand(new RedirectCommand($redirect_url->toString()));
      }
      else {
        $response->addCommand(new CloseDialogCommand());
      }
      return $response;
    }
  }

  /**
   * Close dialog #ajax callback.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return bool|\Drupal\Core\Ajax\AjaxResponse
   *   An AJAX response that display validation error messages.
   */
  public function closeDialog(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new CloseDialogCommand());
    return $response;
  }

  /**
   * Empty submit callback used to only have the submit button to use an #ajax submit callback.
   *
   * This allows modal dialog to using ::submitCallback to validate and submit
   * the form via one ajax required.
   */
  public function noSubmit(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Get the form's redirect URL.
   *
   * Isolate a form's redirect URL/destination so that it can be used by
   * ::submitFormDialog or ::submitForm.
   *
   * @return \Drupal\Core\Url|NULL
   *   The redirect URL or NULL if dialog should just be closed.
   */
  protected function getRedirectUrl() {
    return getDestinationUrl();
  }

  /**
   * Get the current request's redirect destination URL.
   *
   * @return \Drupal\Core\Url|null
   *   The current request's redirect destination or NULL if no
   *   destination available.
   */
  protected function getRedirectDestinationUrl() {
    if ($destination = $this->getRedirectDestinationPath()) {
      return Url::fromUserInput(base_path() . $destination);
    }
    return NULL;
  }

  /**
   * Get the redirect destination path if specified in request.
   *
   * @return string|null
   *   The redirect path or NULL if it is not specified.
   */
  protected function getRedirectDestinationPath() {
    if ($this->requestStack->getCurrentRequest()->get('destination')) {
      return $this->getRedirectDestination()->get();
    }
    return NULL;
  }

}
