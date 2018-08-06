<?php

namespace Drupal\entity_browser;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the interface for entity browser displays.
 *
 * Display plugins determine how a complete entity browser is delivered to the
 * user. They wrap around and encapsulate the entity browser. Examples include:
 *
 * - Displaying the entity browser on its own standalone page.
 * - Displaying the entity browser in an iframe.
 * - Displaying the entity browser in a modal dialog box.
 */
interface DisplayInterface extends PluginInspectionInterface, ConfigurablePluginInterface, PluginFormInterface {

  /**
   * Returns the display label.
   *
   * @return string
   *   The display label.
   */
  public function label();

  /**
   * Displays entity browser.
   *
   * This is the "entry point" for every non-entity browser code to interact
   * with it. It will take care about displaying entity browser in one way or
   * another.
   *
   * @param array $element
   *   A form element array containing basic properties for the entity browser
   *   element:
   *   - #eb_parents: The 'parents' space for the field in the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   * @param array $complete_form
   *   The form structure where entity browser is being attached to.
   * @param array $persistent_data
   *   (optional) Extra information to send to the Entity Browser Widget. This
   *   is needed as the widget may display after a new bootstrap, which would
   *   discard the current form state. Arbitrary values can be added and used
   *   by widgets, if needed.
   *   Expected array keys:
   *     @type \Drupal\Core\Entity\EntityInterface[] $selected_entities
   *       An array of currently selected entities.
   *     @type array $validators
   *       An associative array mapping EntityBrowserWidgetValidation IDs to
   *       an array of options to pass to the plugin's validate method.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function displayEntityBrowser(array $element, FormStateInterface $form_state, array &$complete_form, array $persistent_data = []);

  /**
   * Indicates completed selection.
   *
   * Entity browser will call this function when selection is done. Display
   * plugin is responsible for fetching selected entities and sending them to
   * the initiating code.
   *
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   Array of selected entities.
   */
  public function selectionCompleted(array $entities);

  /**
   * Gets the uuid for this display.
   *
   * @return string
   *   The uuid string.
   */
  public function getUuid();

  /**
   * Sets the uuid for this display.
   *
   * @param string $uuid
   *   The uuid string.
   */
  public function setUuid($uuid);

}
