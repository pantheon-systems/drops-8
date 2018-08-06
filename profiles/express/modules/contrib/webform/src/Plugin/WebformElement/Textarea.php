<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Component\Render\HtmlEscapedText;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'textarea' element.
 *
 * @WebformElement(
 *   id = "textarea",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Textarea.php/class/Textarea",
 *   label = @Translation("Textarea"),
 *   description = @Translation("Provides a form element for input of multiple-line text."),
 *   category = @Translation("Basic elements"),
 *   multiline = TRUE,
 * )
 */
class Textarea extends TextBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return [
      'title' => '',
      'default_value' => '',
      // Description/Help.
      'help' => '',
      'description' => '',
      'more' => '',
      'more_title' => '',
      // Form display.
      'title_display' => '',
      'description_display' => '',
      'field_prefix' => '',
      'field_suffix' => '',
      'placeholder' => '',
      'disabled' => FALSE,
      'rows' => '',
      'maxlength' => '',
      // Form validation.
      'required' => FALSE,
      'required_error' => '',
      'unique' => FALSE,
      'unique_user' => FALSE,
      'unique_entity' => FALSE,
      'unique_error' => '',
      'counter_type' => '',
      'counter_maximum' => '',
      'counter_message' => '',
      // Attributes.
      'wrapper_attributes' => [],
      'attributes' => [],
      // Submission display.
      'format' => $this->getItemDefaultFormat(),
      'format_html' => '',
      'format_text' => '',
      'format_items' => $this->getItemsDefaultFormat(),
      'format_items_html' => '',
      'format_items_text' => '',
    ] + parent::getDefaultProperties() + $this->getDefaultMultipleProperties();
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslatableProperties() {
    return array_merge(parent::getTranslatableProperties(), ['counter_message']);
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepare($element, $webform_submission);

    // @todo Remove once Drupal 8.4.x+ is a dependency.
    // Textarea Form API element now supports #maxlength attribute
    // @see https://www.drupal.org/node/2887280
    if (!empty($element['#maxlength'])) {
      $element['#attributes']['maxlength'] = $element['#maxlength'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function formatHtmlItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);

    return [
      '#markup' => nl2br(new HtmlEscapedText($value)),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function preview() {
    return parent::preview() + [
      '#rows' => 2,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['default']['default_value']['#type'] = 'textarea';
    $form['default']['default_value']['#rows'] = 3;

    $form['form']['placeholder']['#type'] = 'textarea';
    $form['form']['placeholder']['#rows'] = 3;

    return $form;
  }

}
