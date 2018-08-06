<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'table' trait.
 */
trait WebformTableTrait {

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    if ($this->hasMultipleValues($element)) {
      $element['#element_validate'][] = [get_class($this), 'validateMultipleOptions'];
    }

    parent::prepare($element, $webform_submission);

    // Add missing element class.
    $element['#attributes']['class'][] = str_replace('_', '-', $element['#type']);

    // Add one column header is not #header is specified.
    if (!isset($element['#header'])) {
      $element['#header'] = [
        (isset($element['#title']) ? $element['#title'] : ''),
      ];
    }

    // Convert associative array of options into one column row.
    if (isset($element['#options'])) {
      foreach ($element['#options'] as $options_key => $options_value) {
        if (is_string($options_value)) {
          $element['#options'][$options_key] = [
            ['value' => $options_value],
          ];
        }
      }
    }

    $element['#attached']['library'][] = 'webform/webform.element.' . $element['#type'];
  }

  /**
   * {@inheritdoc}
   */
  public function setDefaultValue(array &$element) {
    if (isset($element['#default_value']) && is_array($element['#default_value'])) {
      $element['#default_value'] = array_combine($element['#default_value'], $element['#default_value']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['options']['js_select'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Select all'),
      '#description' => $this->t('If checked, a select all checkbox will be added to the header.'),
      '#return_value' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function getTableSelectElementSelectorOptions(array $element, $input_selector = '') {
    $title = $this->getAdminLabel($element) . ' [' . $this->getPluginLabel() . ']';
    $name = $element['#webform_key'];
    $type = ($this->hasMultipleValues($element) ? $this->t('Checkbox') : $this->t('Radio'));

    $selectors = [];
    foreach ($element['#options'] as $value => $text) {
      if (is_array($text)) {
        $text = $value;
      }
      $selectors[":input[name=\"{$name}[{$value}]$input_selector\"]"] = $text . ' [' . $type . ']';
    }
    return [$title => $selectors];
  }

}
