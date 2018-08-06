<?php

/**
 * @file
 * Contains \Drupal\linkit\Plugin\Linkit\Attribute\Accesskey.
 */

namespace Drupal\linkit\Plugin\Linkit\Attribute;

use Drupal\linkit\AttributeBase;

/**
 * Accesskey attribute.
 *
 * @Attribute(
 *   id = "accesskey",
 *   label = @Translation("Accesskey"),
 *   html_name = "accesskey",
 *   description = @Translation("Basic input field for the accesskey attribute.")
 * )
 */
class Accesskey extends AttributeBase {

  /**
   * {@inheritdoc}
   */
  public function buildFormElement($default_value) {
    return [
      '#type' => 'textfield',
      '#title' => t('Accesskey'),
      '#default_value' => $default_value,
      '#maxlength' => 255,
      '#size' => 40,
      '#placeholder' => t('The "accesskey" attribute value'),
    ];
  }

}
