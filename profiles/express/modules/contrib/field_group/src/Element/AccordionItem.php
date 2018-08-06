<?php

namespace Drupal\field_group\Element;

use Drupal\Core\Render\Element\RenderElement;

/**
 * Provides a render element for an accordion item.
 *
 * @FormElement("field_group_accordion_item")
 */
class AccordionItem extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return array(
      '#open' => TRUE,
      '#theme_wrappers' => array('field_group_accordion_item'),
    );
  }

}
