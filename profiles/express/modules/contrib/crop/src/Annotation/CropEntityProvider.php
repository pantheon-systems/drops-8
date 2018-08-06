<?php

namespace Drupal\crop\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines the annotation object for crop integration with storage entities.
 *
 * @see hook_crop_entity_provider_info_alter()
 *
 * @Annotation
 */
class CropEntityProvider extends Plugin {

  /**
   * Entity type plugin provides.
   *
   * @var string
   */
  public $entity_type;

  /**
   * The human-readable name of the crop entity provider.
   *
   * Will usually match entity type name.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * A brief description of the crop entity provider.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description = '';

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->definition['entity_type'];
  }

}
