<?php

namespace Drupal\Tests\video_embed_field\Functional;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\simpletest\ContentTypeCreationTrait;
use Drupal\simpletest\NodeCreationTrait;

/**
 * A trait for manipulating entity display.
 */
trait EntityDisplaySetupTrait {

  use ContentTypeCreationTrait;
  use NodeCreationTrait;

  /**
   * The field name.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * The name of the content type.
   *
   * @var string
   */
  protected $contentTypeName;

  /**
   * The entity display.
   *
   * @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface
   */
  protected $entityDisplay;

  /**
   * The form display.
   *
   * @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface
   */
  protected $entityFormDisplay;

  /**
   * Setup the entity displays with required fields.
   */
  protected function setupEntityDisplays() {
    $this->fieldName = 'field_test_video_field';
    $this->contentTypeName = 'test_content_type_name';
    $this->createContentType(['type' => $this->contentTypeName]);
    $field_storage = FieldStorageConfig::create([
      'field_name' => $this->fieldName,
      'entity_type' => 'node',
      'type' => 'video_embed_field',
      'settings' => [
        'allowed_providers' => [],
      ],
    ]);
    $field_storage->save();
    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => $this->contentTypeName,
      'settings' => [],
    ])->save();
    $this->entityDisplay = entity_get_display('node', $this->contentTypeName, 'default');
    $this->entityFormDisplay = entity_get_form_display('node', $this->contentTypeName, 'default');
  }

  /**
   * Set component settings for the display.
   *
   * @param string $type
   *   The component to change settings for.
   * @param array $settings
   *   The settings to use.
   */
  protected function setDisplayComponentSettings($type, $settings = []) {
    $this->entityDisplay->setComponent($this->fieldName, [
      'type' => $type,
      'settings' => $settings,
    ])->save();
  }

  /**
   * Set component settings for the form.
   *
   * @param string $type
   *   The component to change settings for.
   * @param array $settings
   *   The settings to use.
   */
  protected function setFormComponentSettings($type, $settings = []) {
    $this->entityFormDisplay
      ->setComponent($this->fieldName, [
        'type' => $type,
        'settings' => $settings,
      ])
      ->save();
  }

  /**
   * Create a video node using the video field.
   *
   * @param string $value
   *   The video URL to use for the field value.
   *
   * @return \Drupal\node\NodeInterface
   *   A node.
   */
  protected function createVideoNode($value) {
    return $this->createNode([
      'type' => $this->contentTypeName,
      $this->fieldName => [
        ['value' => $value],
      ],
    ]);
  }

}
