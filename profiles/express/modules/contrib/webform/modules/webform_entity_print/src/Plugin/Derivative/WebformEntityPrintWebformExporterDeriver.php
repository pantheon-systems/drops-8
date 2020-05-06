<?php

namespace Drupal\webform_entity_print\Plugin\Derivative;

/**
 * Provides webform entity print attachment webform exporter derivatives.
 *
 * @see \Drupal\webform_entity_print\Plugin\WebformExporter\WebformEntityPrintWebformExporter
 */
class WebformEntityPrintWebformExporterDeriver extends WebformEntityPrintWebformDeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $definitions = $this->getDefinitions();
    foreach ($definitions as $id => $definition) {
      $t_args = ['@label' => $definition['label']];
      $this->derivatives[$id] = $base_plugin_definition;
      $this->derivatives[$id]['id'] = $id;
      $this->derivatives[$id]['label'] = $this->t('@label documents', $t_args);
      $this->derivatives[$id]['description'] = $this->t('Exports results as @label documents');
    }
    return $this->derivatives;
  }

}
