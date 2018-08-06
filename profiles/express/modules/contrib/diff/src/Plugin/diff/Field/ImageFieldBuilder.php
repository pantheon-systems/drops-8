<?php

namespace Drupal\diff\Plugin\diff\Field;

use Drupal\diff\FieldDiffBuilderBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin to diff image fields.
 *
 * @FieldDiffBuilder(
 *   id = "image_field_diff_builder",
 *   label = @Translation("Image Field Diff"),
 *   field_types = {
 *     "image"
 *   },
 * )
 */
class ImageFieldBuilder extends FieldDiffBuilderBase {

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

        // Compare file names.
        if (isset($values['target_id'])) {
          /** @var \Drupal\file\Entity\File $image */
          $image = $fileManager->load($values['target_id']);
          $result[$field_key][] = $this->t('Image: @image', [
            '@image' => $image->getFilename(),
          ]);
        }

        // Compare Alt fields.
        if ($this->configuration['compare_alt_field']) {
          if (isset($values['alt'])) {
            $result[$field_key][] = $this->t('Alt: @alt', [
              '@alt' => $values['alt'],
            ]);
          }
        }

        // Compare Title fields.
        if ($this->configuration['compare_title_field']) {
          if (!empty($values['title'])) {
            $result[$field_key][] = $this->t('Title: @title', [
              '@title' => $values['title'],
            ]);
          }
        }

        // Compare file id.
        if ($this->configuration['show_id']) {
          if (isset($values['target_id'])) {
            $result[$field_key][] = $this->t('File ID: @fid', [
              '@fid' => $values['target_id'],
            ]);
          }
        }

        $separator = $this->configuration['property_separator'] == 'nl' ? "\n" : $this->configuration['property_separator'];
        $result[$field_key] = implode($separator, $result[$field_key]);

        // EXPERIMENTAL: Attach thumbnail image data.
        if ($this->configuration['show_thumbnail']) {
          if (isset($values['target_id'])) {
            $storage = $this->entityTypeManager->getStorage('entity_form_display');
            $display = $storage->load($field_items->getFieldDefinition()->getTargetEntityTypeId() . '.' . $field_items->getEntity()->bundle() . '.default');
            if ($image_field = $display->getComponent($field_item->getFieldDefinition()->getName())) {
              $image = $fileManager->load($values['target_id']);

              $image_style[$field_key]['#thumbnail'] = array(
                '#theme' => 'image_style',
                '#uri' => $image->getFileUri(),
                '#style_name' => $image_field['settings']['preview_image_style'],
              );
              $result = array_merge($result, $image_style);
            }
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
    $form['show_id'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show image ID'),
      '#default_value' => $this->configuration['show_id'],
    );
    $form['compare_alt_field'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Compare <em>Alt</em> field'),
      '#default_value' => $this->configuration['compare_alt_field'],
      '#description' => $this->t('This is only used if the "Enable <em>Alt</em> field" is checked in the instance settings.'),
    );
    $form['compare_title_field'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Compare <em>Title</em> field'),
      '#default_value' => $this->configuration['compare_title_field'],
      '#description' => $this->t('This is only used if the "Enable <em>Title</em> field" is checked in the instance settings.'),
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
    $form['show_thumbnail'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show image thumbnail'),
      '#default_value' => $this->configuration['show_thumbnail'],
      '#description' => $this->t('Displays the image field as thumbnail.'),
    ];

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['show_id'] = $form_state->getValue('show_id');
    $this->configuration['compare_alt_field'] = $form_state->getValue('compare_alt_field');
    $this->configuration['compare_title_field'] = $form_state->getValue('compare_title_field');
    $this->configuration['property_separator'] = $form_state->getValue('property_separator');
    $this->configuration['show_thumbnail'] = $form_state->getValue('show_thumbnail');

    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $default_configuration = array(
      'show_id' => 0,
      'compare_alt_field' => 1,
      'compare_title_field' => 1,
      'property_separator' => 'nl',
      'show_thumbnail' => 1,
    );
    $default_configuration += parent::defaultConfiguration();

    return $default_configuration;
  }

}
