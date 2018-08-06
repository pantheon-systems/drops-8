<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailFormatHelper;
use Drupal\Core\Render\Element;
use Drupal\webform\Element\WebformComputedBase as WebformComputedBaseElement;
use Drupal\webform\Plugin\WebformElementBase;
use Drupal\webform\Plugin\WebformElementDisplayOnInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a base clase for 'webform_computed' elements.
 */
abstract class WebformComputedBase extends WebformElementBase implements WebformElementDisplayOnInterface {

  use WebformDisplayOnTrait;

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return [
      // Markup settings.
      'display_on' => static::DISPLAY_ON_BOTH,
      // Description/Help.
      'help' => '',
      'description' => '',
      'more' => '',
      'more_title' => '',
      // Form display.
      'title_display' => '',
      'description_display' => '',
      // Computed values.
      'value' => '',
      'mode' => WebformComputedBaseElement::MODE_AUTO,
      'store' => FALSE,
      // Attributes.
      'wrapper_attributes' => [],
    ] + $this->getDefaultBaseProperties();
  }

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

    $element['#element_validate'][] = [get_class($this), 'validateWebformComputed'];
  }

  /**
   * {@inheritdoc}
   */
  protected function replaceTokens(array &$element, WebformSubmissionInterface $webform_submission) {
    foreach ($element as $key => $value) {
      if (!Element::child($key) && !in_array($key, ['#markup'])) {
        $element[$key] = $this->tokenManager->replace($value, $webform_submission);
      }
    }
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
    if (!empty($element['#store'])) {
      // Get stored value if it is set.
      $value = $webform_submission->getElementData($element['#webform_key']);
      if (isset($value)) {
        return $value;
      }
    }

    return $this->processValue($element, $webform_submission);
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
      '#value' => $this->t('This is a @label value.', ['@label' => $this->getPluginLabel()]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Remove value element so that it appears under computed fieldset.
    unset($form['element']['value']);

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
        WebformComputedBaseElement::MODE_AUTO => t('Auto-detect'),
        WebformComputedBaseElement::MODE_HTML => t('HTML'),
        WebformComputedBaseElement::MODE_TEXT => t('Plain text'),
      ],
    ];
    $form['computed']['value'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'text',
      '#title' => $this->t('Computed value/markup'),
    ];
    $form['computed']['store'] = [
      '#type' => 'checkbox',
      '#return_value' => TRUE,
      '#title' => $this->t('Store value in the database'),
      '#description' => $this->t('The value will be stored in the database. As a result, it will only be recalculated when the submission is updated. This option is required when accessing the computed element through Views.'),
    ];
    $form['computed']['tokens'] = ['#access' => TRUE, '#weight' => 10] + $this->tokenManager->buildTreeLink();
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
  public function preSave(array &$element, WebformSubmissionInterface $webform_submission) {
    $key = $element['#webform_key'];
    $data = $webform_submission->getData();
    if (!empty($element['#store'])) {
      $data[$key] = $this->processValue($element, $webform_submission);
    }
    else {
      // Always unset the value.
      unset($data[$key]);
    }
    $webform_submission->setData($data);
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
   * Process computed element value.
   *
   * @param array $element
   *   An element.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   *
   * @return string
   *   Processed markup.
   */
  protected function processValue(array $element, WebformSubmissionInterface $webform_submission) {
    $class = $this->getFormElementClassDefinition();
    return $class::processValue($element, $webform_submission);
  }

}
