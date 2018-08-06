<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformElementBase;
use Drupal\webform\WebformInterface;

/**
 * Provides a 'webform_codemirror' element.
 *
 * @WebformElement(
 *   id = "webform_codemirror",
 *   label = @Translation("CodeMirror"),
 *   description = @Translation("Provides a form element for editing code in a number of programming languages and markup."),
 *   category = @Translation("Advanced elements"),
 *   multiline = TRUE,
 * )
 */
class WebformCodeMirror extends WebformElementBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return parent::getDefaultProperties() + [
      // Codemirror setings.
      'mode' => 'text',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function formatHtmlItem(array $element, $value, array $options = []) {
    if (empty($value)) {
      return '';
    }

    $format = $this->getItemFormat($element);
    switch ($format) {
      case 'code':
        return [
          '#theme' => 'webform_codemirror',
          '#code' => $value,
          '#type' => $element['#mode'],
        ];

      default:
        return parent::formatHtmlItem($element, $value, $options);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getItemDefaultFormat() {
    return 'code';
  }

  /**
   * {@inheritdoc}
   */
  public function getItemFormats() {
    return parent::getItemFormats() + [
      'code' => $this->t('Code'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getTestValues(array $element, WebformInterface $webform, array $options = []) {
    $element += ['#mode' => 'text'];
    switch ($element['#mode']) {
      case 'html':
        return ['<p><b>Hello World!!!</b></p>'];

      case 'yaml':
        return ["message: 'Hello World'"];

      case 'text':
        return ["Hello World"];

      default:
        return [];

    }

  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['codemirror'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('CodeMirror settings'),
    ];
    $form['codemirror']['mode'] = [
      '#title' => $this->t('Mode'),
      '#type' => 'select',
      '#options' => [
        'yaml' => $this->t('YAML'),
        'html' => $this->t('HTML'),
        'text' => $this->t('Plain text'),
      ],
      '#required' => TRUE,
    ];
    return $form;
  }

}
