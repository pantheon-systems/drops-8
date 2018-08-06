<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailFormatHelper;
use Drupal\filter\Entity\FilterFormat;
use Drupal\webform\Plugin\WebformElementBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'text_format' element.
 *
 * @WebformElement(
 *   id = "text_format",
 *   api = "https://api.drupal.org/api/drupal/core!modules!filter!src!Element!TextFormat.php/class/TextFormat",
 *   label = @Translation("Text format"),
 *   description = @Translation("Provides a text format form element."),
 *   category = @Translation("Advanced elements"),
 *   composite = TRUE,
 *   multiline = TRUE,
 * )
 */
class TextFormat extends WebformElementBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    $properties = parent::getDefaultProperties() + [
      // Text format settings.
      'allowed_formats' => [],
      'hide_help' => FALSE,
    ];
    unset(
      $properties['disabled'],
      $properties['attributes'],
      $properties['wrapper_attributes'],
      $properties['title_display'],
      $properties['description_display'],
      $properties['field_prefix'],
      $properties['field_suffix'],
      $properties['help']
    );
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isInput(array $element) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepare($element, $webform_submission);
    $element['#after_build'] = [[get_class($this), 'afterBuild']];
    $element['#attached']['library'][] = 'webform/webform.element.text_format';
  }

  /**
   * Alter the 'text_format' element after it has been built.
   *
   * @param array $element
   *   An element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The element.
   */
  public static function afterBuild(array $element, FormStateInterface $form_state) {
    if (empty($element['format'])) {
      return $element;
    }

    // Hide tips.
    if (!empty($element['#hide_help']) && isset($element['format']['help'])) {
      $element['format']['help']['#attributes']['style'] = 'display: none';
    }
    else {
      // Display tips in a modal.
      $element['format']['help']['about']['#attributes']['class'][] = 'use-ajax';
      $element['format']['help']['about']['#attributes'] += [
        'data-dialog-type' => 'dialog',
        'data-dialog-options' => Json::encode([
          'dialogClass' => 'webform-text-format-help-dialog',
          'width' => 800,
        ]),
      ];
    }

    // Hide filter format if the select menu and help is hidden.
    if (!empty($element['#hide_help']) &&
      isset($element['format']['format']['#access']) && $element['format']['format']['#access'] === FALSE) {
      // Can't hide the format via #access but we can use CSS.
      $element['format']['#attributes']['style'] = 'display: none';
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function setDefaultValue(array &$element) {
    if (isset($element['#default_value']) && is_array($element['#default_value'])) {
      if (isset($element['#default_value']['format'])) {
        $element['#format'] = $element['#default_value']['format'];
      }
      if (isset($element['#default_value']['value'])) {
        $element['#default_value'] = $element['#default_value']['value'];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function formatHtmlItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);

    $value = (isset($value['value'])) ? $value['value'] : $value;
    $format = (isset($value['format'])) ? $value['format'] : $this->getItemFormat($element);
    switch ($format) {
      case 'raw':
        return $value;

      case 'value':
      default:
        return check_markup($value, $format);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function formatTextItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);

    $format = (isset($value['format'])) ? $value['format'] : $this->getItemFormat($element);
    switch ($format) {
      case 'raw':
        return $value;

      case 'value':
      default:
        $html = $this->formatHtml($element, $webform_submission);
        // Convert any HTML to plain-text.
        $html = MailFormatHelper::htmlToText($html);
        // Wrap the mail body for sending.
        $html = MailFormatHelper::wrapMail($html);
        return $html;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getItemDefaultFormat() {
    return (function_exists('filter_default_format')) ? filter_default_format() : parent::getItemDefaultFormat();
  }

  /**
   * {@inheritdoc}
   */
  public function getItemFormats() {
    $formats = parent::getItemFormats();
    $filters = (class_exists('\Drupal\filter\Entity\FilterFormat')) ? FilterFormat::loadMultiple() : [];
    foreach ($filters as $filter) {
      $formats[$filter->id()] = $filter->label();
    }
    return $formats;
  }

  /**
   * {@inheritdoc}
   */
  protected function getElementSelectorInputsOptions(array $element) {
    $title = $this->getAdminLabel($element);
    return [
      'value' => $title . ' [' . t('Textarea') . ']',
      'format' => $title . ' [' . t('Select') . ']',
    ];
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
    $form = parent::form($form, $form_state);

    $filters = FilterFormat::loadMultiple();
    $options = [];
    foreach ($filters as $filter) {
      $options[$filter->id()] = $filter->label();
    }
    $form['text_format'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Text format settings'),
    ];
    $form['text_format']['allowed_formats'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Allowed formats'),
      '#description' => $this->t('Please check the formats that are available for this element. Leave blank to allow all available formats.'),
      '#options' => $options,
    ];
    $form['text_format']['hide_help'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide help'),
      '#description' => $this->t("If checked, the 'About text formats' link will be hidden."),
      '#return_value' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $allowed_formats = $form_state->getValue('allowed_formats');
    $allowed_formats = array_filter($allowed_formats);
    $form_state->setValue('allowed_formats', $allowed_formats);
    parent::validateConfigurationForm($form, $form_state);
  }

  /**
   * Get composite element.
   *
   * @return array
   *   A composite sub-elements.
   */
  public function hasCompositeElement(array $element, $key) {
    $elements = $this->getCompositeElements();
    return (isset($elements[$key])) ? TRUE : FALSE;
  }

}
