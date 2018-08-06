<?php

namespace Drupal\entity_browser;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining an entity browser entity.
 */
interface EntityBrowserInterface extends ConfigEntityInterface {

  /**
   * Gets the entity browser name.
   *
   * @return string
   *   The name of the entity browser.
   */
  public function getName();

  /**
   * Sets the name of the entity browser.
   *
   * @param string $name
   *   The name of the entity browser.
   *
   * @return \Drupal\entity_browser\EntityBrowserInterface
   *   The class instance this method is called on.
   */
  public function setName($name);

  /**
   * Sets the label of the entity browser.
   *
   * @param string $label
   *   The label of the entity browser.
   *
   * @return \Drupal\entity_browser\EntityBrowserInterface
   *   The class instance this method is called on.
   */
  public function setLabel($label);

  /**
   * Sets the id of the display plugin.
   *
   * @param string $display
   *   The id of the display plugin.
   *
   * @return \Drupal\entity_browser\EntityBrowserInterface
   *   The class instance this method is called on.
   */
  public function setDisplay($display);

  /**
   * Sets the id of the widget selector plugin.
   *
   * @param string $display
   *   The id of the widget selector plugin.
   *
   * @return \Drupal\entity_browser\EntityBrowserInterface
   *   The class instance this method is called on.
   */
  public function setWidgetSelector($widget_selector);

  /**
   * Sets the id of the selection display plugin.
   *
   * @param string $display
   *   The id of the selection display plugin.
   *
   * @return \Drupal\entity_browser\EntityBrowserInterface
   *   The class instance this method is called on.
   */
  public function setSelectionDisplay($selection_display);

  /**
   * Returns the display.
   *
   * @return \Drupal\entity_browser\DisplayInterface
   *   The display.
   */
  public function getDisplay();

  /**
   * Returns a specific widget.
   *
   * @param string $widget
   *   The widget ID.
   *
   * @return \Drupal\entity_browser\WidgetInterface
   *   The widget object.
   */
  public function getWidget($widget);

  /**
   * Returns the widgets for this entity browser.
   *
   * @return \Drupal\entity_browser\WidgetsLazyPluginCollection
   *   The tag plugin bag.
   */
  public function getWidgets();

  /**
   * Saves a widget for this entity browser.
   *
   * @param array $configuration
   *   An array of widget configuration.
   *
   * @return string
   *   The widget ID.
   */
  public function addWidget(array $configuration);

  /**
   * Deletes a widget from this entity browser.
   *
   * @param \Drupal\entity_browser\WidgetInterface $widget
   *   The widget object.
   *
   * @return $this
   */
  public function deleteWidget(WidgetInterface $widget);

  /**
   * Gets first widget based on weights.
   *
   * @return string
   *   First widget instance ID.
   */
  public function getFirstWidget();

  /**
   * Adds paramterers that will be passed to the widget.
   *
   * @param array $parameters
   *   An array of parameters.
   *
   * @return $this
   */
  public function addAdditionalWidgetParameters(array $parameters);

  /**
   * Gets additional parameters that will be passed to the widget.
   *
   * @return array
   *   Array of parameters.
   */
  public function getAdditionalWidgetParameters();

  /**
   * Returns the selection display.
   *
   * @return \Drupal\entity_browser\SelectionDisplayInterface
   *   The display.
   */
  public function getSelectionDisplay();

  /**
   * Returns the widget selector.
   *
   * @return \Drupal\entity_browser\WidgetSelectorInterface
   *   The widget selector.
   */
  public function getWidgetSelector();

  /**
   * Gets route that matches this display.
   *
   * @return \Symfony\Component\Routing\Route|bool
   *   Route object or FALSE if no route is used.
   */
  public function route();

  /**
   * Gets entity browser form object.
   *
   * @return \Drupal\entity_browser\EntityBrowserFormInterface
   *   Entity browser form.
   */
  public function getFormObject();

}
