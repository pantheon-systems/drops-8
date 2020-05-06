<?php

namespace Drupal\webform\Element;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\webform\Utility\WebformElementHelper;
use Drupal\webform\Utility\WebformXss;

/**
 * Provides a webform element for entering HTML using CodeMirror, TextFormat, or custom CKEditor.
 *
 * @FormElement("webform_html_editor")
 */
class WebformHtmlEditor extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#process' => [
        [$class, 'processWebformHtmlEditor'],
        [$class, 'processAjaxForm'],
        [$class, 'processGroup'],
      ],
      '#pre_render' => [
        [$class, 'preRenderGroup'],
      ],
      '#theme_wrappers' => ['form_element'],
      '#format' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    $element += ['#default_value' => ''];
    if ($input === FALSE) {
      return [
        'value' => $element['#default_value'],
      ];
    }
    else {
      // Get value from TextFormat element.
      if (isset($input['value']['value'])) {
        $input['value'] = $input['value']['value'];
      }
      return $input;
    }
  }

  /**
   * Prepares a #type 'webform_html_editor' render element for input.html.twig.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   *
   * @return array
   *   The HTML Editor which can be a CodeMirror element, TextFormat, or
   *   Textarea which is transformed into a custom HTML Editor.
   */
  public static function processWebformHtmlEditor(array $element) {
    $element['#tree'] = TRUE;

    // Define value element.
    $element += ['value' => []];

    // Copy properties to value element.
    $properties = ['#title', '#required', '#attributes', '#default_value'];
    $element['value'] += array_intersect_key($element, array_combine($properties, $properties));

    // Hide title.
    $element['value']['#title_display'] = 'invisible';

    // Don't display inline form error messages.
    $element['#error_no_message'] = TRUE;

    // Add validate callback.
    $element += ['#element_validate' => []];
    array_unshift($element['#element_validate'], [get_called_class(), 'validateWebformHtmlEditor']);

    // If HTML disabled and no #format is specified return simple CodeMirror
    // HTML editor.
    $disabled = \Drupal::config('webform.settings')->get('html_editor.disabled') ?: ($element['#format'] === FALSE);
    if ($disabled) {
      $element['value'] += [
        '#type' => 'webform_codemirror',
        '#mode' => 'html',
      ];
      return $element;
    }

    // If #format or 'webform.settings.html_editor.element_format' is defined return
    // a 'text_format' element.
    $format = $element['#format'] ?: \Drupal::config('webform.settings')->get('html_editor.element_format');
    if ($format) {
      $element['value'] += [
        '#type' => 'text_format',
        '#format' => $format,
        '#allowed_formats' => [$format],
      ];
      WebformElementHelper::fixStatesWrapper($element);
      return $element;
    }

    // Else use textarea with completely custom HTML Editor.
    $element['value'] += [
      '#type' => 'textarea',
    ];
    $element['value']['#attributes']['class'][] = 'js-html-editor';

    $element['#attached']['library'][] = 'webform/webform.element.html_editor';
    $element['#attached']['drupalSettings']['webform']['html_editor']['allowedContent'] = static::getAllowedContent();

    /** @var \Drupal\webform\WebformLibrariesManagerInterface $libraries_manager */
    $libraries_manager = \Drupal::service('webform.libraries_manager');
    $libraries = $libraries_manager->getLibraries(TRUE);
    $element['#attached']['drupalSettings']['webform']['html_editor']['plugins'] = [];
    foreach ($libraries as $library_name => $library) {
      if (strpos($library_name, 'ckeditor.') === FALSE) {
        continue;
      }

      $plugin_name = str_replace('ckeditor.', '', $library_name);
      $plugin_path = $library['plugin_path'];
      $plugin_url = $library['plugin_url'];
      if (file_exists($plugin_path)) {
        $element['#attached']['drupalSettings']['webform']['html_editor']['plugins'][$plugin_name] = base_path() . $plugin_path;
      }
      else {
        $element['#attached']['drupalSettings']['webform']['html_editor']['plugins'][$plugin_name] = $plugin_url;
      }
    }

    if (\Drupal::moduleHandler()->moduleExists('imce') && \Drupal\imce\Imce::access()) {
      $element['#attached']['library'][] = 'imce/drupal.imce.ckeditor';
      $element['#attached']['drupalSettings']['webform']['html_editor']['ImceImageIcon'] = file_create_url(drupal_get_path('module', 'imce') . '/js/plugins/ckeditor/icons/imceimage.png');
    }

    if (!empty($element['#states'])) {
      webform_process_states($element, '#wrapper_attributes');
    }

    return $element;
  }

  /**
   * Webform element validation handler for #type 'webform_html_editor'.
   */
  public static function validateWebformHtmlEditor(&$element, FormStateInterface $form_state, &$complete_form) {
    $value = $element['#value']['value'];
    if (is_array($value)) {
      // Get value from TextFormat element.
      $value = $value['value'];
    }
    else {
      $value = trim($value);
    }

    $element['#value'] = $value;
    $form_state->setValueForElement($element, $value);
  }

  /**
   * Get allowed content.
   *
   * @return array
   *   Allowed content (tags) for CKEditor.
   */
  public static function getAllowedContent() {
    $allowed_tags = \Drupal::config('webform.settings')->get('element.allowed_tags');
    switch ($allowed_tags) {
      case 'admin':
        $allowed_tags = Xss::getAdminTagList();
        break;

      case 'html':
        $allowed_tags = Xss::getHtmlTagList();
        break;

      default:
        $allowed_tags = preg_split('/ +/', $allowed_tags);
        break;
    }
    foreach ($allowed_tags as $index => $allowed_tag) {
      $allowed_tags[$index] .= '(*)[*]{*}';
    }
    return implode('; ', $allowed_tags);
  }

  /**
   * Get allowed tags.
   *
   * @return array
   *   Allowed tags.
   */
  public static function getAllowedTags() {
    $allowed_tags = \Drupal::config('webform.settings')->get('element.allowed_tags');
    switch ($allowed_tags) {
      case 'admin':
        return WebformXss::getAdminTagList();

      case 'html':
        return WebformXss::getHtmlTagList();

      default:
        return preg_split('/ +/', $allowed_tags);
    }
  }

  /**
   * Runs HTML markup through (optional) text format.
   *
   * @param string $text
   *   The text to be filtered.
   * @param array $options
   *   HTML markup options.
   *
   * @return array
   *   Render array containing 'processed_text'.
   *
   * @see \Drupal\webform\Plugin\WebformHandler\EmailWebformHandler::getMessage
   */
  public static function checkMarkup($text, array $options = []) {
    $options += [
      'tidy' => \Drupal::config('webform.settings')->get('html_editor.tidy'),
    ];
    // Remove <p> tags around a single line of text, which creates minor
    // margin issues.
    if ($options['tidy']) {
      if (substr_count($text, '<p>') === 1 && preg_match('#^\s*<p>.*</p>\s*$#m', $text)) {
        $text = preg_replace('#^\s*<p>#', '', $text);
        $text = preg_replace('#</p>\s*$#', '', $text);
      }
    }

    if ($format = \Drupal::config('webform.settings')->get('html_editor.element_format')) {
      return [
        '#type' => 'processed_text',
        '#text' => $text,
        '#format' => $format,
      ];
    }
    else {
      return [
        '#theme' => 'webform_html_editor_markup',
        '#markup' => $text,
        '#allowed_tags' => static::getAllowedTags(),
      ];
    }
  }

  /**
   * Strip dis-allowed HTML tags from HTML text.
   *
   * @param string $text
   *   HTML text.
   *
   * @return string
   *   HTML text with dis-allowed HTML tags removed.
   */
  public static function stripTags($text) {
    $allowed_tags = '<' . implode('><', static::getAllowedTags()) . '>';
    return strip_tags($text, $allowed_tags);
  }

}
