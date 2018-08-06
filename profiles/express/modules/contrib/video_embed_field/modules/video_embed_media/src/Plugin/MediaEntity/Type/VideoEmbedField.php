<?php

namespace Drupal\video_embed_media\Plugin\MediaEntity\Type;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\media_entity\MediaInterface;
use Drupal\media_entity\MediaTypeBase;
use Drupal\video_embed_field\ProviderManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\Config;

/**
 * Provides media type plugin for video embed field.
 *
 * @MediaType(
 *   id = "video_embed_field",
 *   label = @Translation("Video embed field"),
 *   description = @Translation("Enables video_embed_field integration with media_entity.")
 * )
 */
class VideoEmbedField extends MediaTypeBase {

  /**
   * The name of the field on the media entity.
   */
  const VIDEO_EMBED_FIELD_DEFAULT_NAME = 'field_media_video_embed_field';

  /**
   * The video provider manager.
   *
   * @var \Drupal\video_embed_field\ProviderManagerInterface
   */
  protected $providerManager;

  /**
   * The media settings.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $mediaSettings;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, Config $config, ProviderManagerInterface $provider_manager, Config $media_settings) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $entity_field_manager, $config);
    $this->providerManager = $provider_manager;
    $this->mediaSettings = $media_settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('config.factory')->get('media_entity.settings'),
      $container->get('video_embed_field.provider_manager'),
      $container->get('config.factory')->get('media_entity.settings')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function thumbnail(MediaInterface $media) {
    if ($provider = $this->loadProvider($media)) {
      $provider->downloadThumbnail();
      return $provider->getLocalThumbnailUri();
    }
    return $this->getDefaultThumbnail();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $options = [];
    foreach ($this->entityFieldManager->getFieldDefinitions('media', $form_state->getFormObject()->getEntity()->id()) as $field_name => $field) {
      if ($field->getType() == 'video_embed_field') {
        $options[$field_name] = $field->getLabel();
      }
    }
    if (empty($options)) {
      $form['summary']['#markup'] = $this->t('A video embed field will be created on this media bundle when you save this form. You can return to this configuration screen to alter the video field used for this bundle, or you can use the one provided.');
    }
    if (!empty($options)) {
      $form['source_field'] = [
        '#type' => 'select',
        '#required' => TRUE,
        '#title' => $this->t('Source Video Field'),
        '#description' => $this->t('The field on the media entity that contains the video URL.'),
        '#default_value' => empty($this->configuration['source_field']) ? VideoEmbedField::VIDEO_EMBED_FIELD_DEFAULT_NAME : $this->configuration['source_field'],
        '#options' => $options,
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getField(MediaInterface $media, $name) {
    if (!$url = $this->getVideoUrl($media)) {
      return FALSE;
    }
    $provider = $this->providerManager->loadProviderFromInput($url);
    $definition = $this->providerManager->loadDefinitionFromInput($url);
    switch ($name) {
      case 'id':
        return $provider->getIdFromInput($url);

      case 'source':
        return $definition['id'];

      case 'source_name':
        return $definition['id'];

      case 'image_local':
      case 'image_local_uri':
        return $this->thumbnail($media);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function providedFields() {
    return [
      'id' => $this->t('Video ID.'),
      'source' => $this->t('Video source machine name.'),
      'source_name' => $this->t('Video source human name.'),
      'image_local' => $this->t('Copies thumbnail image to the local filesystem and returns the URI.'),
      'image_local_uri' => $this->t('Gets URI of the locally saved image.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultName(MediaInterface $media) {
    if ($provider = $this->loadProvider($media)) {
      return $this->loadProvider($media)->getName();
    }
    return parent::getDefaultThumbnail();
  }

  /**
   * Load a video provider given a media entity.
   *
   * @param \Drupal\media_entity\MediaInterface $media
   *   The media entity.
   *
   * @return \Drupal\video_embed_field\ProviderPluginInterface
   *   The provider plugin.
   */
  protected function loadProvider(MediaInterface $media) {
    $video_url = $this->getVideoUrl($media);
    return !empty($video_url) ? $this->providerManager->loadProviderFromInput($video_url) : FALSE;
  }

  /**
   * Get the video URL from a media entity.
   *
   * @param \Drupal\media_entity\MediaInterface $media
   *   The media entity.
   *
   * @return string|bool
   *   A video URL or FALSE on failure.
   */
  protected function getVideoUrl(MediaInterface $media) {
    $field_name = empty($this->configuration['source_field']) ? VideoEmbedField::VIDEO_EMBED_FIELD_DEFAULT_NAME : $this->configuration['source_field'];
    $video_url = $media->{$field_name}->value;
    return !empty($video_url) ? $video_url : FALSE;
  }

  /**
   * The function that is invoked during the insert of media bundles.
   *
   * @param string $media_bundle_id
   *   The ID of the media bundle.
   */
  public static function createVideoEmbedField($media_bundle_id) {
    if (!$storage = FieldStorageConfig::loadByName('media', static::VIDEO_EMBED_FIELD_DEFAULT_NAME)) {
      FieldStorageConfig::create([
        'field_name' => static::VIDEO_EMBED_FIELD_DEFAULT_NAME,
        'entity_type' => 'media',
        'type' => 'video_embed_field',
      ])->save();
    }
    FieldConfig::create([
      'entity_type' => 'media',
      'field_name' => static::VIDEO_EMBED_FIELD_DEFAULT_NAME,
      'label' => 'Video URL',
      'required' => TRUE,
      'bundle' => $media_bundle_id,
    ])->save();
    // Make the field visible on the form display.
    $form_display = entity_get_form_display('media', $media_bundle_id, 'default');
    $form_display->setComponent(static::VIDEO_EMBED_FIELD_DEFAULT_NAME, [
      'type' => 'video_embed_field_textfield',
    ])->save();
    // Make the field visible on the media entity itself.
    $dispaly = entity_get_display('media', $media_bundle_id, 'default');
    $dispaly->setComponent(static::VIDEO_EMBED_FIELD_DEFAULT_NAME, [
      'type' => 'video_embed_field_video',
    ])->save();
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultThumbnail() {
    return $this->mediaSettings->get('icon_base') . '/video.png';
  }

}
