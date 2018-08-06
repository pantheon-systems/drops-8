<?php

namespace Drupal\webform\Plugin\WebformElement;

/**
 * Provides a 'container' element.
 *
 * @WebformElement(
 *   id = "container",
 *   default_key = "container",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Container.php/class/Container",
 *   label = @Translation("Container"),
 *   description = @Translation("Provides an element that wraps child elements in a container."),
 *   category = @Translation("Containers"),
 * )
 */
class Container extends ContainerBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return [
      // Attributes.
      'attributes' => [],
      // Flexbox.
      'flex' => 1,
      // Conditional logic.
      'states' => [],
      // Format.
      'format' => $this->getItemDefaultFormat(),
      'format_html' => '',
      'format_text' => '',
    ];
  }

}
