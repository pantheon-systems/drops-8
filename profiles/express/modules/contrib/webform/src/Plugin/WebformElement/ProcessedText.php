<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailFormatHelper;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'processed_text' element.
 *
 * @WebformElement(
 *   id = "processed_text",
 *   default_key = "processed_text",
 *   label = @Translation("Advanced HTML/Text"),
 *   category = @Translation("Markup elements"),
 *   description = @Translation("Provides an element to render advanced HTML markup and processed text."),
 *   states_wrapper = TRUE,
 * )
 */
class ProcessedText extends WebformMarkupBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    if (function_exists('filter_formats')) {
      // Works around filter_default_format() throwing fatal error when
      // user is not allowed to use any filter formats.
      // @see filter_default_format.
      $formats = filter_formats(\Drupal::currentUser());
      $format = reset($formats);
      $default_format = $format ? $format->id() : filter_fallback_format();
    }
    else {
      $default_format = '';
    }

    return [
      'wrapper_attributes' => [],
      // Markup settings.
      'text' => '',
      'format' => $default_format ,
    ] + parent::getDefaultProperties();
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslatableProperties() {
    return array_merge(parent::getTranslatableProperties(), ['text']);
  }

  /**
   * {@inheritdoc}
   */
  public function buildText(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    // Copy to element so that we can render it without altering the actual
    // $element.
    $render_element = $element;
    $html = (string) \Drupal::service('renderer')->renderPlain($render_element);
    $element['#markup'] = MailFormatHelper::htmlToText($html);

    // Must remove #type, #text, and #format.
    unset($element['#type'], $element['#text'], $element['#format']);

    return parent::buildText($element, $webform_submission, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function preview() {
    return (\Drupal::moduleHandler()->moduleExists('filter')) ? parent::preview() : [];
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    // Issue #2741877 Nested modals don't work: when using CKEditor in a
    // modal, then clicking the image button opens another modal,
    // which closes the original modal.
    // @todo Remove the below workaround once this issue is resolved.
    if (!$form_state->getUserInput() && \Drupal::currentUser()->hasPermission('administer webform')) {
      drupal_set_message($this->t('Processed text element can not be opened within a modal. Please see <a href="https://www.drupal.org/node/2741877">Issue #2741877: Nested modals don\'t work</a>.'), 'warning');
    }
    $form = parent::form($form, $form_state);

    // Remove 'Submission display' since the 'format' property is handled by
    // the text format element.
    unset($form['display']);

    $form['markup']['#title'] = $this->t('Processed text settings');
    $form['markup']['text'] = [
      '#type' => 'text_format',
      '#format' => '',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function setConfigurationFormDefaultValue(array &$form, array &$element_properties, array &$property_element, $property_name) {
    // Apply element.format to the text (text_format) element and unset it.
    if ($property_name == 'text') {
      $property_element['#format'] = $element_properties['format'];
      unset($element_properties['format']);
    }

    parent::setConfigurationFormDefaultValue($form, $element_properties, $property_element, $property_name);
  }

  /**
   * {@inheritdoc}
   */
  protected function getConfigurationFormProperty(array &$properties, $property_name, $property_value, array $element) {
    if ($property_name == 'text') {
      $properties['text'] = $property_value['value'];
      $properties['format'] = $property_value['format'];
    }
    else {
      parent::getConfigurationFormProperty($properties, $property_name, $property_value, $element);
    }
  }

}
