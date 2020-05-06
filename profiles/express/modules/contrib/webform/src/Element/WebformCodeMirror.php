<?php

namespace Drupal\webform\Element;

use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Render\Element\Textarea;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\Twig\WebformTwigExtension;
use Drupal\webform\Utility\WebformYaml;

/**
 * Provides a webform element for using CodeMirror.
 *
 * Known Issues/Feature Requests:
 *
 * - Mixed Twig Mode #3292
 *   https://github.com/codemirror/CodeMirror/issues/3292
 *
 * @FormElement("webform_codemirror")
 */
class WebformCodeMirror extends Textarea {

  /**
   * An associative array of supported CodeMirror modes by type and mime-type.
   *
   * @var array
   */
  protected static $modes = [
    'css' => 'text/css',
    'html' => 'text/html',
    'htmlmixed' => 'htmlmixed',
    'javascript' => 'text/javascript',
    'text' => 'text/plain',
    'yaml' => 'text/x-yaml',
    'php' => 'text/x-php',
    'twig' => 'twig',
  ];

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#mode' => 'text',
      '#skip_validation' => FALSE,
      '#decode_value' => FALSE,
      '#cols' => 60,
      '#rows' => 5,
      '#wrap' => TRUE,
      '#resizable' => 'vertical',
      '#process' => [
        [$class, 'processWebformCodeMirror'],
        [$class, 'processAjaxForm'],
        [$class, 'processGroup'],
      ],
      '#pre_render' => [
        [$class, 'preRenderWebformCodeMirror'],
        [$class, 'preRenderGroup'],
      ],
      '#theme' => 'textarea',
      '#theme_wrappers' => ['form_element'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input === FALSE && $element['#mode'] == 'yaml' && isset($element['#default_value'])) {
      // Convert associative array in default value to YAML.
      if (is_array($element['#default_value'])) {
        $element['#default_value'] = WebformYaml::encode($element['#default_value']);
      }
      // Convert empty YAML into an empty string.
      if ($element['#default_value'] == '{  }') {
        $element['#default_value'] = '';
      }
      return $element['#default_value'];
    }
    return NULL;
  }

  /**
   * Processes a 'webform_codemirror' element.
   */
  public static function processWebformCodeMirror(&$element, FormStateInterface $form_state, &$complete_form) {
    // Check that mode is defined and valid, if not default to (plain) text.
    if (empty($element['#mode']) || !isset(static::$modes[$element['#mode']])) {
      $element['#mode'] = 'text';
    }

    // Check edit Twig template permission and complete disable editing.
    if ($element['#mode'] == 'twig') {
      if (!WebformTwigExtension::hasEditTwigAccess()) {
        $element['#disable'] = TRUE;
        $element['#attributes']['disabled'] = 'disabled';
        $element['#field_prefix'] = [
          '#type' => 'webform_message',
          '#message_type' => 'warning',
          '#message_message' => t("Only webform administrators and user's assigned the 'Edit webform Twig templates' permission are allowed to edit this Twig template."),
        ];
      }
    }

    // Set wrap off.
    if (empty($element['#wrap'])) {
      $element['#attributes']['wrap'] = 'off';
    }

    // Add validate callback.
    $element += ['#element_validate' => []];
    array_unshift($element['#element_validate'], [get_called_class(), 'validateWebformCodeMirror']);

    return $element;
  }

  /**
   * Prepares a #type 'webform_code' render element for theme_element().
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   *   Properties used: #title, #value, #description, #size, #maxlength,
   *   #placeholder, #required, #attributes.
   *
   * @return array
   *   The $element with prepared variables ready for theme_element().
   */
  public static function preRenderWebformCodeMirror(array $element) {
    static::setAttributes($element, ['js-webform-codemirror', 'webform-codemirror', $element['#mode']]);
    $element['#attributes']['data-webform-codemirror-mode'] = static::getMode($element['#mode']);
    $element['#attached']['library'][] = 'webform/webform.element.codemirror.' . $element['#mode'];
    return $element;
  }

  /**
   * Webform element validation handler for #type 'webform_codemirror'.
   */
  public static function validateWebformCodeMirror(&$element, FormStateInterface $form_state, &$complete_form) {
    // If element is disabled then use the #default_value.
    if (!empty($element['#disable'])) {
      $element['#value'] = $element['#default_value'];
      $form_state->setValueForElement($element, $element['#default_value']);
    }
    $errors = static::getErrors($element, $form_state, $complete_form);
    if ($errors) {
      $build = [
        'title' => [
          '#markup' => t('%title is not valid.', ['%title' => static::getTitle($element)]),
        ],
        'errors' => [
          '#theme' => 'item_list',
          '#items' => $errors,
        ],
      ];
      $form_state->setError($element, \Drupal::service('renderer')->render($build));
    }
    else {
      // If editing YAML and #default_value is an array, decode #value.
      if ($element['#mode'] == 'yaml'
        && (isset($element['#default_value']) && is_array($element['#default_value']) || $element['#decode_value'])
      ) {
        // Handle rare case where single array value is not parsed correctly.
        if (preg_match('/^- (.*?)\s*$/', $element['#value'], $match)) {
          $value = [$match[1]];
        }
        else {
          $value = $element['#value'] ? Yaml::decode($element['#value']) : [];
        }
        $form_state->setValueForElement($element, $value);
      }
    }
  }

  /**
   * Get validation errors.
   */
  protected static function getErrors(&$element, FormStateInterface $form_state, &$complete_form) {
    if (!empty($element['#skip_validation'])) {
      return NULL;
    }

    switch ($element['#mode']) {
      case 'html':
        return static::validateHtml($element, $form_state, $complete_form);

      case 'yaml':
        return static::validateYaml($element, $form_state, $complete_form);

      case 'twig':
        return static::validateTwig($element, $form_state, $complete_form);

      default:
        return NULL;
    }
  }

  /**
   * Get an element's title.
   *
   * @param array $element
   *   An element.
   *
   * @return string
   *   The element's title.
   */
  protected static function getTitle(array $element) {
    if (isset($element['#title'])) {
      return $element['#title'];
    }

    switch ($element['#mode']) {
      case 'html':
        return t('HTML');

      case 'yaml':
        return t('YAML');

      case 'twig':
        return t('Twig');

      default:
        return t('Code');
    }
  }

  /**
   * Get the CodeMirror mode for specified type.
   *
   * @param string $mode
   *   Mode (text, html, or yaml).
   *
   * @return string
   *   The CodeMirror mode (aka mime type).
   */
  public static function getMode($mode) {
    return (isset(static::$modes[$mode])) ? static::$modes[$mode] : static::$modes['text'];
  }

  /****************************************************************************/
  // Language/markup validation callback.
  /****************************************************************************/

  /**
   * Validate HTML.
   *
   * @param array $element
   *   The form element whose value is being validated.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array|null
   *   An array of error messages.
   */
  protected static function validateHtml($element, FormStateInterface $form_state, $complete_form) {
    // @see: http://stackoverflow.com/questions/3167074/which-function-in-php-validate-if-the-string-is-valid-html
    // @see: http://stackoverflow.com/questions/5030392/x-html-validator-in-php
    libxml_use_internal_errors(TRUE);
    if (simplexml_load_string('<fragment>' . $element['#value'] . '</fragment>')) {
      return NULL;
    }

    $errors = libxml_get_errors();
    libxml_clear_errors();
    if (!$errors) {
      return NULL;
    }

    $messages = [];
    foreach ($errors as $error) {
      $messages[] = $error->message;
    }
    return $messages;
  }

  /**
   * Validate Twig.
   *
   * @param array $element
   *   The form element whose value is being validated.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array|null
   *   An array of error messages.
   */
  protected static function validateTwig($element, FormStateInterface $form_state, $complete_form) {
    $template = $element['#value'];
    $form_object = $form_state->getFormObject();
    try {
      // If form object has ::getWebform method validate Twig template
      // using a temporary webform submission context.
      if (method_exists($form_object, 'getWebform')) {
        /** @var \Drupal\webform\WebformInterface $webform */
        $webform = $form_object->getWebform();

        // Get a temporary webform submission.
        /** @var \Drupal\webform\WebformSubmissionGenerateInterface $webform_submission_generate */
        $webform_submission_generate = \Drupal::service('webform_submission.generate');
        $values = [
          // Set sid to 0 to prevent validation errors.
          'sid' => 0,
          'webform_id' => $webform->id(),
          'data' => $webform_submission_generate->getData($webform),
        ];
        $webform_submission = WebformSubmission::create($values);
        $build = WebformTwigExtension::buildTwigTemplate($webform_submission, $template, []);
      }
      else {
        $build = [
          '#type' => 'inline_template',
          '#template' => $element['#value'],
          '#context' => [],
        ];
      }
      \Drupal::service('renderer')->renderPlain($build);
      return NULL;
    }
    catch (\Exception $exception) {
      return [$exception->getMessage()];
    }
  }

  /**
   * Validate YAML.
   *
   * @param array $element
   *   The form element whose value is being validated.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array|null
   *   An array of error messages.
   */
  protected static function validateYaml($element, FormStateInterface $form_state, $complete_form) {
    try {
      $value = $element['#value'];
      $data = Yaml::decode($value);
      if (!is_array($data) && $value) {
        throw new \Exception(t('YAML must contain an associative array of elements.'));
      }
      return NULL;
    }
    catch (\Exception $exception) {
      return [$exception->getMessage()];
    }
  }

}
