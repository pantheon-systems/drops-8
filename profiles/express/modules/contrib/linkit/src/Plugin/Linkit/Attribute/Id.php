<?php

/**
 * @file
 * Contains \Drupal\linkit\Plugin\Linkit\Attribute\Id.
 */

namespace Drupal\linkit\Plugin\Linkit\Attribute;

use Drupal\linkit\AttributeBase;

/**
 * Id attribute.
 *
 * @Attribute(
 *   id = "id",
 *   label = @Translation("Id"),
 *   html_name = "id",
 *   description = @Translation("Basic input field for the id attribute."),
 * )
 */
class Id extends AttributeBase {

  /**
   * {@inheritdoc}
   */
  public function buildFormElement($default_value) {
    return [
      '#type' => 'textfield',
      '#title' => t('Id'),
      '#default_value' => $default_value,
      '#maxlength' => 255,
      '#size' => 40,
      '#placeholder' => t('The "id" attribute value'),
    ];
  }

}
