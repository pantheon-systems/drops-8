<?php

namespace Drupal\webform_options_custom\Plugin\WebformElement;

use Drupal\webform\Plugin\WebformElementEntityOptionsInterface;
use Drupal\webform\Plugin\WebformElement\WebformEntityReferenceTrait;
use Drupal\webform\Plugin\WebformElement\WebformEntityOptionsTrait;

/**
 * Provides a custom options entity reference element.
 *
 * @WebformElement(
 *   id = "webform_options_custom_entity",
 *   label = @Translation("Custom entity reference"),
 *   description = @Translation("Provides a form element for creating custom options using HTML and SVG markup with entity references."),
 *   category = @Translation("Custom elements"),
 *   deriver = "Drupal\webform_options_custom\Plugin\Derivative\WebformOptionsCustomEntityDeriver"
 * )
 */
class WebformOptionsCustomEntity extends WebformOptionsCustom implements WebformElementEntityOptionsInterface {

  use WebformEntityReferenceTrait;
  use WebformEntityOptionsTrait;

  /**
   * {@inheritdoc}
   */
  protected function setOptions(array &$element, array $settings = []) {
    list($type, $options_custom) = explode(':', $this->getPluginId());
    $element['#type'] = $type;
    $element['#options_custom'] = $options_custom;

    /** @var \Drupal\webform_options_custom\Element\WebformOptionsCustomInterface $class */
    $class = $this->getFormElementClassDefinition();
    $class::setTemplateOptions($element);
  }

}
