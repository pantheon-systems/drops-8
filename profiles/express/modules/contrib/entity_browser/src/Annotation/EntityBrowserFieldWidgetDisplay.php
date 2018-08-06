<?php

namespace Drupal\entity_browser\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an entity browser field widget display annotation object.
 *
 * @see hook_entity_browser_field_widget_display_info_alter()
 *
 * @Annotation
 */
class EntityBrowserFieldWidgetDisplay extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the field widget display display.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * A brief description of the field widget display.
   *
   * This will be shown when adding or configuring this display.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation (optional)
   */
  public $description = '';

}
