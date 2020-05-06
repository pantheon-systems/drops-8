<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides an 'entity_reference' with options trait.
 */
trait WebformEntityOptionsTrait {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    $properties = parent::defineDefaultProperties() + [
      // Entity reference settings.
      'target_type' => '',
      'selection_handler' => '',
      'selection_settings' => [],
    ];
    unset(
      $properties['options'],
      $properties['options_description_display']
    );
    if ($this->getPluginId() === 'webform_entity_radios') {
      unset(
        $properties['format_items'],
        $properties['format_items_html'],
        $properties['format_items_text']
      );
    }
    return $properties;
  }

  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    $this->setOptions($element, ['webform_submission' => $webform_submission]);
    parent::prepare($element, $webform_submission);
  }

  /**
   * {@inheritdoc}
   */
  protected function getElementSelectorInputsOptions(array $element) {
    $this->setOptions($element);
    return parent::getElementSelectorInputsOptions($element);
  }

  /**
   * {@inheritdoc}
   */
  public function getElementSelectorSourceValues(array $element) {
    $this->setOptions($element);
    return parent::getElementSelectorSourceValues($element);
  }

}
