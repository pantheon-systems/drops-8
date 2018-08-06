<?php

namespace Drupal\webform\Element;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

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
        [$class, 'processAjaxForm'],
        [$class, 'processGroup'],
        [$class, 'preRenderWebformHtmlEditor'],
      ],
      '#pre_render' => [
        [$class, 'preRenderGroup'],
      ],
      '#element_validate' => [
        [$class, 'validateWebformHtmlEditor'],
      ],
      '#theme_wrappers' => ['form_element'],
      '#format' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    if ($input === FALSE) {
      if (!isset($element['#default_value'])) {
        $element['#default_value'] = '';
      }
      return [
        'value' => $element['#default_value'],
      ];
    }
    else {
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
  public static function preRenderWebformHtmlEditor(array $element) {
    $element['#tree'] = TRUE;

    $element['value']['#title'] = $element['#title'];
    $element['value']['#title_display'] = 'invisible';
    if (isset($element['#required'])) {
      $element['value']['#required'] = $element['#required'];
    }

    // If HTML disabled and no #format is specified return simple CodeMirror
    // HTML editor.
    $disabled = \Drupal::config('webform.settings')->get('html_editor.disabled') ?: ($element['#format'] ===  FALSE);
    if ($disabled) {
      $element['value'] += [
        '#type' => 'webform_codemirror',
        '#mode' => 'html',
        '#value' => empty($element['#value']) ? NULL : $element['#value']['value'],
      ];
      return $element;
    }

    // If #format or 'webform.settings.html_editor.format' is defined return
    // a 'text_format' element.
    $format = $element['#format'] ?: \Drupal::config('webform.settings')->get('html_editor.format');
    if ($format) {
      $element['value'] += [
        '#type' => 'text_format',
        '#format' => $format,
        '#allowed_formats' => [$format],
        '#value' => empty($element['#value']) ? NULL : $element['#value']['value'],
      ];
      return $element;
    }

    // Else use textarea with completely custom HTML Editor.
    $element['value'] += [
      '#type' => 'textarea',
      '#attributes' => ['class' => ['js-html-editor']],
      '#value' => empty($element['#value']) ? NULL : $element['#value']['value'],
    ];

    $element['#attached']['library'][] = 'webform/webform.element.html_editor';
    $element['#attached']['drupalSettings']['webform']['html_editor']['allowedContent'] = static::getAllowedContent();

    $base_path = base_path();
    /** @var \Drupal\webform\WebformLibrariesManagerInterface $libaries_manager */
    $libaries_manager = \Drupal::service('webform.libraries_manager');
    $libraries = $libaries_manager->getLibraries(TRUE);
    $element['#attached']['drupalSettings']['webform']['html_editor']['plugins'] = [];
    foreach ($libraries as $library_name => $library) {
      if (strpos($library_name, 'ckeditor.') === 0) {
        $plugin_version = $library['version'];
        $plugin_name = str_replace('ckeditor.', '', $library_name);
        if (file_exists("libraries/$library_name")) {
          $element['#attached']['drupalSettings']['webform']['html_editor']['plugins'][$plugin_name] = "{$base_path}libraries/{$library_name}/";
        }
        else {
          $element['#attached']['drupalSettings']['webform']['html_editor']['plugins'][$plugin_name] = "https://cdn.rawgit.com/ckeditor/ckeditor-dev/$plugin_version/plugins/$plugin_name/";
        }
      }
    }

    if (\Drupal::moduleHandler()->moduleExists('imce') && \Drupal\imce\Imce::access()) {
      $element['#attached']['library'][] = 'imce/drupal.imce.ckeditor';
      $element['#attached']['drupalSettings']['webform']['html_editor']['ImceImageIcon'] = file_create_url(drupal_get_path('module', 'imce') . '/js/plugins/ckeditor/icons/imceimage.png');
    }

    if (isset($element['#states'])) {
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
      $form_state->setValueForElement($element, $value['value']);
    }
    else {
      $form_state->setValueForElement($element, trim($value));
    }
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
        $allowed_tags = Xss::getAdminTagList();
        // <label>, <fieldset>, <legend> is missing from allowed tags.
        $allowed_tags[] = 'label';
        $allowed_tags[] = 'fieldset';
        $allowed_tags[] = 'legend';
        return $allowed_tags;

      case 'html':
        return Xss::getHtmlTagList();

      default:
        return preg_split('/ +/', $allowed_tags);
    }
  }

  /**
   * Runs HTML markup through (optional) text format.
   *
   * @param string $text
   *   The text to be filtered.
   * @param bool $render
   *   If TRUE the HTML markup should be rendered. If FALSE a renderable array
   *   containing #markup or processed_text is returned. Defaults to FALSE.
   *
   * @return \Drupal\Component\Render\MarkupInterface|string|array
   *   The filtered text, text, or a render array containing 'processed_text'.
   *
   * @see \Drupal\webform\Plugin\WebformHandler\EmailWebformHandler::getMessage
   */
  public static function checkMarkup($text, $render = FALSE) {
    // Remove <p> tags around a single line of text, which creates minor
    // margin issues.
    if (\Drupal::config('webform.settings')->get('html_editor.tidy')) {
      if (substr_count($text, '<p>') === 1 && preg_match('#^\s*<p>.*</p>\s*$#m', $text)) {
        $text = preg_replace('#^\s*<p>#', '', $text);
        $text = preg_replace('#</p>\s*$#', '', $text);
      }
    }

    if ($format = \Drupal::config('webform.settings')->get('html_editor.format')) {
      if ($render) {
        return check_markup($text, $format);
      }
      else {
        return [
          '#type' => 'processed_text',
          '#text' => $text,
          '#format' => $format,
        ];
      }
    }
    else {
      if ($render) {
        return $text;
      }
      else {
        return [
          '#markup' => $text,
          '#allowed_tags' => static::getAllowedTags(),
        ];
      }
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
