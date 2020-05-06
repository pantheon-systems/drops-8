<?php

namespace Drupal\webform_options_custom\Element;

use Drupal\webform\Element\WebformEntityTrait;

/**
 * Provides a webform element for a selecting custom entity references from HTML or SVG markup.
 *
 * @FormElement("webform_options_custom_entity")
 */
class WebformOptionsCustomEntity extends WebformOptionsCustom {

  use WebformEntityTrait;

  /**
   * {@inheritdoc}
   */
  public static function setTemplateOptions(array &$element) {
    if (isset($element['#_options_custom'])) {
      return;
    }

    // Remove any prefine #options so the entity reference options will be used.
    unset($element['#options']);
    parent::setTemplateOptions($element);
  }

}
