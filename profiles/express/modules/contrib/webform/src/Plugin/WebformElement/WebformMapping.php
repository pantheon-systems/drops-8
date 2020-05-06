<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element as RenderElement;
use Drupal\webform\Element\WebformHtmlEditor;
use Drupal\webform\Element\WebformMapping as WebformMappingElement;
use Drupal\webform\Entity\WebformOptions;
use Drupal\webform\Utility\WebformElementHelper;
use Drupal\webform\Utility\WebformOptionsHelper;
use Drupal\webform\Plugin\WebformElementBase;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'mapping' element.
 *
 * @WebformElement(
 *   id = "webform_mapping",
 *   label = @Translation("Mapping"),
 *   description = @Translation("Provides a form element where source values can mapped to destination values."),
 *   category = @Translation("Advanced elements"),
 *   multiline = TRUE,
 *   composite = TRUE,
 * )
 */
class WebformMapping extends WebformElementBase {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    return [
      'title' => '',
      'default_value' => [],
      // Description/Help.
      'help' => '',
      'help_title' => '',
      'description' => '',
      'more' => '',
      'more_title' => '',
      // Form display.
      'title_display' => '',
      'description_display' => '',
      'help_display' => '',
      'disabled' => FALSE,
      // Form validation.
      'required' => FALSE,
      'required_error' => '',
      // Submission display.
      'format' => $this->getItemDefaultFormat(),
      'format_html' => '',
      'format_text' => '',
      'format_attributes' => [],
      // Mapping settings.
      'arrow' => '→',
      'source' => [],
      'source__description_display' => 'description',
      'source__title' => 'Source',
      'destination' => [],
      'destination__type' => 'select',
      'destination__title' => 'Destination',
      'destination__description' => '',
      // Attributes.
      'wrapper_attributes' => [],
      'label_attributes' => [],
    ] + $this->defineDefaultBaseProperties();
  }

  /**
   * {@inheritdoc}
   */
  protected function defineTranslatableProperties() {
    return array_merge(parent::defineTranslatableProperties(), ['source', 'destination']);
  }

  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function initialize(array &$element) {
    parent::initialize($element);

    // Set element answers.
    if (isset($element['#source'])) {
      $element['#source'] = WebformOptions::getElementOptions($element, '#source');
    }
    if (isset($element['#destination'])) {
      $element['#destination'] = WebformOptions::getElementOptions($element, '#destination');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepare($element, $webform_submission);
    if (isset($element['#destination__description'])) {
      $element['#destination__description'] = WebformHtmlEditor::checkMarkup($element['#destination__description']);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function formatHtmlItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);

    $element += [
      '#destination' => [],
      '#arrow' => '→',
    ];

    $arrow = htmlentities($element['#arrow']);
    $format = $this->getItemFormat($element);
    switch ($format) {
      case 'raw':
        $items = [];
        foreach ($element['#source'] as $source_key => $source_title) {
          $destination_value = (isset($value[$source_key])) ? $value[$source_key] : NULL;
          $items[$source_key] = ['#markup' => "$source_key $arrow $destination_value"];
        }
        return [
          '#theme' => 'item_list',
          '#items' => $items,
        ];

      case 'table':

        $element += [
          '#source__title' => $this->t('Source'),
          '#destination__title' => $this->t('Destination'),
        ];

        $header = [
          ['data' => $element['#source__title'], 'width' => '50%'],
          ['data' => $element['#destination__title'], 'width' => '50%'],
        ];

        $rows = [];
        foreach ($element['#source'] as $source_key => $source_text) {
          list($source_title) = explode(WebformOptionsHelper::DESCRIPTION_DELIMITER, $source_text);
          $destination_value = (isset($value[$source_key])) ? $value[$source_key] : NULL;
          $destination_title = ($destination_value) ? WebformOptionsHelper::getOptionText($destination_value, $element['#destination']) : $this->t('[blank]');
          $rows[$source_key] = [
            $source_title,
            ['data' => ['#markup' => "$arrow $destination_title"]],
          ];
        }

        return [
          '#type' => 'table',
          '#header' => $header,
          '#rows' => $rows,
          '#attributes' => [
            'class' => ['webform-mapping-table'],
          ],
        ];

      default:
      case 'value':
      case 'list':
        $items = [];
        foreach ($element['#source'] as $source_key => $source_text) {
          list($source_title) = explode(WebformOptionsHelper::DESCRIPTION_DELIMITER, $source_text);
          $destination_value = (isset($value[$source_key])) ? $value[$source_key] : NULL;
          $destination_title = ($destination_value) ? WebformOptionsHelper::getOptionText($destination_value, $element['#destination']) : $this->t('[blank]');
          $items[$source_key] = ['#markup' => "$source_title $arrow $destination_title"];
        }
        return [
          '#theme' => 'item_list',
          '#items' => $items,
        ];

    }
  }

  /**
   * {@inheritdoc}
   */
  protected function formatTextItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    if ($this->hasValue($element, $webform_submission, $options)) {
      return '';
    }

    $value = $this->getValue($element, $webform_submission, $options);

    $element += [
      '#destination' => [],
      '#arrow' => '→',
    ];

    $arrow = $element['#arrow'];
    $format = $this->getItemFormat($element);
    switch ($format) {
      case 'raw':
        $list = [];
        foreach ($element['#source'] as $source_key => $source_title) {
          $destination_value = (isset($value[$source_key])) ? $value[$source_key] : NULL;
          $list[$source_key] = "$source_key $arrow $destination_value";
        }
        return implode(PHP_EOL, $list);

      default:
      case 'value':
      case 'table':
      case 'list':
        $list = [];
        foreach ($element['#source'] as $source_key => $source_text) {
          list($source_title) = explode(WebformOptionsHelper::DESCRIPTION_DELIMITER, $source_text);
          $destination_value = (isset($value[$source_key])) ? $value[$source_key] : NULL;
          $destination_title = ($destination_value) ? WebformOptionsHelper::getOptionText($destination_value, $element['#destination']) : $this->t('[blank]');
          $list[] = "$source_title $arrow $destination_title";
        }
        return implode(PHP_EOL, $list);

    }
  }

  /**
   * {@inheritdoc}
   */
  public function preview() {
    return parent::preview() + [
      '#source' => [
        'one' => $this->t('One'),
        'two' => $this->t('Two'),
        'three' => $this->t('Three'),
      ],
      '#destination' => [
        'four' => $this->t('Four'),
        'five' => $this->t('Five'),
        'six' => $this->t('Six'),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildExportHeader(array $element, array $options) {
    $header = [];
    foreach ($element['#source'] as $key => $label) {
      $header[] = ($options['header_format'] == 'key') ? $key : $label;
    }
    return $this->prefixExportHeader($header, $element, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function buildExportRecord(array $element, WebformSubmissionInterface $webform_submission, array $export_options) {
    $value = $this->getValue($element, $webform_submission);

    $record = [];
    foreach ($element['#source'] as $source_key => $source_title) {
      $record[] = (isset($value[$source_key])) ? $value[$source_key] : NULL;
    }
    return $record;
  }

  /**
   * {@inheritdoc}
   */
  public function getItemDefaultFormat() {
    return 'list';
  }

  /**
   * {@inheritdoc}
   */
  public function getItemFormats() {
    return parent::getItemFormats() + [
      'list' => $this->t('List'),
      'table' => $this->t('Table'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getTableColumn(array $element) {
    $key = $element['#webform_key'];
    $title = $element['#title'] ?: $key;

    $is_title_displayed = WebformElementHelper::isTitleDisplayed($element);

    // Get the main composite element, which can't be sorted.
    $columns = parent::getTableColumn($element);
    $columns['element__' . $key]['sort'] = FALSE;

    // Get individual sources.
    foreach ($element['#source'] as $source_key => $source_title) {
      $columns['element__' . $key . '__' . $source_key] = [
        'title' => ($is_title_displayed ? $title . ': ' : '') . $source_title,
        'sort' => TRUE,
        'default' => FALSE,
        'key' => $key,
        'element' => $element,
        'delta' => $source_key,
        'source_key' => $source_key,
        'plugin' => $this,
      ];
    }
    return $columns;
  }

  /**
   * {@inheritdoc}
   */
  public function formatTableColumn(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    if (isset($options['source_key'])) {
      $source_key = $options['source_key'];
      $value = $this->getValue($element, $webform_submission);
      $question_value = (isset($value[$source_key])) ? $value[$source_key] : '';
      return (isset($element['#destination'])) ? WebformOptionsHelper::getOptionText($question_value, $element['#destination']) : NULL;
    }
    else {
      return $this->formatHtml($element, $webform_submission);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTestValues(array $element, WebformInterface $webform, array $options = []) {
    /** @var \Drupal\webform\WebformSubmissionGenerateInterface $generate */
    $generate = \Drupal::service('webform_submission.generate');

    $form_state = new FormState();
    $form_completed = [];
    $element += [
      '#name' => (isset($element['#webform_key'])) ? $element['#webform_key'] : '',
      '#required' => FALSE,
    ];
    $element = WebformMappingElement::processWebformMapping($element, $form_state, $form_completed);

    $values = [];
    for ($i = 1; $i <= 3; $i++) {
      $value = [];
      foreach (RenderElement::children($element['table']) as $source_key) {
        $value[$source_key] = $generate->getTestValue($webform, $source_key, $element['table'][$source_key][$source_key], $options);
      }
      $values[] = $value;
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  protected function getElementSelectorInputsOptions(array $element) {
    return $element['#source'];
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['mapping'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Mapping settings'),
    ];

    $form['mapping']['arrow'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Arrow character'),
      '#size' => 10,
    ];

    $form['mapping']['mapping_source'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Mapping source'),
    ];
    $form['mapping']['mapping_source']['source__title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Source title'),
    ];
    $form['mapping']['mapping_source']['source'] = [
      '#type' => 'webform_element_options',
      '#title' => $this->t('Source options'),
      '#label' => $this->t('source'),
      '#labels' => $this->t('sources'),
      '#required' => TRUE,
      '#options_description' => TRUE,
    ];
    $form['mapping']['mapping_source']['source__description_display'] = [
      '#title' => $this->t('Source description display'),
      '#type' => 'select',
      '#options' => [
        'description' => $this->t('Description'),
        'help' => $this->t('Help text'),
      ],
    ];
    $form['mapping']['mapping_destination'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Mapping destination'),
    ];
    $form['mapping']['mapping_destination']['destination__type'] = [
      '#type' => 'select',
      '#title' => $this->t('Destination type'),
      '#options' => [
        'select' => $this->t('Select'),
        'webform_select_other' => $this->t('Select other'),
        'textfield' => $this->t('Textfield'),
        'email' => $this->t('Email'),
        'tel' => $this->t('Telephone'),
        'url' => $this->t('Url'),
        'webform_email_multiple' => $this->t('Email multiple'),
      ],
      '#other__description' => $this->t('Please enter an element type.'),
    ];
    $form['mapping']['mapping_destination']['destination__title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Destination title'),
    ];
    $form['mapping']['mapping_destination']['destination__description'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Destination description'),
    ];
    $form['mapping']['mapping_destination']['destination'] = [
      '#type' => 'webform_element_options',
      '#title' => $this->t('Destination options'),
      '#label' => $this->t('destination'),
      '#labels' => $this->t('destinations'),
      '#states' => [
        'visible' => [
          [':input[name="properties[destination__type][select]"]' => ['value' => 'select']],
          'or',
          [':input[name="properties[destination__type][select]"]' => ['value' => 'webform_select_other']],
        ],
      ],
    ];
    return $form;
  }

}
