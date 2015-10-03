<?php

/**
 * @file
 * Contains \Drupal\field_test\Plugin\Field\FieldFormatter\TestFieldDefaultFormatter.
 */

namespace Drupal\field_test\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'field_test_default' formatter.
 *
 * @FieldFormatter(
 *   id = "field_test_default",
 *   label = @Translation("Default"),
 *   description = @Translation("Default formatter"),
 *   field_types = {
 *     "test_field",
 *     "test_field_with_preconfigured_options"
 *   },
 *   weight = 1
 * )
 */
class TestFieldDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'test_formatter_setting' => 'dummy test string',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['test_formatter_setting'] = array(
      '#title' => t('Setting'),
      '#type' => 'textfield',
      '#size' => 20,
      '#default_value' => $this->getSetting('test_formatter_setting'),
      '#required' => TRUE,
    );
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();
    $summary[] = t('@setting: @value', array('@setting' => 'test_formatter_setting', '@value' => $this->getSetting('test_formatter_setting')));
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();

    foreach ($items as $delta => $item) {
      $elements[$delta] = array('#markup' => $this->getSetting('test_formatter_setting') . '|' . $item->value);
    }

    return $elements;
  }
}
