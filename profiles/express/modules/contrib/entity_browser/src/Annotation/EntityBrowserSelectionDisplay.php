<?php

namespace Drupal\entity_browser\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an entity browser selection display annotation object.
 *
 * @see hook_entity_browser_selection_display_info_alter()
 *
 * @Annotation
 */
class EntityBrowserSelectionDisplay extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the selection display.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * A brief description of the selection display.
   *
   * This will be shown when adding or configuring this selection display.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation (optional)
   */
  public $description = '';

  /**
   * Preselection support.
   *
   * This will be used by entity browser form element to check, if selection
   * display accepts preselection of entities.
   *
   * @var bool
   */
  public $acceptPreselection = FALSE;

  /**
   * Indicates that javascript commands can be executed for Selection display.
   *
   * Currently supported javascript commands are adding and removing selection
   * from selection display. Javascript commands use Ajax requests to load
   * relevant changes and makes user experience way better, becase form is not
   * flashed every time.
   *
   * @var bool
   */
  public $js_commands = FALSE;

}
