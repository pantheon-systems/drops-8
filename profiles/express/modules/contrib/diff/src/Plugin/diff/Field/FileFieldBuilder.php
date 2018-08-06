<?php

namespace Drupal\diff\Plugin\diff\Field;

use Drupal\diff\FieldDiffBuilderBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin to diff file fields.
 *
 * @FieldDiffBuilder(
 *   id = "file_field_diff_builder",
 *   label = @Translation("File Field Diff"),
 *   field_types = {
 *     "file"
 *   },
 * )
 */
class FileFieldBuilder extends FieldDiffBuilderBase {

  /**
   * {@inheritdoc}
   */
  public function build(FieldItemListInterface $field_items) {
    $result = array();
    $fileManager = $this->entityTypeManager->getStorage('file');

    // Every item from $field_items is of type FieldItemInterface.
    foreach ($field_items as $field_key => $field_item) {
      if (!$field_item->isEmpty()) {
        $values = $field_item->getValue();

        // Add file name to the comparison.
        if (isset($values['target_id'])) {
          /** @var \Drupal\file\Entity\File $file */
          $file = $fileManager->load($values['target_id']);
          $result[$field_key][] = $this->t('File: :image', [
            ':image' => $file->getFilename(),
          ]);
        }

        // Add file id to the comparison.
        if ($this->configuration['show_id']) {
          if (isset($values['target_id'])) {
            $result[$field_key][] = $this->t('File ID: :fid', [
              ':fid' => $values['target_id'],
            ]);
          }
        }

        // Compare file description fields.
        if ($this->configuration['compare_description_field']) {
          if (isset($values['description'])) {
            $result[$field_key][] = $this->t('Description: @description', [
              '@description' => $values['description'],
            ]);
          }
        }

        // Compare Enable Display property.
        if ($this->configuration['compare_display_field']) {
          if (isset($values['display'])) {
            if ($values['display'] == 1) {
              $result[$field_key][] = $this->t('Displayed');
            }
            else {
              $result[$field_key][] = $this->t('Hidden');
            }
          }
        }

        // Add the requested separator between resulted strings.
        if ($this->configuration['property_separator']) {
          $separator = $this->configuration['property_separator'] == 'nl' ? "\n" : $this->configuration['property_separator'];
          $result[$field_key] = implode($separator, $result[$field_key]);
        }
      }

    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['show_id'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show file ID'),
      '#default_value' => $this->configuration['show_id'],
    );
    $form['compare_description_field'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Compare description field'),
      '#default_value' => $this->configuration['compare_description_field'],
      '#description' => $this->t('This is only used if the "Enable <em>Description</em> field" is checked in the instance settings.'),
    );
    $form['compare_display_field'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Compare display state field'),
      '#default_value' => $this->configuration['compare_display_field'],
      '#description' => $this->t('This is only used if the "Enable <em>Display</em> field" is checked in the field settings.'),
    );
    $form['property_separator'] = array(
      '#type' => 'select',
      '#title' => $this->t('Property separator'),
      '#default_value' => $this->configuration['property_separator'],
      '#description' => $this->t('Provides the ability to show properties inline or across multiple lines.'),
      '#options' => array(
        ', ' => $this->t('Comma (,)'),
        '; ' => $this->t('Semicolon (;)'),
        ' ' => $this->t('Space'),
        'nl' => $this->t('New line'),
      ),
    );

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['show_id'] = $form_state->getValue('show_id');
    $this->configuration['compare_description_field'] = $form_state->getValue('compare_description_field');
    $this->configuration['compare_display_field'] = $form_state->getValue('compare_display_field');
    $this->configuration['property_separator'] = $form_state->getValue('property_separator');

    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $default_configuration = array(
      'show_id' => 1,
      'compare_description_field' => 0,
      'compare_display_field' => 0,
      'property_separator' => 'nl',
    );
    $default_configuration += parent::defaultConfiguration();

    return $default_configuration;
  }

}
