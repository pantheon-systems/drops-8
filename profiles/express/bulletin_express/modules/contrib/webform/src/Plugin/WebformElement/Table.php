<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\webform\WebformElementBase;
use Drupal\webform\WebformInterface;
use Drupal\Component\Utility\Unicode;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'table' element.
 *
 * @WebformElement(
 *   id = "table",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Table.php/class/Table",
 *   label = @Translation("Table"),
 *   description = @Translation("Provides an element to render a table."),
 *   category = @Translation("Markup elements"),
 * )
 */
class Table extends WebformElementBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return [
      // Table settings.
      'header' => [],
      'empty' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslatableProperties() {
    return array_merge(parent::getTranslatableProperties(), ['header']);
  }

  /**
   * {@inheritdoc}
   */
  public function isInput(array $element) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isContainer(array $element) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission) {
    parent::prepare($element, $webform_submission);

    // Add .js-form.wrapper to fix #states handling.
    $element['#attributes']['class'][] = 'js-form-wrapper';

    // Disable #tree for table element. Webforms do not support the #tree
    // property.
    $element['#tree'] = FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getItemDefaultFormat() {
    return 'table';
  }

  /**
   * {@inheritdoc}
   */
  public function getItemFormats() {
    return ['table'];
  }

  /**
   * {@inheritdoc}
   */
  public function getTestValues(array $element, WebformInterface $webform, array $options = []) {
    // Containers should never have values and therefore should never have
    // a test value.
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function formatHtmlItem(array $element, $value, array $options = []) {
    // Undo webform submission elements and convert rows back into a simple
    // render array.
    $rows = [];
    foreach ($value as $row_key => $row_element) {
      $element[$row_key] = [];
      foreach ($row_element['#value'] as $column_key => $column_element) {
        if (isset($column_element['#value'])) {
          if (is_string($column_element['#value']) || $column_element['#value'] instanceof TranslatableMarkup) {
            $value = ['#markup' => $column_element['#value']];
          }
          else {
            $value = $column_element['#value'];
          }
        }
        elseif (isset($column_element['#markup'])) {
          $value = ['#markup' => $column_element['#markup']];
        }
        else {
          $value = '';
        }
        $rows[$row_key][$column_key] = ['data' => $value];
      }
    }
    return $rows + $element;
  }

  /**
   * {@inheritdoc}
   */
  public function formatTextItem(array $element, $value, array $options = []) {
    // Render the HTML table.
    $build = $this->formatHtml($element, $value, $options);
    $html = \Drupal::service('renderer')->renderPlain($build);

    // Convert table in pipe delimited plain text.
    $html = preg_replace('#\s*</td>\s*<td[^>]*>\s*#', ' | ', $html);
    $html = preg_replace('#\s*</th>\s*<th[^>]*>\s*#', ' | ', $html);
    $html = preg_replace('#^\s+#m', '', $html);
    $html = preg_replace('#\s+$#m', '', $html);
    $html = preg_replace('#\n+#s', PHP_EOL, $html);
    $html = strip_tags($html);

    // Remove blank links from text.
    // From: http://stackoverflow.com/questions/709669/how-do-i-remove-blank-lines-from-text-in-php
    $html = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", PHP_EOL, $html);

    // Add divider between (optional) header.
    if (!empty($element['#header'])) {
      $lines = explode(PHP_EOL, trim($html));
      $lines[0] .= PHP_EOL . str_repeat('-', Unicode::strlen($lines[0]));
      $html = implode(PHP_EOL, $lines);
    }

    return $html;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['table'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Table settings'),
    ];
    $form['table']['header'] = [
      '#title' => $this->t('Header (YAML)'),
      '#type' => 'webform_codemirror',
      '#mode' => 'yaml',
    ];
    $form['table']['empty'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Empty text'),
      '#description' => $this->t('Text to display when no rows are present.'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getElementSelectorOptions(array $element) {
    return [];
  }

}
