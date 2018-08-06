<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Element\WebformLikert as WebformLikertElement;
use Drupal\webform\Entity\WebformOptions;
use Drupal\webform\Utility\WebformElementHelper;
use Drupal\webform\Utility\WebformOptionsHelper;
use Drupal\webform\Plugin\WebformElementBase;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'likert' element.
 *
 * @WebformElement(
 *   id = "webform_likert",
 *   label = @Translation("Likert"),
 *   description = @Translation("Provides a form element where users can respond to multiple questions using a <a href=""https://en.wikipedia.org/wiki/Likert_scale"">Likert</a> scale."),
 *   category = @Translation("Options elements"),
 *   multiline = TRUE,
 *   composite = TRUE,
 * )
 */
class WebformLikert extends WebformElementBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return [
      'title' => '',
      'default_value' => [],
      // Description/Help.
      'help' => '',
      'description' => '',
      'more' => '',
      'more_title' => '',
      // Form display.
      'title_display' => '',
      'description_display' => '',
      'disabled' => FALSE,
      // Form validation.
      'required' => FALSE,
      'required_error' => '',
      // Submission display.
      'format' => $this->getItemDefaultFormat(),
      'format_html' => '',
      'format_text' => '',
      // Likert settings.
      'questions' => [],
      'questions_randomize' => FALSE,
      'answers' => [],
      'na_answer' => FALSE,
      'na_answer_value' => '',
      'na_answer_text' => $this->t('N/A'),
      // Attributes.
      'wrapper_attributes' => [],
      // iCheck settings.
      'icheck' => '',
    ] + $this->getDefaultBaseProperties();
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslatableProperties() {
    return array_merge(parent::getTranslatableProperties(), ['questions', 'answers', 'na_answer_text']);
  }

  /**
   * {@inheritdoc}
   */
  public function initialize(array &$element) {
    parent::initialize($element);

    // Set element answers.
    if (isset($element['#answers'])) {
      $element['#answers'] = WebformOptions::getElementOptions($element, '#answers');
    }

    // Process answers and set N/A.
    WebformLikertElement::processWebformLikertAnswers($element);
  }

  /**
   * {@inheritdoc}
   */
  public function formatHtmlItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);

    $format = $this->getItemFormat($element);
    switch ($format) {
      case 'raw':
        $items = [];
        foreach ($element['#questions'] as $question_key => $question_label) {
          $answer_value = (isset($value[$question_key])) ? $value[$question_key] : NULL;
          $items[$question_key] = ['#markup' => "<b>$question_key:</b> $answer_value"];
        }
        return [
          '#theme' => 'item_list',
          '#items' => $items,
        ];

      case 'table':
        // NOTE: Including inline align attributes to help style the table for
        // HTML emails.
        $header = [];
        $header['likert_question'] = [
          'data' => '',
          'align' => 'left',
          'width' => '40%',
        ];
        foreach ($element['#answers'] as $answer_value => $answer_text) {
          $header[$answer_value] = [
            'data' => $answer_text,
            'align' => 'center',
          ];
        }

        // Calculate answers width.
        $width = number_format((60 / count($element['#answers'])), 2, '.', '') . '%';

        $rows = [];
        foreach ($element['#questions'] as $question_key => $question_label) {
          $question_value = (isset($value[$question_key])) ? $value[$question_key] : NULL;
          $row = [];
          $row['likert_question'] = [
            'data' => $question_label,
            'align' => 'left',
            'width' => '40%',
          ];
          foreach ($element['#answers'] as $answer_value => $answer_text) {
            $row[$answer_value] = [
              'data' => ($question_value == $answer_value) ? ['#markup' => '&#10007;'] : '',
              'align' => 'center',
              'width' => $width,
            ];
          }
          $rows[$question_key] = $row;
        }
        return [
          '#type' => 'table',
          '#header' => $header,
          '#rows' => $rows,
          '#attributes' => [
            'class' => ['webform-likert-table'],
          ],
          '#attached' => ['library' => ['webform/webform.element.likert']],
        ];

      default:
      case 'value':
      case 'list':
        $items = [];
        foreach ($element['#questions'] as $question_key => $question_label) {
          $answer_value = (isset($value[$question_key])) ? $value[$question_key] : NULL;
          $answer_text = ($answer_value) ? WebformOptionsHelper::getOptionText($answer_value, $element['#answers']) : $this->t('[blank]');
          $items[$question_key] = ['#markup' => "<b>$question_label:</b> $answer_text"];
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
  public function formatTextItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);

    $format = $this->getItemFormat($element);
    switch ($format) {
      case 'raw':
        $list = [];
        foreach ($element['#questions'] as $question_key => $question_label) {
          $answer_value = (isset($value[$question_key])) ? $value[$question_key] : NULL;
          $list[] = "$question_key: $answer_value";
        }
        return implode(PHP_EOL, $list);

      default:
      case 'value':
      case 'table':
      case 'list':
        $list = [];
        foreach ($element['#questions'] as $question_key => $question_label) {
          $answer_value = (isset($value[$question_key])) ? $value[$question_key] : NULL;
          $answer_text = WebformOptionsHelper::getOptionText($answer_value, $element['#answers']);
          $list[] = "$question_label: $answer_text";
        }
        return implode(PHP_EOL, $list);

    }
  }

  /**
   * {@inheritdoc}
   */
  public function getExportDefaultOptions() {
    return [
      'likert_answers_format' => 'label',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildExportOptionsForm(array &$form, FormStateInterface $form_state, array $export_options) {
    parent::buildExportOptionsForm($form, $form_state, $export_options);
    if (isset($form['likert'])) {
      return;
    }

    $form['likert'] = [
      '#type' => 'details',
      '#title' => $this->t('Likert questions and answers options'),
      '#open' => TRUE,
      '#weight' => -10,
    ];
    $form['likert']['likert_answers_format'] = [
      '#type' => 'radios',
      '#title' => $this->t('Answers format'),
      '#options' => [
        'label' => $this->t('Answer labels, the human-readable value (label)'),
        'key' => $this->t('Answer keys, the raw value stored in the database (key)'),
      ],
      '#default_value' => $export_options['likert_answers_format'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildExportHeader(array $element, array $options) {
    $header = [];
    foreach ($element['#questions'] as $key => $label) {
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
    foreach ($element['#questions'] as $question_key => $question_label) {
      $answer_value = (isset($value[$question_key])) ? $value[$question_key] : NULL;
      if ($export_options['likert_answers_format'] == 'key') {
        $record[] = $answer_value;
      }
      else {
        $record[] = WebformOptionsHelper::getOptionText($answer_value, $element['#answers']);
      }
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

    // Get individual questions.
    foreach ($element['#questions'] as $question_key => $question_label) {
      $columns['element__' . $key . '__' . $question_key] = [
        'title' => ($is_title_displayed ? $title . ': ' : '') . $question_label,
        'sort' => TRUE,
        'default' => FALSE,
        'key' => $key,
        'element' => $element,
        'delta' => $question_key,
        'question_key' => $question_key,
        'plugin' => $this,
      ];
    }
    return $columns;
  }

  /**
   * {@inheritdoc}
   */
  public function formatTableColumn(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    if (isset($options['question_key'])) {
      $value = $this->getValue($element, $webform_submission);
      $question_key = $options['question_key'];
      $question_value = (isset($value[$question_key])) ? $value[$question_key] : '';
      return WebformOptionsHelper::getOptionText($question_value, $element['#answers']);
    }
    else {
      return $this->formatHtml($element, $webform_submission);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preview() {
    return parent::preview() + [
      '#questions' => [
        'q1' => $this->t('Please answer question 1?'),
        'q2' => $this->t('How about now answering question 2?'),
        'q3' => $this->t('Finally, here is question 3?'),
      ],
      '#answers' => [
        '1' => '1',
        '2' => '2',
        '3' => '3',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getTestValues(array $element, WebformInterface $webform, array $options = []) {
    $value = [];
    foreach ($element['#questions'] as $key => $question) {
      $keys = array_keys($element['#answers']);
      $value[$key] = ($options['random']) ? $keys[array_rand($keys)] : reset($keys);
    }
    return [$value];
  }

  /**
   * {@inheritdoc}
   */
  protected function getElementSelectorInputsOptions(array $element) {
    $selectors = $element['#questions'];
    foreach ($selectors as &$text) {
      $text .= ' [' . $this->t('Radios') . ']';
    }
    return $selectors;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['likert'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Likert settings'),
    ];
    $form['likert']['questions'] = [
      '#type' => 'webform_options',
      '#title' => $this->t('Questions'),
      '#label' => $this->t('question'),
      '#labels' => $this->t('questions'),
      '#required' => TRUE,
    ];
    $form['likert']['questions_randomize'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Randomize questions'),
      '#description' => $this->t('Randomizes the order of the questions when they are displayed in the webform.'),
      '#return_value' => TRUE,
    ];
    $form['likert']['answers'] = [
      '#type' => 'webform_element_options',
      '#title' => $this->t('Answers'),
      '#likert' => TRUE,
      '#required' => TRUE,
    ];
    $form['likert']['na_answer'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow N/A answer'),
      '#description' => $this->t('Allowing N/A is ideal for situations where you wish to make a likert element required, but still want to allow users to opt out of certain questions.'),
      '#return_value' => TRUE,
    ];
    $form['likert']['na_answer_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('N/A answer value'),
      '#description' => $this->t('Value stored in the database. Leave blank to store an empty string in the database.'),
      '#states' => [
        'visible' => [
          ':input[name="properties[na_answer]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['likert']['na_answer_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('N/A answer text'),
      '#description' => $this->t('Text display display on webform.'),
      '#states' => [
        'visible' => [
          ':input[name="properties[na_answer]"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[name="properties[na_answer]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    return $form;
  }

}
