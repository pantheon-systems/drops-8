<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Component\Render\HtmlEscapedText;
use Drupal\Core\Form\FormStateInterface;

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
      // General settings.
      'description' => '',
      'default_value' => '',
      // Form display.
      'title_display' => '',
      'description_display' => '',
      'field_prefix' => '',
      'field_suffix' => '',
      'placeholder' => '',
      'rows' => '',
      // Form validation.
      'required' => FALSE,
      'required_error' => '',
      'unique' => FALSE,
      'unique_error' => '',
      'counter_type' => '',
      'counter_maximum' => '',
      'counter_message' => '',
      // Attributes.
      'wrapper_attributes' => [],
      'attributes' => [],
      // Submission display.
      'format' => $this->getItemDefaultFormat(),
    ] + $this->getDefaultBaseProperties();
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
  public function formatHtmlItem(array $element, $value, array $options = []) {
    return [
      '#markup' => nl2br(new HtmlEscapedText($value)),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['element']['default_value']['#type'] = 'textarea';
    return $form;
  }

}
