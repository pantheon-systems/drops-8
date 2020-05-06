<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailFormatHelper;
use Drupal\webform\Element\WebformComputedTwig as WebformComputedTwigElement;
use Drupal\webform\Element\WebformComputedBase as WebformComputedBaseElement;
use Drupal\webform\Plugin\WebformElementBase;
use Drupal\webform\Plugin\WebformElementComputedInterface;
use Drupal\webform\Plugin\WebformElementDisplayOnInterface;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a base class for 'webform_computed' elements.
 */
abstract class WebformComputedBase extends WebformElementBase implements WebformElementDisplayOnInterface, WebformElementComputedInterface {

  use WebformDisplayOnTrait;

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    return [
      // Element settings.
      'title' => '',
      // Markup settings.
      'display_on' => static::DISPLAY_ON_BOTH,
      // Description/Help.
      'help' => '',
      'help_title' => '',
      'description' => '',
      'more' => '',
      'more_title' => '',
      // Form display.
      'title_display' => '',
      'description_display' => '',
      // Computed values.
      'template' => '',
      'mode' => WebformComputedBaseElement::MODE_AUTO,
      'hide_empty' => FALSE,
      'store' => FALSE,
      'ajax' => FALSE,
      // Attributes.
      'wrapper_attributes' => [],
      'label_attributes' => [],
    ] + $this->defineDefaultBaseProperties();
  }

  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function isInput(array $element) {
    // Set to TRUE so that the computed value can be exported.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function isContainer(array $element) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepare($element, $webform_submission);

    // Hide element if it should not be displayed on 'form'.
    if (!$this->isDisplayOn($element, static::DISPLAY_ON_FORM)) {
      $element['#access'] = FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareElementValidateCallbacks(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepareElementValidateCallbacks($element, $webform_submission);
    $element['#element_validate'][] = [get_class($this), 'validateWebformComputed'];
  }

  /**
   * {@inheritdoc}
   */
  public function formatTableColumn(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    return ['#markup' => $this->formatHtml($element, $webform_submission)];
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    // Get stored value if it is set.
    $value = $webform_submission->getElementData($element['#webform_key']);
    if (isset($value)) {
      return $value;
    }

    return $this->computeValue($element, $webform_submission);
  }

  /**
   * {@inheritdoc}
   */
  protected function formatHtmlItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission);
    if ($this->getMode($element) === WebformComputedBaseElement::MODE_TEXT) {
      return nl2br($value);
    }
    else {
      return $value;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function formatTextItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission);
    if ($this->getMode($element) === WebformComputedBaseElement::MODE_HTML) {
      return MailFormatHelper::htmlToText($value);
    }
    else {
      return $value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getRelatedTypes(array $element) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function preview() {
    return [
      '#type' => $this->getTypeName(),
      '#title' => $this->getPluginLabel(),
      '#template' => $this->t('This is a @label value.', ['@label' => $this->getPluginLabel()]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['computed'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Computed settings'),
    ];
    $form['computed']['display_on'] = [
      '#type' => 'select',
      '#title' => $this->t('Display on'),
      '#options' => $this->getDisplayOnOptions(TRUE),
    ];
    $form['computed']['display_on_message'] = [
      '#type' => 'webform_message',
      '#message_message' => $this->t("This computed element's value will only be available as a token or exported value."),
      '#message_type' => 'warning',
      '#access' => TRUE,
      '#states' => [
        'visible' => [':input[name="properties[display_on]"]' => ['value' => WebformElementDisplayOnInterface::DISPLAY_ON_NONE]],
      ],
    ];
    $form['computed']['mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Mode'),
      '#options' => [
        WebformComputedBaseElement::MODE_AUTO => $this->t('Auto-detect'),
        WebformComputedBaseElement::MODE_HTML => $this->t('HTML'),
        WebformComputedBaseElement::MODE_TEXT => $this->t('Plain text'),
      ],
    ];
    $form['computed']['template'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'text',
      '#title' => $this->t('Computed value/markup'),
    ];
    $form['computed']['whitespace'] = [
      '#type' => 'select',
      '#title' => $this->t('Remove whitespace around the'),
      '#empty_option' => $this->t('- None -'),
      '#options' => [
        WebformComputedTwigElement::WHITESPACE_TRIM => $this->t('computed value'),
        WebformComputedTwigElement::WHITESPACE_SPACELESS => $this->t('computed value and between HTML tags'),
      ],
    ];
    $form['computed']['hide_empty'] = [
      '#type' => 'checkbox',
      '#return_value' => TRUE,
      '#title' => $this->t('Hide empty'),
      '#description' => $this->t('If checked the computed elements will be hidden from display when the value is an empty string.'),
    ];
    $form['computed']['store'] = [
      '#type' => 'checkbox',
      '#return_value' => TRUE,
      '#title' => $this->t('Store value in the database'),
      '#description' => $this->t('The value will be stored in the database. As a result, it will only be recalculated when the submission is updated. This option is required when accessing the computed element through Views.'),
    ];
    $form['computed']['ajax'] = [
      '#type' => 'checkbox',
      '#return_value' => TRUE,
      '#title' => $this->t('Automatically update the computed value using Ajax'),
      '#description' => $this->t('If checked, any element used within the computed value/markup will trigger any automatic update.'),
    ];
    $form['computed']['tokens'] = ['#access' => TRUE, '#weight' => 10] + $this->tokenManager->buildTreeElement();
    return $form;
  }

  /**
   * Form API callback. Removes ignored element for $form_state values.
   */
  public static function validateWebformComputed(array &$element, FormStateInterface $form_state, array &$completed_form) {
    if (empty($element['#store'])) {
      // Unset the value from the $form_state to prevent modules from relying
      // on this value.
      $key = $element['#webform_key'];
      $form_state->unsetValue($key);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(array &$element, WebformSubmissionInterface $webform_submission, $update = TRUE) {
    if ($update || empty($element['#store']) || $webform_submission->getWebform()->getSetting('results_disabled')) {
      return;
    }

    // Recalculate the stored computed value to account new a submission's
    // generated sid and serial.
    $key = $element['#webform_key'];
    $value = (string) $this->computeValue($element, $webform_submission);

    // Update the submission's value.
    $webform_submission->setElementData($key, $value);

    // The below database update is a one-off solution because there is
    // currently no other instances where a single element's value
    // needs to be updated.
    // @see \Drupal\webform\WebformSubmissionStorage::saveData
    $fields = [
      'webform_id' => $webform_submission->getWebform()->id(),
      'sid' => $webform_submission->id(),
      'name' => $key,
      'property' => '',
      'delta' => 0,
      'value' => $value,
    ];
    \Drupal::database()->update('webform_submission_data')
      ->fields($fields)
      ->condition('webform_id', $fields['webform_id'])
      ->condition('sid', $fields['sid'])
      ->condition('name', $fields['name'])
      ->execute();
  }

  /**
   * Get computed element markup.
   *
   * @param array $element
   *   An element.
   *
   * @return string
   *   The type of markup, HTML or plain-text.
   */
  protected function getMode(array $element) {
    return WebformComputedBaseElement::getMode($element);
  }

  /**
   * {@inheritdoc}
   */
  public function computeValue(array $element, WebformSubmissionInterface $webform_submission) {
    $class = $this->getFormElementClassDefinition();
    return $class::computeValue($element, $webform_submission);
  }

  /**
   * {@inheritdoc}
   */
  public function getTestValues(array $element, WebformInterface $webform, array $options = []) {
    // Computed elements should never get a test value.
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getElementSelectorInputValue($selector, $trigger, array $element, WebformSubmissionInterface $webform_submission) {
    return (string) $this->computeValue($element, $webform_submission);
  }

}
