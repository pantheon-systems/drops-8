<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElementBase;
use Drupal\webform\Utility\WebformElementHelper;
use Drupal\webform\Utility\WebformHtmlHelper;
use Drupal\webform\Utility\WebformTextHelper;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a base 'text' (field) class.
 */
abstract class TextBase extends WebformElementBase {

  use TextBaseTrait;

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    return [
      'readonly' => FALSE,
      'size' => NULL,
      'minlength' => NULL,
      'maxlength' => NULL,
      'placeholder' => '',
      'autocomplete' => 'on',
      'pattern' => '',
      'pattern_error' => '',
    ] + parent::defineDefaultProperties();
  }

  /**
   * {@inheritdoc}
   */
  protected function defineTranslatableProperties() {
    return array_merge(parent::defineTranslatableProperties(), ['counter_minimum_message', 'counter_maximum_message', 'pattern_error']);
  }

  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepare($element, $webform_submission);

    // Counter.
    if (!empty($element['#counter_type'])
      && (!empty($element['#counter_minimum']) || !empty($element['#counter_maximum']))
      && $this->librariesManager->isIncluded('jquery.textcounter')) {

      // Apply character min/max to min/max length.
      if ($element['#counter_type'] == 'character') {
        if (!empty($element['#counter_minimum'])) {
          $element['#minlength'] = $element['#counter_minimum'];
        }
        if (!empty($element['#counter_maximum'])) {
          $element['#maxlength'] = $element['#counter_maximum'];
        }
      }

      // Set 'data-counter-*' attributes using '#counter_*' properties.
      $data_attributes = [
        'counter_type',
        'counter_minimum',
        'counter_minimum_message',
        'counter_maximum',
        'counter_maximum_message',
      ];
      foreach ($data_attributes as $data_attribute) {
        if (!empty($element['#' . $data_attribute])) {
          $element['#attributes']['data-' . str_replace('_', '-', $data_attribute)] = $element['#' . $data_attribute];
        }
      }

      $element['#attributes']['class'][] = 'js-webform-counter';
      $element['#attributes']['class'][] = 'webform-counter';
      $element['#attached']['library'][] = 'webform/webform.element.counter';

      $element['#element_validate'][] = [get_class($this), 'validateCounter'];
    }

    // Input mask.
    if (!empty($element['#input_mask']) && $this->librariesManager->isIncluded('jquery.inputmask')) {
      // See if the element mask is JSON by looking for 'name':, else assume it
      // is a mask pattern.
      $input_mask = $element['#input_mask'];
      if (preg_match("/^'[^']+'\s*:/", $input_mask)) {
        $element['#attributes']['data-inputmask'] = $input_mask;
      }
      else {
        $element['#attributes']['data-inputmask-mask'] = $input_mask;
      }
      // Set input mask #pattern.
      $input_masks = $this->getInputMasks();
      if (isset($input_masks[$input_mask])
        && isset($input_masks[$input_mask]['pattern']) &&
        empty($element['#pattern'])) {
        $element['#pattern'] = $input_masks[$input_mask]['pattern'];
      }

      $element['#attributes']['class'][] = 'js-webform-input-mask';
      $element['#attached']['library'][] = 'webform/webform.element.inputmask';
      $element['#element_validate'][] = [get_called_class(), 'validateInputMask'];
    }

    // Input hiding.
    if (!empty($element['#input_hide'])) {
      $element['#attributes']['class'][] = 'js-webform-input-hide';
      $element['#attached']['library'][] = 'webform/webform.element.inputhide';
    }

    // Pattern validation.
    // This override core's pattern validation to support unicode
    // and a custom error message.
    if (isset($element['#pattern'])) {
      $element['#attributes']['pattern'] = $element['#pattern'];
      $element['#element_validate'][] = [get_called_class(), 'validatePattern'];

      // Set pattern error message using #pattern_error.
      // @see Drupal.behaviors.webformRequiredError
      // @see webform.form.js
      if (!empty($element['#pattern_error'])) {
        $element['#attributes']['data-webform-pattern-error'] = WebformHtmlHelper::toPlainText($element['#pattern_error']);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Input mask.
    $form['form']['input_mask'] = [
      '#type' => 'webform_select_other',
      '#title' => $this->t('Input masks'),
      '#description' => $this->t('An <a href=":href">inputmask</a> helps the user with the element by ensuring a predefined format.', [':href' => 'https://github.com/RobinHerbots/jquery.inputmask']),
      '#other__option_label' => $this->t('Custom…'),
      '#other__placeholder' => $this->t('Enter input mask…'),
      '#other__description' => $this->t('(9 = numeric; a = alphabetical; * = alphanumeric)'),
      '#empty_option' => $this->t('- None -'),
      '#options' => $this->getInputMaskOptions(),
    ];
    if ($this->librariesManager->isExcluded('jquery.inputmask')) {
      $form['form']['input_mask']['#access'] = FALSE;
    }

    // Input hiding.
    $form['form']['input_hide'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Input hiding'),
      '#description' => $this->t('Hide the input of the element when the input is not being focused.'),
      '#return_value' => TRUE,
    ];

    // Pattern.
    $form['validation']['pattern'] = [
      '#type' => 'webform_checkbox_value',
      '#title' => $this->t('Pattern'),
      '#description' => $this->t('A <a href=":href">regular expression</a> that the element\'s value is checked against.', [':href' => 'http://www.w3schools.com/js/js_regexp.asp']),
      '#value__title' => $this->t('Pattern regular expression'),
      '#value__description' => $this->t('Enter a <a href=":href">regular expression</a> that the element\'s value should match.', [':href' => 'http://www.w3schools.com/js/js_regexp.asp']),
      '#value__maxlength' => NULL,
    ];
    $form['validation']['pattern_error'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Pattern message'),
      '#description' => $this->t('If set, this message will be used when a pattern is not matched, instead of the default "@message" message.', ['@message' => t('%name field is not in the right format.')]),
      '#states' => [
        'visible' => [
          ':input[name="properties[pattern][checkbox]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Counter.
    $form['validation'] += $this->buildCounterForm();

    if (isset($form['form']['maxlength'])) {
      $form['form']['maxlength']['#description'] .= ' ' . $this->t('If character counter is enabled, maxlength will automatically be set to the count maximum.');
      $form['form']['maxlength']['#states'] = [
        'invisible' => [
          ':input[name="properties[counter_type]"]' => ['value' => 'character'],
        ],
      ];
    }

    return $form;
  }

  /**
   * Form API callback. Validate (word/character) counter.
   */
  public static function validateCounter(array &$element, FormStateInterface $form_state) {
    $value = $element['#value'];
    if ($value === '') {
      return;
    }

    $type = $element['#counter_type'];
    $max = (!empty($element['#counter_maximum'])) ? $element['#counter_maximum'] : NULL;
    $min = (!empty($element['#counter_minimum'])) ? $element['#counter_minimum'] : NULL;

    // Display error.
    // @see \Drupal\Core\Form\FormValidator::performRequiredValidation
    $t_args = [
      '@type' => ($type == 'character') ? t('characters') : t('words'),
      '@name' => $element['#title'],
      '%max' => $max,
      '%min' => $min,
    ];

    // Get character/word count.
    if ($type === 'character') {
      $length = mb_strlen($value);
      $t_args['%length'] = $length;
    }
    // Validate word count.
    elseif ($type === 'word') {
      $length = WebformTextHelper::wordCount($value);
      $t_args['%length'] = $length;
    }

    // Validate character/word count.
    if ($max && $length > $max) {
      $form_state->setError($element, t('@name cannot be longer than %max @type but is currently %length @type long.', $t_args));
    }
    elseif ($min && $length < $min) {
      $form_state->setError($element, t('@name must be longer than %min @type but is currently %length @type long.', $t_args));
    }
  }

  /**
   * Form API callback. Validate input mask and display required error message.
   *
   * Makes sure a required element's value doesn't include the default
   * input mask as the submitted value.
   *
   * Applies only to the currency input mask.
   */
  public static function validateInputMask(&$element, FormStateInterface $form_state, &$complete_form) {
    // Set required error when input mask is submitted.
    if (!empty($element['#required'])
      && static::isDefaultInputMask($element, $element['#value'])) {
      WebformElementHelper::setRequiredError($element, $form_state);
    }
  }

  /**
   * Check if an element's value is the input mask's default value.
   *
   * @param array $element
   *   An element.
   * @param string $value
   *   A value.
   *
   * @return bool
   *   TRUE if an element's value is the input mask's default value.
   */
  public static function isDefaultInputMask(array $element, $value) {
    if (empty($element['#input_mask']) || $value === '') {
      return FALSE;
    }

    $input_mask = $element['#input_mask'];
    $input_masks = [
      "'alias': 'currency'" => '$ 0.00',
    ];
    return (isset($input_masks[$input_mask]) && $input_masks[$input_mask] === $value) ? TRUE : FALSE;
  }

  /**
   * Form API callback. Validate unicode pattern and display a custom error.
   *
   * @see https://www.drupal.org/project/drupal/issues/2633550
   */
  public static function validatePattern(&$element, FormStateInterface $form_state, &$complete_form) {
    if ($element['#value'] !== '') {
      // PHP: Convert JavaScript-escaped Unicode characters to PCRE
      // escape sequence format.
      // @see https://bytefreaks.net/programming-2/php-programming-2/php-convert-javascript-escaped-unicode-characters-to-html-hex-references˚
      $pcre_pattern = preg_replace('/\\\\u([a-fA-F0-9]{4})/', '\\x{\\1}', $element['#pattern']);
      $pattern = '{^(?:' . $pcre_pattern . ')$}u';
      if (!preg_match($pattern, $element['#value'])) {
        if (!empty($element['#pattern_error'])) {
          $form_state->setError($element, WebformHtmlHelper::toHtmlMarkup($element['#pattern_error']));
        }
        else {
          $form_state->setError($element, t('%name field is not in the right format.', ['%name' => $element['#title']]));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);
    $properties = $this->getConfigurationFormProperties($form, $form_state);

    // Validate #pattern's regular expression.
    // @see \Drupal\Core\Render\Element\FormElement::validatePattern
    // @see http://stackoverflow.com/questions/4440626/how-can-i-validate-regex
    if (!empty($properties['#pattern'])) {
      set_error_handler('_webform_entity_element_validate_rendering_error_handler');

      // PHP: Convert JavaScript-escaped Unicode characters to PCRE escape
      // sequence format.
      // @see https://bytefreaks.net/programming-2/php-programming-2/php-convert-javascript-escaped-unicode-characters-to-html-hex-references
      $pcre_pattern = preg_replace('/\\\\u([a-fA-F0-9]{4})/', '\\x{\\1}', $properties['#pattern']);

      if (preg_match('{^(?:' . $pcre_pattern . ')$}u', NULL) === FALSE) {
        $form_state->setErrorByName('pattern', t('Pattern %pattern is not a valid regular expression.', ['%pattern' => $properties['#pattern']]));
      }

      set_error_handler('_drupal_error_handler');
    }

    // Validate #counter_maximum.
    if (!empty($properties['#counter_type']) && empty($properties['#counter_maximum']) && empty($properties['#counter_minimum'])) {
      $form_state->setErrorByName('counter_minimum', t('Counter minimum or maximum is required.'));
      $form_state->setErrorByName('counter_maximum', t('Counter minimum or maximum is required.'));
    }
  }

  /****************************************************************************/
  // Input masks.
  /****************************************************************************/

  /**
   * Get input masks.
   *
   * @return array
   *   An associative array keyed my input mask contain input mask title,
   *   example, and patterh.
   */
  protected function getInputMasks() {
    $input_masks = [
      "'alias': 'currency'" => [
        'title' => $this->t('Currency'),
        'example' => '$ 9.99',
        'pattern' => '^\$ [0-9]{1,3}(,[0-9]{3})*.\d\d$',
      ],
      "'alias': 'datetime'" => [
        'title' => $this->t('Date'),
        'example' => '2007-06-09\'T\'17:46:21',
      ],
      "'alias': 'decimal'" => [
        'title' => $this->t('Decimal'),
        'example' => '1.234',
        'pattern' => '^\d+(\.\d+)?$',
      ],
      "'alias': 'email'" => [
        'title' => $this->t('Email'),
        'example' => 'example@example.com',
      ],
      "'alias': 'ip'" => [
        'title' => $this->t('IP address'),
        'example' => '255.255.255.255',
        'pattern' => '^\d\d\d\.\d\d\d\.\d\d\d\.\d\d\d$',
      ],
      '[9-]AAA-999' => [
        'title' => $this->t('License plate'),
        'example' => '[9-]AAA-999',
      ],
      "'alias': 'mac'" => [
        'title' => $this->t('MAC address'),
        'example' => '99-99-99-99-99-99',
        'pattern' => '^\d\d-\d\d-\d\d-\d\d-\d\d-\d\d$',
      ],
      "'alias': 'percentage'" => [
        'title' => $this->t('Percentage'),
        'example' => '99 %',
        'pattern' => '^\d+ %$',
      ],
      '(999) 999-9999' => [
        'title' => $this->t('Phone'),
        'example' => '(999) 999-9999',
        'pattern' => '^\(\d\d\d\) \d\d\d-\d\d\d\d$',
      ],
      '999-99-9999' => [
        'title' => $this->t('Social Security Number (SSN)'),
        'example' => '999-99-9999',
        'pattern' => '^\d\d\d-\d\d-\d\d\d\d$',
      ],
      "'alias': 'vin'" => [
        'title' => $this->t('Vehicle identification number (VIN)'),
        'example' => 'JA3AY11A82U020534',
      ],
      '99999[-9999]' => [
        'title' => $this->t('ZIP Code'),
        'example' => '99999[-9999]',
        'pattern' => '^\d\d\d\d\d(-\d\d\d\d)?$',
      ],
      "'casing': 'upper'" => [
        'title' => $this->t('Uppercase'),
        'example' => 'UPPERCASE',
      ],
      "'casing': 'lower'" => [
        'title' => $this->t('Lowercase'),
        'example' => 'lowercase',
      ],
    ];

    // Get input masks.
    $modules = \Drupal::moduleHandler()->getImplementations('webform_element_input_masks');
    foreach ($modules as $module) {
      $input_masks += \Drupal::moduleHandler()->invoke($module, 'webform_element_input_masks');
    }

    // Alter input masks.
    \Drupal::moduleHandler()->alter('webform_element_input_masks', $input_masks);

    return $input_masks;
  }

  /**
   * Get input masks as select menu options.
   *
   * @return array
   *   An associative array of options.
   */
  protected function getInputMaskOptions() {
    $input_masks = $this->getInputMasks();
    $options = [];
    foreach ($input_masks as $input_mask => $settings) {
      $options[$input_mask] = $settings['title'] . (!empty($settings['example']) ? ' - ' . $settings['example'] : '');
    }
    return $options;
  }

}
