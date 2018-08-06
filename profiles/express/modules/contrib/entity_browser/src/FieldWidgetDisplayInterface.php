<?php

namespace Drupal\entity_browser;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the interface for entity browser field widget display plugins.
 */
interface FieldWidgetDisplayInterface extends PluginInspectionInterface, ConfigurablePluginInterface {

  /**
   * Builds and gets render array for the entity.
   *
   * @param EntityInterface $entity
   *   Entity to be displayed.
   *
   * @return array
   *   Render array that is to be used to display the entity in field widget.
   */
  public function view(EntityInterface $entity);

  /**
   * Returns a form to configure settings for the plugin.
   *
   * @param array $form
   *   The form where the settings form is being included in.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form definition for the widget settings.
   */
  public function settingsForm(array $form, FormStateInterface $form_state);

  /**
   * Returns if the FieldWidgetDisplay can be used for the provided field.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type that should be checked.
   *
   * @return bool
   *   TRUE if the FieldWidgetDisplay can be used, FALSE otherwise.
   */
  public function isApplicable(EntityTypeInterface $entity_type);

}
