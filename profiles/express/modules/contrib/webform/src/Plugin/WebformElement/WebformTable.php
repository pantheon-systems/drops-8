<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\webform\Plugin\WebformElementBase;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'webform_table' element.
 *
 * @WebformElement(
 *   id = "webform_table",
 *   label = @Translation("Table"),
 *   description = @Translation("Provides an element to render a table."),
 *   category = @Translation("Containers"),
 * )
 */
class WebformTable extends WebformElementBase {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    $properties = parent::defineDefaultProperties() + [
      'title' => [],
      'header' => [],
      'caption' => '',
      'sticky' => FALSE,
      'prefix_children' => TRUE,
    ];
    unset(
      $properties['format_items'],
      $properties['format_items_html'],
      $properties['format_items_text'],
      $properties['unique'],
      $properties['unique_user'],
      $properties['unique_entity'],
      $properties['unique_error'],
      $properties['disabled'],
      $properties['prepopulate']
    );
    return $properties;
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
    $this->prepareTableHeader($element);
    parent::prepare($element, $webform_submission);
  }

  /**
   * Prepare webform talble header for rendering.
   *
   * @param array &$element
   *   A webform table element.
   */
  protected function prepareTableHeader(array &$element) {
    // Convert webform table header into a simple table header.
    if (!isset($element['#header'])) {
      return;
    }

    foreach ($element['#header'] as $index => $header) {
      if (is_array($header) && isset($header['title'])) {
        $attributes = (isset($header['attributes'])) ? $header['attributes'] : [];
        $element['#header'][$index] = [
          'data' => ['#markup' => $header['title']],
        ] + $attributes;
      }
    }
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
    return [
      'table' => $this->t('Table'),
      'fieldset' => $this->t('Fieldset'),
      'details' => $this->t('Details (opened)'),
      'details-closed' => $this->t('Details (closed)'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getTestValues(array $element, WebformInterface $webform, array $options = []) {
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
    $format = $this->getItemFormat($element);
    switch ($format) {
      case 'details':
      case 'details-closed':
      case 'fieldset':
        $element['#type'] = 'container';
        break;

      case 'table':
      default:
        $this->prepareTableHeader($element);
        // Switch submission display back to a Drupal table.
        $element['#type'] = 'table';
        unset($element['#states']);
        break;
    }

    // Build each individual table row.
    foreach ($element as $row_key => $row_element) {
      if (Element::child($row_key)) {
        $row_element_plugin = $this->elementManager->getElementInstance($row_element);
        $element[$row_key] = $row_element_plugin->buildHtml($row_element, $webform_submission, $options);
      }
    }
    return $element;

  }

  /**
   * {@inheritdoc}
   */
  protected function formatTextItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    /** @var \Drupal\webform\WebformSubmissionViewBuilderInterface $view_builder */
    $view_builder = \Drupal::entityTypeManager()->getViewBuilder('webform_submission');
    $children = $view_builder->buildElements($element, $webform_submission, $options, 'text');
    if (empty($children)) {
      return [];
    }

    $build = ['#prefix' => PHP_EOL];
    if (!empty($element['#title'])) {
      $build['title'] = [
        '#markup' => $element['#title'],
        '#suffix' => PHP_EOL,
      ];
      $build['divider'] = [
        '#markup' => str_repeat('-', mb_strlen($element['#title'])),
        '#suffix' => PHP_EOL,
      ];
    }
    $build['children'] = $children;
    return $build;
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
      '#title' => $this->t('Table header'),
      '#type' => 'webform_multiple',
      '#header' => [
        'title' => ['data' => $this->t('Header title'), 'width' => '50%'],
        'attributes' => ['data' => $this->t('Header attributes'), 'width' => '50%'],
      ],
      '#element' => [
        'title' => [
          '#type' => 'textfield',
          '#title' => $this->t('Header title'),
          '#error_no_message' => TRUE,
        ],
        'attributes' => [
          '#type' => 'webform_codemirror',
          '#mode' => 'yaml',
          '#title' => $this->t('Header attributes (YAML)'),
          '#decode_value' => TRUE,
          '#error_no_message' => TRUE,
        ],
      ],
    ];

    $form['table']['caption'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Caption for the table'),
      '#description' => $this->t('A title semantically associated with your table for increased accessibility.'),
      '#maxlength' => 255,
    ];

    $form['table']['sticky'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Drupal style "sticky" table headers (Javascript)'),
      '#description' => $this->t("If checked, the table's header will remain visible as the user scrolls through the table."),
      '#return_value' => TRUE,
    ];

    $form['table']['prefix_children'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Automatically prefix and increment the table's rows and elements"),
      '#description' => $this->t("If checked, all rows and elements within the table will be prefixed with the table's element key and a  incremented numeric value. (i.e. table_01_first_name)"),
      '#return_value' => TRUE,
    ];

    // Update #required label.
    $form['validation']['required_container']['required']['#title'] .= ' <em>' . $this->t('(Display purposes only)') . '</em>';
    $form['validation']['required_container']['required']['#description'] = $this->t('If checked, adds required indicator to the title, if visible. To require individual elements, also tick "Required" under each elements settings.');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function preview() {
    return [
      '#type' => 'table',
      '#header' => [
        $this->t('Header 1'),
        $this->t('Header 2'),
      ],
      '#rows' => [
        ['Row 1 - Col 1', 'Row 1 - Col 2'],
        ['Row 2 - Col 1', 'Row 2 - Col 2'],
        ['Row 3 - Col 1', 'Row 3 - Col 2'],
      ],
    ];
  }

}
