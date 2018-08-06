<?php

namespace Drupal\webform\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\webform\Ajax\WebformCloseDialogCommand;
use Drupal\webform\Ajax\WebformRefreshCommand;
use Drupal\webform\Ajax\WebformScrollTopCommand;
use Drupal\webform\Ajax\WebformSubmissionAjaxResponse;
use Drupal\webform\Utility\WebformDialogHelper;
use Drupal\webform\WebformSubmissionForm;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Trait class for Webform Ajax support.
 */
trait WebformAjaxFormTrait {

  /**
   * Returns if webform is using Ajax.
   *
   * @return bool
   *   TRUE if webform is using Ajax.
   */
  abstract protected function isAjax();

  /**
   * Cancel form #ajax callback.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An Ajax response that display validation error messages or redirects
   *   to a URL
   */
  abstract public function cancelAjaxForm(array &$form, FormStateInterface $form_state);

  /**
   * Get default ajax callback settings.
   *
   * @return array
   *   An associative array containing  default ajax callback settings.
   */
  protected function getDefaultAjaxSettings() {
    return [
      'disable-refocus' => TRUE,
      'effect' => 'fade',
      'speed' => 1000,
      'progress' => [
        'type' => 'throbber',
        'message' => '',
      ],
    ];
  }

  /**
   * Is the current request for an Ajax modal/dialog.
   *
   * @return bool
   *   TRUE if the current request is for an Ajax modal/dialog.
   */
  protected function isDialog() {
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
   * Is the current request a quick edit page.
   *
   * @return bool
   *   TRUE if the current request a quick edit page.
   */
  protected function isQuickEdit() {
    if (!$this->moduleHandler->moduleExists('quickedit')) {
      return FALSE;
    }
    
    return (\Drupal::request()->query->get('destination')) ? TRUE : FALSE;
  }

  /**
   * Get the form's Ajax wrapper id.
   *
   * @return string
   *   The form's Ajax wrapper id.
   */
  protected function getWrapperId() {
    return $this->getFormId() . '-ajax';
  }

  /**
   * Add Ajax support to a form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $settings
   *   Ajax settings.
   *
   * @return array
   *   The form with Ajax callbacks.
   */
  protected function buildAjaxForm(array &$form, FormStateInterface $form_state, array $settings = []) {
    if (!$this->isAjax()) {
      return $form;
    }

    // Apply default settings.
    $settings += $this->getDefaultAjaxSettings();

    // Make sure the form has (submit) actions.
    if (!isset($form['actions'])) {
      return $form;
    }

    // Add Ajax callback to all submit buttons.
    foreach (Element::children($form['actions']) as $key) {
      $is_submit_button = (isset($form['actions'][$key]['#type']) && $form['actions'][$key]['#type'] == 'submit');
      if ($is_submit_button) {
        $form['actions'][$key]['#ajax'] = [
          'callback' => '::submitAjaxForm',
          'event' => 'click',
        ] + $settings;
      }
    }

    // Add Ajax wrapper around the form.
    $form['#form_wrapper_id'] = $this->getWrapperId();
    $form['#prefix'] = '<div id="' . $this->getWrapperId() . '">';
    $form['#suffix'] = '</div>';

    // Add Ajax library which contains 'Scroll to top' Ajax command and
    // Ajax callback for confirmation back to link.
    $form['#attached']['library'][] = 'webform/webform.ajax';

    return $form;
  }

  /**
   * Submit form #ajax callback.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An Ajax response that display validation error messages or redirects
   *   to a URL
   */
  public function submitAjaxForm(array &$form, FormStateInterface $form_state) {
    $scroll_top_target = (isset($form['#webform_ajax_scroll_top'])) ? $form['#webform_ajax_scroll_top'] : 'form';
    if ($form_state->hasAnyErrors()) {
      // Display validation errors and scroll to the top of the page.
      $response = $this->replaceForm($form, $form_state);
      if ($scroll_top_target) {
        $response->addCommand(new WebformScrollTopCommand('#' . $this->getWrapperId(), $scroll_top_target));
      }
      return $response;
    }
    elseif ($form_state->isRebuilding()) {
      // Rebuild form.
      $response = $this->replaceForm($form, $form_state);
      if ($scroll_top_target) {
        $response->addCommand(new WebformScrollTopCommand('#' . $this->getWrapperId(), $scroll_top_target));
      }
      return $response;
    }
    elseif ($redirect_url = $this->getFormStateRedirectUrl($form_state)) {
      // Redirect to URL.
      $response = $this->createAjaxResponse($form, $form_state);
      $response->addCommand(new WebformCloseDialogCommand());
      $response->addCommand(new WebformRefreshCommand($redirect_url));
      return $response;
    }
    else {
      return $this->cancelAjaxForm($form, $form_state);
    }
  }

  /**
   * Empty submit callback used to only have the submit button to use an #ajax submit callback.
   *
   * This allows modal dialog to using ::submitCallback to validate and submit
   * the form via one ajax request.
   */
  public function noSubmit(array &$form, FormStateInterface $form_state) {
    // Do nothing.
  }

  /**
   * Create an AjaxResponse or WebformAjaxResponse object.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An AjaxResponse or WebformAjaxResponse object
   */
  protected function createAjaxResponse(array $form, $form_state) {
    $form_object = $form_state->getFormObject();
    if ($form_object instanceof WebformSubmissionForm) {
      /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
      $webform_submission = $form_object->getEntity();

      $response = new WebformSubmissionAjaxResponse();
      $response->setWebformSubmission($webform_submission);
      return $response;
    }
    else {
      return new AjaxResponse();
    }
  }

  /**
   * Replace form via an Ajax response.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An Ajax response that replaces a form.
   */
  protected function replaceForm(array $form, $form_state) {
    // Display messages first by prefixing it the form and setting its weight
    // to -1000.
    $form = [
      'status_messages' => [
        '#type' => 'status_messages',
        '#weight' => -1000,
      ],
    ] + $form;

    // Remove wrapper.
    unset($form['#prefix'], $form['#suffix']);

    $response = $this->createAjaxResponse($form, $form_state);
    $response->addCommand(new HtmlCommand('#' . $this->getWrapperId(), $form));
    return $response;
  }

  /**
   * Get redirect URL from the form's state.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return bool|\Drupal\Core\GeneratedUrl|string
   *   The redirect URL or FALSE if the form is not redirecting.
   */
  protected function getFormStateRedirectUrl(FormStateInterface $form_state) {
    // Always check the ?destination which is used by the off-canvas/system tray.
    if (\Drupal::request()->get('destination')) {
      $destination = $this->getRedirectDestination()->get();
      return (strpos($destination, $destination) === 0) ? $destination : base_path() . $destination;
    }

    // ISSUE:
    // Can't get the redirect URL from the form state during an AJAX submission.
    //
    // WORKAROUND:
    // Re-enable redirect, grab the URL, and then disable again.
    $no_redirect = $form_state->isRedirectDisabled();
    $form_state->disableRedirect(FALSE);
    $redirect = $form_state->getRedirect() ?: $form_state->getResponse();
    $form_state->disableRedirect($no_redirect);

    if ($redirect instanceof RedirectResponse) {
      return $redirect->getTargetUrl();
    }
    elseif ($redirect instanceof Url) {
      return $redirect->setAbsolute()->toString();
    }
    else {
      return FALSE;
    }
  }

}
