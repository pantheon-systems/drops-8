<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\webform\Plugin\WebformElementBase;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'table' element.
 *
 * @WebformElement(
 *   id = "table",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Table.php/class/Table",
 *   label = @Translation("Table"),
 *   description = @Translation("Provides an element to render a table."),
 *   hidden = TRUE,
 * )
 */
class Table extends WebformElementBase {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    return [
      // Table settings.
      'header' => [],
      'empty' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function defineTranslatableProperties() {
    return array_merge(parent::defineTranslatableProperties(), ['header']);
  }

  /****************************************************************************/

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
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
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
  protected function format($type, array &$element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $item_function = 'format' . $type . 'Item';
    return $this->$item_function($element, $webform_submission, $options);
  }

  /**
   * {@inheritdoc}
   */
  protected function formatHtmlItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $rows = [];
    foreach ($element as $row_key => $row_element) {
      if (Element::property($row_key)) {
        continue;
      }

      $element[$row_key] = [];
      foreach ($row_element as $column_key => $column_element) {
        if (Element::property($column_key)) {
          continue;
        }

        // Get column element plugin and get formatted HTML value.
        $column_element_plugin = $this->elementManager->getElementInstance($column_element);
        $column_value = $column_element_plugin->format('html', $column_element, $webform_submission, $options);

        // If column value is empty see if we can use #markup.
        if (empty($column_value) && isset($column_element['#markup'])) {
          $column_value = $column_element['#markup'];
        }

        if (is_array($column_value)) {
          $rows[$row_key][$column_key] = ['data' => $column_value];
        }
        else {
          $rows[$row_key][$column_key] = ['data' => ['#markup' => $column_value]];
        }
      }
    }
    return $rows + $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function formatTextItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    // Render the HTML table.
    $build = $this->formatHtml($element, $webform_submission, $options);
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
      $lines[0] .= PHP_EOL . str_repeat('-', mb_strlen($lines[0]));
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
