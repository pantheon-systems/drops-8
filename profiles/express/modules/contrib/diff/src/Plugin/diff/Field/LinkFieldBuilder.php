<?php

namespace Drupal\diff\Plugin\diff\Field;

use Drupal\diff\FieldDiffBuilderBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin to compare the title and the uris of two link fields.
 *
 * @FieldDiffBuilder(
 *   id = "link_field_diff_builder",
 *   label = @Translation("Link Field Diff"),
 *   field_types = {
 *     "link"
 *   },
 * )
 */
class LinkFieldBuilder extends FieldDiffBuilderBase {

  /**
   * {@inheritdoc}
   */
  public function build(FieldItemListInterface $field_items) {
    $result = array();

    // Every item from $field_items is of type FieldItemInterface.
    foreach ($field_items as $field_key => $field_item) {
      if (!$field_item->isEmpty()) {
        $values = $field_item->getValue();
        // Compare the link title if that plugin options is selected.
        if ($this->configuration['compare_title']) {
          if (isset($values['title'])) {
            $result[$field_key][] = $values['title'];
          }
        }
        // Compare the uri if that plugin options is selected.
        if ($this->configuration['compare_uri']) {
          if (isset($values['uri'])) {
            $result[$field_key][] = $values['uri'];
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
    $form['compare_title'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Compare link title'),
      '#default_value' => $this->configuration['compare_title'],
    );
    $form['compare_uri'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Compare URI'),
      '#default_value' => $this->configuration['compare_uri'],
    );

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['compare_title'] = $form_state->getValue('compare_title');
    $this->configuration['compare_uri'] = $form_state->getValue('compare_uri');

    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $default_configuration = array(
      'compare_title' => 0,
      'compare_uri' => 1,
    );
    $default_configuration += parent::defaultConfiguration();

    return $default_configuration;
  }

}
