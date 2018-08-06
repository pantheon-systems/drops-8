<?php

namespace Drupal\entity_browser;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Defines the interface for entity browser widget selectors.
 *
 * This plugin type is responsible for providing ways for users to select the
 * current widget used for selecting entities in an entity browser. For example,
 * if the user wants to tab between widgets, the tab set will be created and
 * managed by the widget selector.
 */
interface WidgetSelectorInterface extends PluginInspectionInterface, ConfigurablePluginInterface, PluginFormInterface {

  /**
   * Returns the widget selector label.
   *
   * @return string
   *   The widget label.
   */
  public function label();

  /**
   * Returns widget selector form.
   *
   * @return array
   *   Form structure.
   */
  public function getForm(array &$form, FormStateInterface &$form_state);

  /**
   * Sets the default widget.
   *
   * @param string $widget
   *   Id of widget to set as the current widget.
   */
  public function setDefaultWidget($widget);

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
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   *
   * @return string
   *   The selected widget ID.
   */
  public function submit(array &$form, FormStateInterface $form_state);

}
