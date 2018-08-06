<?php

namespace Drupal\webform\Element;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Textarea;

/**
 * Provides a webform element for entering HTML using CKEditor or CodeMirror.
 *
 * @FormElement("webform_html_editor")
 */
class WebformHtmlEditor extends Textarea {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    $info = parent::getInfo();
    $info['#pre_render'][] = [$class, 'preRenderWebformHtmlEditor'];
    $info['#element_validate'][] = [$class, 'validateWebformHtmlEditor'];
    return $info;
  }

  /**
   * Prepares a #type 'html_editor' render element for input.html.twig.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   *   Properties used: #title, #value, #return_value, #description, #required,
   *   #attributes, #checked.
   *
   * @return array
   *   The $element with prepared variables ready for input.html.twig.
   */
  public static function preRenderWebformHtmlEditor(array $element) {
    if (\Drupal::config('webform.settings')->get('ui.html_editor_disabled')) {
      $element['#mode'] = 'html';
      $element = WebformCodeMirror::preRenderWebformCodeMirror($element);
    }
    else {
      $element['#attached']['library'][] = 'webform/webform.element.html_editor';
      $element['#attached']['drupalSettings']['webform']['html_editor']['allowedContent'] = self::getAllowedContent();

      if (\Drupal::moduleHandler()->moduleExists('imce') && \Drupal\imce\Imce::access()) {
        $element['#attached']['library'][] = 'imce/drupal.imce.ckeditor';
        $element['#attached']['drupalSettings']['webform']['html_editor']['ImceImageIcon'] = file_create_url(drupal_get_path('module', 'imce') . '/js/plugins/ckeditor/icons/imceimage.png');
      }
    }
    return $element;
  }

  /**
   * Webform element validation handler for #type 'webform_html_editor'.
   */
  public static function validateWebformHtmlEditor(&$element, FormStateInterface $form_state, &$complete_form) {
    $value = $element['#value'];
    $form_state->setValueForElement($element, trim($value));
  }

  /**
   * Get allowed content.
   *
   * @return array
   *   Allowed content (tags) for CKEditor.
   */
  public static function getAllowedContent() {
    $allowed_tags = \Drupal::config('webform.settings')->get('elements.allowed_tags');
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

}
