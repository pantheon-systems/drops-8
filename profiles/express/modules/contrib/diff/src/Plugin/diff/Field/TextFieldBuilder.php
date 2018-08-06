<?php

namespace Drupal\diff\Plugin\diff\Field;

use Drupal\diff\FieldDiffBuilderBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin to diff text fields.
 *
 * @FieldDiffBuilder(
 *   id = "text_field_diff_builder",
 *   label = @Translation("Text Field Diff"),
 *   field_types = {
 *     "text_with_summary",
 *     "text",
 *     "text_long"
 *   },
 * )
 */
class TextFieldBuilder extends FieldDiffBuilderBase {

  /**
   * {@inheritdoc}
   */
  public function build(FieldItemListInterface $field_items) {
    $result = array();
    // Every item from $field_items is of type FieldItemInterface.
    foreach ($field_items as $field_key => $field_item) {
      $values = $field_item->getValue();
      // Compare text formats.
      if ($this->configuration['compare_format'] == 1) {
        if (isset($values['format'])) {
          $controller = $this->entityTypeManager->getStorage('filter_format');
          $format = $controller->load($values['format']);
          // The format loaded successfully.
          $label = $this->t('Format');
          if ($format != NULL) {
            $result[$field_key][] = $label . ": " . $format->label();
          }
          else {
            $result[$field_key][] = $label . ": " . $this->t('Missing format @format', array('@format' => $values[$field_key]));
          }
        }
      }
      // Compare field values.
      if (isset($values['value'])) {
        $value_only = TRUE;
        // Check if summary or text format are included in the diff.
        if ($this->configuration['compare_format'] == 1) {
          $value_only = FALSE;
        }
        $label = $this->t('Value');
        if ($value_only) {
          // Don't display 'value' label.
          $result[$field_key][] = $values['value'];
        }
        else {
          $result[$field_key][] = $label . ":\n" . $values['value'];
        }
      }
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['compare_format'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Compare format'),
      '#default_value' => $this->configuration['compare_format'],
      '#description' => $this->t('This is only used if the "Text processing" instance settings are set to <em>Filtered text (user selects text format)</em>.'),
    );

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['compare_format'] = $form_state->getValue('compare_format');

    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $default_configuration = array(
      'compare_format' => 0,
    );
    $default_configuration += parent::defaultConfiguration();

    return $default_configuration;
  }

}
