<?php

namespace Drupal\video_embed_media;

use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\media_entity\Entity\Media;
use Drupal\media_entity\Entity\MediaBundle;
use Drupal\video_embed_media\Plugin\MediaEntity\Type\VideoEmbedField;

/**
 * Upgrades existing media_entity_embedded_video bundles.
 */
class UpgradeManager implements UpgradeManagerInterface {

  /**
   * The entity query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * UpgradeManager constructor.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query
   *   The entity query service.
   */
  public function __construct(QueryFactory $entity_query) {
    $this->entityQuery = $entity_query;
  }

  /**
   * {@inheritdoc}
   */
  public function upgrade() {
    $entities = $this->entityQuery->get('media_bundle')->condition('type', 'embeddable_video')->execute();
    foreach ($entities as $entity) {
      $bundle = MediaBundle::load($entity);
      $this->upgradeBundle($bundle);
    }
  }

  /**
   * Upgrade a whole bundle to use video_embed_field.
   *
   * @param \Drupal\media_entity\Entity\MediaBundle $bundle
   *   The media bundle object.
   */
  protected function upgradeBundle(MediaBundle $bundle) {
    // Create a video embed field on the media bundle.
    VideoEmbedField::createVideoEmbedField($bundle->id());
    // Load and update all of the existing media entities.
    $media_entities = $this->entityQuery->get('media')->condition('bundle', $bundle->id())->execute();
    foreach ($media_entities as $media_entity) {
      $media_entity = Media::load($media_entity);
      $this->upgradeEntity($media_entity, $bundle->getTypeConfiguration());
    }
    // Update the media bundle type.
    $bundle->type = 'video_embed_field';
    $bundle->save();
  }

  /**
   * Upgrade an individual media entity.
   *
   * @param \Drupal\media_entity\Entity\Media $media_entity
   *   The media entity.
   * @param array $type_configuration
   *   The media type configuration.
   */
  protected function upgradeEntity(Media $media_entity, $type_configuration) {
    // Copy the existing media bundle field value to the new field value.
    $existing_url_field = $media_entity->{$type_configuration['source_field']}->getValue();
    $existing_url = isset($existing_url_field[0]['uri']) ? $existing_url_field[0]['uri'] : $existing_url_field[0]['value'];
    $media_entity->{VideoEmbedField::VIDEO_EMBED_FIELD_DEFAULT_NAME} = [['value' => $existing_url]];
    $media_entity->save();
  }

}
