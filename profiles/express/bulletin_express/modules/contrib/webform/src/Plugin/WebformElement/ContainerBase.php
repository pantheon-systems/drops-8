<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\webform\WebformElementBase;
use Drupal\webform\WebformInterface;

/**
 * Provides a base 'container' class.
 */
abstract class ContainerBase extends WebformElementBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return [
      'title' => '',
      // General settings.
      'description' => '',
      // Form display.
      'title_display' => '',
      // Form validation.
      'required' => FALSE,
      // Attributes.
      'attributes' => [],
    ] + $this->getDefaultBaseProperties();
  }

  /**
   * {@inheritdoc}
   */
  public function isInput(array $element) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isContainer(array $element) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function build($format, array &$element, $value, array $options = []) {
    if (empty($value)) {
      return [];
    }

    return [
      '#theme' => 'webform_container_base_' . $format,
      '#element' => $element,
      '#value' => $value,
      '#options' => $options,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getItemDefaultFormat() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getItemFormats() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getTestValues(array $element, WebformInterface $webform, array $options = []) {
    // Containers should never have values and therefore should never have
    // a test value.
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getElementSelectorOptions(array $element) {
    return [];
  }

}
