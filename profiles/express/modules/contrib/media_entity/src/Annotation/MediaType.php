<?php

namespace Drupal\media_entity\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an media entity type plugin annotation object.
 *
 * @see hook_media_entity_type_info_alter()
 *
 * @Annotation
 */
class MediaType extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the type.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * A brief description of the plugin.
   *
   * This will be shown when adding or configuring this display.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation (optional)
   */
  public $description = '';

}
