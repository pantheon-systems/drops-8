<?php

namespace Drupal\entity_browser\Events;

/**
 * Contains all events thrown by entity browser.
 */
final class Events {

  /**
   * The SELECED event occurs when enities are selected in currently active
   * widget.
   *
   * @var string
   */
  const SELECTED = 'entity_browser.selected';

  /**
   * The DONE event occurs when selection process is done. While it can be emitted
   * by any part of the system that will usually be done by selection display plugin.
   *
   * @var string
   */
  const DONE = 'entity_browser.done';

  /**
   * The REGISTER_JS_CALLBACKS collects JS callbacks that need to be notified when
   * we bring selected entities back to the form. Callbacks are responsible to
   * propagate selection further to entitiy fields, etc.
   *
   * @var string
   */
  const REGISTER_JS_CALLBACKS = 'entity_browser.register_js_callbacks';

  /**
   * The ALTER_BROWSER_DISPLAY_DATA allows for entity browser display plugin data
   * to be tweaked.
   *
   * @var string
   */
  const ALTER_BROWSER_DISPLAY_DATA = 'entity_browser.alter_browser_display_data';

}
