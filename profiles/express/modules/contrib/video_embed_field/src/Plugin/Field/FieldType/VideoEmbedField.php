<?php

namespace Drupal\video_embed_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\TraversableTypedDataInterface;

/**
 * Plugin implementation of the video_embed_field field type.
 *
 * @FieldType(
 *   id = "video_embed_field",
 *   label = @Translation("Video Embed"),
 *   description = @Translation("Stores a video and then outputs some embed code."),
 *   category = @Translation("Media"),
 *   default_widget = "video_embed_field_textfield",
 *   default_formatter = "video_embed_field_video",
 *   constraints = {"VideoEmbedValidation" = {}}
 * )
 */
class VideoEmbedField extends FieldItemBase {

  /**
   * The embed provider plugin manager.
   *
   * @var \Drupal\video_embed_field\ProviderManagerInterface
   */
  protected $providerManager;

  /**
   * {@inheritdoc}
   */
  public function __construct($definition, $name = NULL, TraversableTypedDataInterface $parent = NULL, $provider_manager = NULL) {
    parent::__construct($definition, $name, $parent);
    $this->providerManager = $provider_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance($definition, $name = NULL, TraversableTypedDataInterface $parent = NULL) {
    $provider_manager = \Drupal::service('video_embed_field.provider_manager');
    return new static($definition, $name, $parent, $provider_manager);
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'varchar',
          'length' => 256,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Video url'))
      ->setRequired(TRUE);
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return empty($value);
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $form = [];
    $form['allowed_providers'] = [
      '#title' => $this->t('Allowed Providers'),
      '#description' => $this->t('Restrict users from entering information from the following providers. If none are selected any video provider can be used.'),
      '#type' => 'checkboxes',
      '#default_value' => $this->getSetting('allowed_providers'),
      '#options' => $this->providerManager->getProvidersOptionList(),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'allowed_providers' => [],
    ];
  }

}
