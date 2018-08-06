<?php

namespace Drupal\crop\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\crop\CropInterface;
use Drupal\crop\EntityProviderNotFoundException;

/**
 * Defines the crop entity class.
 *
 * @ContentEntityType(
 *   id = "crop",
 *   label = @Translation("Crop"),
 *   bundle_label = @Translation("Crop type"),
 *   handlers = {
 *     "storage" = "Drupal\crop\CropStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityConfirmFormBase",
 *       "edit" = "Drupal\Core\Entity\ContentEntityForm"
 *     },
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler"
 *   },
 *   base_table = "crop",
 *   data_table = "crop_field_data",
 *   revision_table = "crop_revision",
 *   revision_data_table = "crop_field_revision",
 *   fieldable = TRUE,
 *   translatable = TRUE,
 *   render_cache = FALSE,
 *   entity_keys = {
 *     "id" = "cid",
 *     "bundle" = "type",
 *     "revision" = "vid",
 *     "langcode" = "langcode",
 *     "uuid" = "uuid"
 *   },
 *   bundle_entity_type = "crop_type",
 *   permission_granularity = "entity_type",
 *   admin_permission = "administer crop",
 *   links = {
 *   }
 * )
 */
class Crop extends ContentEntityBase implements CropInterface {

  /**
   * {@inheritdoc}
   */
  public function position() {
    return [
      'x' => (int) $this->x->value,
      'y' => (int) $this->y->value,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setPosition($x, $y) {
    $this->x = $x;
    $this->y = $y;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function anchor() {
    return [
      'x' => (int) ($this->x->value - ($this->width->value / 2)),
      'y' => (int) ($this->y->value - ($this->height->value / 2)),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function size() {
    return [
      'width' => (int) $this->width->value,
      'height' => (int) $this->height->value,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setSize($width, $height) {
    $this->width = $width;
    $this->height = $height;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function provider() {
    /** @var \Drupal\crop\EntityProviderManager $plugin_manager */
    $plugin_manager = \Drupal::service('plugin.manager.crop.entity_provider');

    if (!$plugin_manager->hasDefinition($this->entity_type->value)) {
      throw new EntityProviderNotFoundException(t('Entity provider @id not found.', ['@id' => $this->entity_type->value]));
    }

    return $plugin_manager->createInstance($this->entity_type->value);
  }

  /**
   * {@inheritdoc}
   */
  public static function cropExists($uri, $type = NULL) {
    $query = \Drupal::entityQuery('crop')
      ->condition('uri', $uri);
    if ($type) {
      $query->condition('type', $type);
    }
    return (bool) $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public static function findCrop($uri, $type) {
    $query = \Drupal::entityQuery('crop')
      ->condition('uri', $uri);
    if ($type) {
      $query->condition('type', $type);
    }
    $crop = $query->sort('cid')
      ->range(0, 1)
      ->execute();

    return $crop ? \Drupal::entityTypeManager()->getStorage('crop')->load(current($crop)) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    // If no revision author has been set explicitly, make the current user
    // revision author.
    if (!$this->get('revision_uid')->entity) {
      $this->set('revision_uid', \Drupal::currentUser()->id());
    }

    // Try to set URI if not yet defined.
    if (empty($this->uri->value) && !empty($this->entity_type->value) && !empty($this->entity_id->value)) {
      $entity = \Drupal::entityTypeManager()->getStorage($this->entity_type->value)->load($this->entity_id->value);
      if ($uri = $this->provider()->uri($entity)) {
        $this->set('uri', $uri);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preSaveRevision(EntityStorageInterface $storage, \stdClass $record) {
    parent::preSaveRevision($storage, $record);

    if (!$this->isNewRevision() && isset($this->original) && (!isset($record->revision_log) || $record->revision_log === '')) {
      // If we are updating an existing crop without adding a new revision, we
      // need to make sure $entity->revision_log is reset whenever it is empty.
      // Therefore, this code allows us to avoid clobbering an existing log
      // entry with an empty one.
      $record->revision_log = $this->original->revision_log->value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // If you are manually generating your image derivatives instead of waiting
    // for them to be generated on the fly, because you are using a cloud
    // storage service (like S3), then you may not want your image derivatives
    // to be flushed. If they are you could end up serving 404s during the time
    // between the crop entity being saved and the image derivative being
    // manually generated and pushed to your cloud storage service. In that
    // case, set this configuration variable to false.
    $flush_derivative_images = \Drupal::config('crop.settings')->get('flush_derivative_images');
    if ($flush_derivative_images) {
      image_path_flush($this->uri->value);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = [];

    $fields['cid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Crop ID'))
      ->setDescription(t('The crop ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The crop UUID.'))
      ->setReadOnly(TRUE);

    $fields['vid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Revision ID'))
      ->setDescription(t('The crop revision ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Type'))
      ->setDescription(t('The crop type.'))
      ->setSetting('target_type', 'crop_type')
      ->setReadOnly(TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The node language code.'))
      ->setRevisionable(TRUE);

    $fields['entity_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Entity ID'))
      ->setDescription(t('ID of entity crop belongs to.'))
      ->setSetting('unsigned', TRUE)
      ->setRevisionable(TRUE)
      ->setReadOnly(TRUE);

    $fields['entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Entity type'))
      ->setDescription(t('The type of entity crop belongs to.'))
      ->setRevisionable(TRUE)
      ->setReadOnly(TRUE);

    // Denormalized information, which is calculated in storage plugin for a
    // given entity type. Saved here for performance reasons in image effects.
    // ---
    // TODO - we are not enforcing uniqueness on this as we want to support more
    // crops per same image/image_style combination. However, image effect
    // operates with image URI only, which means we have no mechanism to
    // distinguish between multiple crops in there. If we really want to
    // support multiple crops we'll need to override core at least,
    // in \Drupal\Core\Image\ImageFactory and \Drupal\Core\Image\Image.
    // Let's leave this for now and simply load based on URI only.
    // We can use some semi-smart approach in case there are multiple crops
    // with same URI for now (first created, last created, ...).
    $fields['uri'] = BaseFieldDefinition::create('uri')
      ->setLabel(t('URI'))
      ->setDescription(t('The URI of the image crop belongs to.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setSetting('max_length', 255);

    $fields['height'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Height'))
      ->setDescription(t('The crop height.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['width'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Width'))
      ->setDescription(t('The crop width.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['x'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('X coordinate'))
      ->setDescription(t("The crop's X coordinate."))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['y'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Y coordinate'))
      ->setDescription(t("The crop's Y coordinate."))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['revision_timestamp'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Revision timestamp'))
      ->setDescription(t('The time that the current revision was created.'))
      ->setQueryable(FALSE)
      ->setRevisionable(TRUE);

    $fields['revision_uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Revision author ID'))
      ->setDescription(t('The user ID of the author of the current revision.'))
      ->setSetting('target_type', 'user')
      ->setQueryable(FALSE)
      ->setRevisionable(TRUE);

    $fields['revision_log'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Revision Log'))
      ->setDescription(t('The log entry explaining the changes in this revision.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    return $fields;
  }

}
