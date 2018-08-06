<?php

namespace Drupal\entity_browser\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an entity browser widget validation annotation object.
 *
 * @see hook_entity_browser_widget_validation_info_alter()
 *
 * @Annotation
 */
class EntityBrowserWidgetValidation extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the widget validator.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * (Optional) The data type plugin ID, for which a constraint should be added.
   *
   * @var string
   */
  public $data_type;

  /**
   * (Optional) The constraint ID.
   *
   * @var string
   */
  public $constraint;
}
