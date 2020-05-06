<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailFormatHelper;
use Drupal\filter\Entity\FilterFormat;
use Drupal\user\Entity\User;
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
 *   states_wrapper = TRUE,
 *   composite = TRUE,
 *   multiline = TRUE,
 * )
 */
class TextFormat extends WebformElementBase {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    $properties = [
      'default_value' => [],
      // Text format settings.
      'allowed_formats' => [],
      'hide_help' => FALSE,
    ] + parent::defineDefaultProperties();
    unset(
      $properties['disabled'],
      $properties['attributes'],
      $properties['wrapper_attributes'],
      $properties['title_display'],
      $properties['description_display'],
      $properties['field_prefix'],
      $properties['field_suffix'],
      $properties['format_items'],
      $properties['format_items_html'],
      $properties['format_items_text']
    );
    return $properties;
  }

  /****************************************************************************/

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
    $this->setElementDefaultCallback($element, 'process');
    $element['#process'][] = [get_class($this), 'process'];
    $element['#after_build'] = [[get_class($this), 'afterBuild']];
    $element['#attached']['library'][] = 'webform/webform.element.text_format';
  }

  /**
   * Fix text format #more property.
   *
   * @param array $element
   *   The form element to process. See main class documentation for properties.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The form element.
   *
   * @see template_preprocess_text_format_wrapper()
   * @see text-format-wrapper.html.twig
   */
  public static function process(&$element, FormStateInterface $form_state, &$complete_form) {
    if (!empty($element['#more'])) {
      // Process #more and append to #description.
      $variables = ['element' => $element, 'description' => []];
      _webform_preprocess_element($variables);

      // Update element description.
      $element['#description'] = $variables['description'];

      // Remove attributes which causes conflicts.
      unset($element['#description']['attributes']);

      // Unset old #more attributes.
      unset($element['value']['#more']);
      unset($element['#more']);
    }

    return $element;
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
   * Set an elements #states and flexbox wrapper.
   *
   * @param array $element
   *   An element.
   */
  protected function prepareWrapper(array &$element) {
    $element['#pre_render'][] = [get_class($this), 'preRenderFixTextFormatStates'];
    parent::prepareWrapper($element);
  }

  /**
   * Fix state .js-webform-states-hidden wrapper for text format element.
   *
   * Adds .js-webform-states-hidden to wrapper to text format because
   * text format's wrapper is hard-coded.
   *
   * @param array $element
   *   An element.
   *
   * @return array
   *   An element with .js-webform-states-hidden added to the #prefix
   *   and #suffix.
   *
   * @see \Drupal\webform\Utility\WebformElementHelper::fixStatesWrapper
   * @see text-format-wrapper.html.twig
   */
  public static function preRenderFixTextFormatStates(array $element) {
    if (isset($element['#attributes']) && isset($element['#attributes']['class'])) {
      $index = array_search('js-webform-states-hidden', $element['#attributes']['class']);
      if ($index !== FALSE) {
        unset($element['#attributes']['class'][$index]);
        $element += ['#prefix' => '', '#suffix' => ''];
        $element['#prefix'] = '<div class="js-webform-text-format-hidden">';
        $element['#suffix'] = $element['#suffix'] . '</div>';
      }
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
  protected function formatHtmlItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);

    $format = (isset($value['format'])) ? $value['format'] : $this->getItemFormat($element);
    $value = (isset($value['value'])) ? $value['value'] : $value;
    switch ($format) {
      case 'raw':
        return $value;

      case 'value':
        $default_format = filter_default_format(User::load($webform_submission->getOwnerId()));
        return check_markup($value, $default_format);

      default:
        return check_markup($value, $format);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function formatTextItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);

    $format = (isset($value['format'])) ? $value['format'] : $this->getItemFormat($element);
    $value = (isset($value['value'])) ? $value['value'] : $value;
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
  public function buildExportRecord(array $element, WebformSubmissionInterface $webform_submission, array $export_options) {
    $element['#format_items'] = $export_options['multiple_delimiter'];
    return [$this->formatHtml($element, $webform_submission, $export_options)];
  }

  /**
   * {@inheritdoc}
   */
  protected function getElementSelectorInputsOptions(array $element) {
    $title = $this->getAdminLabel($element);
    return [
      'value' => $title . ' [' . $this->t('Textarea') . ']',
      'format' => $title . ' [' . $this->t('Select') . ']',
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
   * {@inheritdoc}
   */
  public function postSave(array &$element, WebformSubmissionInterface $webform_submission, $update = TRUE) {
    $webform = $webform_submission->getWebform();
    if ($webform->isResultsDisabled()) {
      return;
    }

    // Get current value and original value for this element.
    $key = $element['#webform_key'];

    $data = $webform_submission->getData();
    $value = (isset($data[$key]) && isset($data[$key]['value'])) ? $data[$key]['value'] : '';
    $uuids = _webform_parse_file_uuids($value);

    if ($update) {
      $original_data = $webform_submission->getOriginalData();
      $original_value = isset($original_data[$key]) ? $original_data[$key]['value'] : '';
      $original_uuids = _webform_parse_file_uuids($original_value);

      // Detect file usages that should be incremented.
      $added_files = array_diff($uuids, $original_uuids);
      _webform_record_file_usage($added_files, $webform_submission->getEntityTypeId(), $webform_submission->id());

      // Detect file usages that should be decremented.
      $removed_files = array_diff($original_uuids, $uuids);
      _webform_delete_file_usage($removed_files, $webform_submission->getEntityTypeId(), $webform_submission->id(), 1);
    }
    else {
      _webform_record_file_usage($uuids, $webform_submission->getEntityTypeId(), $webform_submission->id());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postDelete(array &$element, WebformSubmissionInterface $webform_submission) {
    $key = $element['#webform_key'];
    $value = $webform_submission->getElementData($key);
    $uuids = _webform_parse_file_uuids($value['value']);
    _webform_delete_file_usage($uuids, $webform_submission->getEntityTypeId(), $webform_submission->id(), 0);
  }

  /**
   * Check if composite element exists.
   *
   * @return bool
   *   TRUE if composite element exists.
   */
  public function hasCompositeElement(array $element, $key) {
    $elements = $this->getCompositeElements();
    return (isset($elements[$key])) ? TRUE : FALSE;
  }

}
