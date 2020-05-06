<?php

namespace Drupal\webform\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AnnounceCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use Drupal\webform\Ajax\WebformCloseDialogCommand;
use Drupal\webform\Ajax\WebformConfirmReloadCommand;
use Drupal\webform\Ajax\WebformRefreshCommand;
use Drupal\webform\Ajax\WebformScrollTopCommand;
use Drupal\webform\Ajax\WebformSubmissionAjaxResponse;
use Drupal\webform\Utility\WebformElementHelper;
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
   *   An associative array containing default ajax callback settings.
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
      'drupal_dialog.off_canvas',
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
      'drupal_dialog.off_canvas',
    ])) ? TRUE : FALSE;
  }

  /**
   * Get the form's Ajax wrapper id.
   *
   * @return string
   *   The form's Ajax wrapper id.
   */
  protected function getWrapperId() {
    $form_id = (method_exists($this, 'getBaseFormId') ? $this->getBaseFormId() : $this->getFormId());
    return Html::getId($form_id . '-ajax');
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

    // Add Ajax callback to all submit buttons.
    foreach (Element::children($form) as $element_key) {
      if (!WebformElementHelper::isType($form[$element_key], 'actions')) {
        continue;
      }

      $actions =& $form[$element_key];
      foreach (Element::children($actions) as $action_key) {
        if (WebformElementHelper::isType($actions[$action_key], 'submit')) {
          $actions[$action_key]['#ajax'] = [
            'callback' => '::submitAjaxForm',
            'event' => 'click',
          ] + $settings;
        }
      }
    }

    // Add Ajax wrapper with wrapper content bookmark around the form.
    // @see Drupal.AjaxCommands.prototype.webformScrollTop
    $wrapper_id = $this->getWrapperId();
    $wrapper_attributes = [];
    $wrapper_attributes['id'] = $wrapper_id;
    $wrapper_attributes['class'] = ['webform-ajax-form-wrapper'];
    if (isset($settings['effect'])) {
      $wrapper_attributes['data-effect'] = $settings['effect'];
    }
    if (isset($settings['progress']['type'])) {
      $wrapper_attributes['data-progress-type'] = $settings['progress']['type'];
    }
    $wrapper_attributes = new Attribute($wrapper_attributes);

    $form['#form_wrapper_id'] = $wrapper_id;
    $form['#prefix'] = '<a id="' . $wrapper_id . '-content" tabindex="-1" aria-hidden="true"></a>';
    $form['#prefix'] .= '<div' . $wrapper_attributes . '>';
    $form['#suffix'] = '</div>';

    // Add Ajax library which contains 'Scroll to top' Ajax command and
    // Ajax callback for confirmation back to link.
    $form['#attached']['library'][] = 'webform/webform.ajax';

    // Add validate Ajax form.
    $form['#validate'][] = '::validateAjaxForm';

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

      // Announce validation errors.
      $this->announce($this->t('Form validation errors have been found.'));
    }
    elseif ($form_state->getResponse() instanceof AjaxResponse) {
      // Allow developers via form_alter hooks to set their own Ajax response.
      // The custom Ajax response could be used to close modals and refresh
      // selected regions and blocks on the page.
      $response = $form_state->getResponse();
    }
    elseif ($form_state->isRebuilding()) {
      // Rebuild form.
      $response = $this->replaceForm($form, $form_state);
      if ($scroll_top_target) {
        $response->addCommand(new WebformScrollTopCommand('#' . $this->getWrapperId(), $scroll_top_target));
      }
    }
    elseif ($redirect_url = $this->getFormStateRedirectUrl($form_state)) {
      // Redirect to URL.
      $response = $this->createAjaxResponse($form, $form_state);
      $response->addCommand(new WebformCloseDialogCommand());
      $response->addCommand(new WebformRefreshCommand($redirect_url));
    }
    else {
      $response = $this->cancelAjaxForm($form, $form_state);
    }

    // Add announcements to Ajax response and then reset the announcements.
    // @see \Drupal\webform\Form\WebformAjaxFormTrait::announce
    $announcements = $this->getAnnouncements();
    foreach ($announcements as $announcement) {
      $response->addCommand(new AnnounceCommand($announcement['text'], $announcement['priority']));
    }
    $this->resetAnnouncements();

    return $response;
  }

  /**
   * Validate form #ajax callback.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validateAjaxForm(array &$form, FormStateInterface $form_state) {
    if (!$this->isCallableAjaxCallback($form, $form_state)) {
      $this->missingAjaxCallback($form, $form_state);
    }
  }

  /**
   * Determine if Ajax callback is callable.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return bool
   *   TRUE if if Ajax callback exists.
   */
  protected function isCallableAjaxCallback(array &$form, FormStateInterface $form_state) {
    // Make sure the ajax callback exists.
    // @see \Drupal\Core\Form\FormAjaxResponseBuilder::buildResponse
    $callback = NULL;
    if (($triggering_element = $form_state->getTriggeringElement()) && isset($triggering_element['#ajax']['callback'])) {
      $callback = $triggering_element['#ajax']['callback'];
    }
    $callback = $form_state->prepareCallback($callback);
    return (empty($callback) || !is_callable($callback)) ? FALSE : TRUE;
  }

  /**
   * Handle missing Ajax callback.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function missingAjaxCallback(array &$form, FormStateInterface $form_state) {
    $command = new WebformConfirmReloadCommand($this->t('We are unable to complete the current request.') . PHP_EOL . PHP_EOL . $this->t('Do you want to reload the current page?'));
    print Json::encode([$command->render()]);
    exit;
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
  protected function createAjaxResponse(array $form, FormStateInterface $form_state) {
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
  protected function replaceForm(array $form, FormStateInterface $form_state) {
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
    if ($this->getRequest()->get('destination')) {
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
    $redirect = $form_state->getResponse() ?: $form_state->getRedirect();
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

  /****************************************************************************/
  // Drupal.announce handling.
  //
  // Announcements are stored in the user session because the $form_state
  // is already serialized (and can't be altered) when announcements
  // are added to Ajax response.
  // @see \Drupal\webform\Form\WebformAjaxFormTrait::submitAjaxForm
  /****************************************************************************/

  /**
   * Queue announcement with Ajax response.
   *
   * @param string $text
   *   A string to be read by the UA.
   * @param string $priority
   *   A string to indicate the priority of the message. Can be either
   *   'polite' or 'assertive'.
   *
   * @see \Drupal\Core\Ajax\AnnounceCommand
   * @see \Drupal\webform\Form\WebformAjaxFormTrait::submitAjaxForm
   */
  protected function announce($text, $priority = 'polite') {
    $announcements = $this->getAnnouncements();
    $announcements[] = [
      'text' => $text,
      'priority' => $priority,
    ];
    $this->setAnnouncements($announcements);
  }

  /**
   * Get announcements.
   *
   * @return array
   *   An associative array of announcements.
   */
  protected function getAnnouncements() {
    $session = $this->getRequest()->getSession();
    return $session->get('announcements') ?: [];
  }

  /**
   * Set announcements.
   *
   * @param array $announcements
   *   An associative array of announcements.
   */
  protected function setAnnouncements(array $announcements) {
    $session = $this->getRequest()->getSession();
    $session->set('announcements', $announcements);
    $session->save();
  }

  /**
   * Reset announcements.
   */
  protected function resetAnnouncements() {
    $session = $this->getRequest()->getSession();
    $session->remove('announcements');
    $session->save();
  }

}
