<?php

namespace Drupal\diff\Plugin\diff\Field;

use Drupal\diff\FieldDiffBuilderBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin to diff list fields.
 *
 * @FieldDiffBuilder(
 *   id = "list_field_diff_builder",
 *   label = @Translation("List Field Diff"),
 *   field_types = {
 *     "list_string",
 *     "list_integer",
 *     "list_float"
 *   },
 * )
 */
class ListFieldBuilder extends FieldDiffBuilderBase {

  /**
   * {@inheritdoc}
   */
  public function build(FieldItemListInterface $field_items) {
    $result = array();

    // Every item from $field_items is of type FieldItemInterface.
    foreach ($field_items as $field_key => $field_item) {
      // Build the array for comparison only if the field is not empty.
      if (!$field_item->isEmpty()) {
        $possible_options = $field_item->getPossibleOptions();
        $values = $field_item->getValue();
        if ($this->configuration['compare']) {
          switch ($this->configuration['compare']) {
            case 'both':
              $result[$field_key][] = $possible_options[$values['value']] . ' (' . $values['value'] . ')';
              break;

            case 'label':
              $result[$field_key][] = $possible_options[$values['value']];
              break;

            default:
              $result[$field_key][] = $values['value'];
              break;
          }
        }
      }
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['compare'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Comparison method'),
      '#options' => array(
        'label' => $this->t('Label'),
        'key' => $this->t('Key'),
        'both' => $this->t('Label (key)'),
      ),
      '#default_value' => $this->configuration['compare'],
    );

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['compare'] = $form_state->getValue('compare');

    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $default_configuration = array(
      'compare' => 'key',
    );
    $default_configuration += parent::defaultConfiguration();

    return $default_configuration;
  }

}
