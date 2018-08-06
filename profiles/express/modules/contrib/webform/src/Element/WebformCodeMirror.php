<?php

namespace Drupal\webform\Element;

use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Render\Element\Textarea;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Utility\WebformYaml;

/**
 * Provides a webform element for HTML, YAML, or Plain text using CodeMirror.
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
      '#cols' => 60,
      '#rows' => 5,
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
        $element['#default_value'] = WebformYaml::tidy(Yaml::encode($element['#default_value']));
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

    // Set validation.
    if (isset($element['#element_validate'])) {
      $element['#element_validate'] = array_merge([[get_called_class(), 'validateWebformCodeMirror']], $element['#element_validate']);
    }
    else {
      $element['#element_validate'] = [[get_called_class(), 'validateWebformCodeMirror']];
    }

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
    if ($errors = static::getErrors($element, $form_state, $complete_form)) {
      $build = [
        'title' => [
          '#markup' => t('%title is not valid.', ['%title' => (isset($element['#title']) ? $element['#title'] : t('YAML'))]),
        ],
        'errors' => [
          '#theme' => 'item_list',
          '#items' => $errors,
        ],
      ];
      $form_state->setError($element, \Drupal::service('renderer')->render($build));
    }

    if ($element['#mode'] == 'yaml' && (isset($element['#default_value']) && is_array($element['#default_value']))) {
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

  /**
   * Get validation errors.
   */
  protected static function getErrors(&$element, FormStateInterface $form_state, &$complete_form) {
    if (!empty($element['#skip_validation'])) {
      return NULL;
    }

    switch ($element['#mode']) {
      case 'html':
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

      case 'yaml':
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

      case 'twig':
        try {
          $build = [
            '#type' => 'inline_template',
            '#template' => $element['#value'],
            '#context' => [],
          ];
          \Drupal::service('renderer')->renderPlain($build);
          return NULL;
        }
        catch (\Exception $exception) {
          return [$exception->getMessage()];
        }

      default:
        return NULL;
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

}
