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
  public function getDefaultProperties() {
    $properties = parent::getDefaultProperties() + [
      // Entity reference settings.
      'target_type' => '',
      'selection_handler' => '',
      'selection_settings' => [],
    ];
    unset($properties['options'], $properties['options_description_display']);
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    $this->setOptions($element);
    parent::prepare($element, $webform_submission);
  }

  /**
   * {@inheritdoc}
   */
  protected function getElementSelectorInputsOptions(array $element) {
    $this->setOptions($element);
    return parent::getElementSelectorInputsOptions($element);
  }

}
