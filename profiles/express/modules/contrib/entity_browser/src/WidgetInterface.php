<?php

namespace Drupal\entity_browser;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Defines the interface for entity browser widgets.
 *
 * Entity browser widgets present a form for actually selecting entities in an
 * entity browser. Once entities have been selected, they are delivered upstream
 * to the entity browser's selection display plugin.
 */
interface WidgetInterface extends PluginInspectionInterface, ConfigurablePluginInterface, PluginFormInterface {

  /**
   * Returns the widget id.
   *
   * @return string
   *   The widget id.
   */
  public function id();

  /**
   * Returns the widget UUID.
   *
   * @return string
   *   The widget UUID.
   */
  public function uuid();

  /**
   * Returns the widget label.
   *
   * @return string
   *   The widget label.
   */
  public function label();

  /**
   * Sets the widget's label.
   *
   * @param string $label
   *   New plugin label.
   *
   * @return \Drupal\entity_browser\WidgetInterface
   *   This object.
   */
  public function setLabel($label);

  /**
   * Returns the widget's weight.
   *
   * @return int
   *   Widget's weight.
   */
  public function getWeight();

  /**
   * Sets the widget's weight.
   *
   * @param int $weight
   *   New plugin weight.
   *
   * @return \Drupal\entity_browser\WidgetInterface
   *   This object.
   */
  public function setWeight($weight);

  /**
   * Returns widget form.
   *
   * @param array $original_form
   *   Entire form bult up to this point. Form elements for widget should generally
   *   not be added directly to it but returned from funciton as a separated
   *   unit.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   * @param array $additional_widget_parameters
   *   Additional parameters that we want to pass to the widget.
   *
   * @return array
   *   Form structure.
   */
  public function getForm(array &$original_form, FormStateInterface $form_state, array $additional_widget_parameters);

  /**
   * Validates form.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   */
  public function validate(array &$form, FormStateInterface $form_state);

  /**
   * Submits form.
   *
   * @param array $element
   *   Widget part of the form.
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   */
  public function submit(array &$element, array &$form, FormStateInterface $form_state);

  /**
   * Returns if widget requires JS commands support by selection display.
   *
   * @return bool
   *   True is auto selection is enabled and add/remove of entities will be done
   *   over javascript events on selection display.
   */
  public function requiresJsCommands();

}
