<?php

/**
 * @file
 * Contains \Drupal\linkit_test\Plugin\Linkit\Attribute\DummyAttribute.
 */

namespace Drupal\linkit_test\Plugin\Linkit\Attribute;

use Drupal\linkit\AttributeBase;

/**
 * Accesskey attribute.
 *
 * @Attribute(
 *   id = "dummy_attribute",
 *   label = @Translation("Dummy Attribute"),
 *   html_name = "dummyattribute",
 *   description = @Translation("Dummy Attribute")
 * )
 */
class DummyAttribute extends AttributeBase {

  /**
   * {@inheritdoc}
   */
  public function buildFormElement($default_value) {
    return [
      '#type' => 'textfield',
      '#title' => t('DummyAttribute'),
      '#default_value' => $default_value,
      '#maxlength' => 255,
      '#size' => 40,
    ];
  }

}
