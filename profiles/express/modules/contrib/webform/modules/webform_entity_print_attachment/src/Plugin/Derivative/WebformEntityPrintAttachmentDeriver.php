<?php

namespace Drupal\webform_entity_print_attachment\Plugin\Derivative;

use Drupal\webform_entity_print\Plugin\Derivative\WebformEntityPrintWebformDeriverBase;

/**
 * Provides webform entity print attachment webform element derivatives.
 *
 * @see \Drupal\webform_entity_print_attachment\Plugin\WebformElement\WebformEntityPrintAttachmentWebformElement
 */
class WebformEntityPrintAttachmentDeriver extends WebformEntityPrintWebformDeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $definitions = $this->getDefinitions();
    foreach ($definitions as $id => $definition) {
      $t_args = ['@label' => $definition['label']];
      $this->derivatives[$id] = $base_plugin_definition;
      $this->derivatives[$id]['label'] = $this->t('Attachment @label', $t_args);
      $this->derivatives[$id]['description'] = $this->t('Generates a @label attachment.', $t_args);
    }
    return $this->derivatives;
  }

}
