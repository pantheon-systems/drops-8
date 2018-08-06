<?php

/**
 * @file
 * Contains \Drupal\linkit\Plugin\Linkit\Attribute\Relationship.
 */

namespace Drupal\linkit\Plugin\Linkit\Attribute;

use Drupal\linkit\AttributeBase;

/**
 * Relationship attribute.
 *
 * @Attribute(
 *   id = "relationship",
 *   label = @Translation("Relationship"),
 *   html_name = "rel",
 *   description = @Translation("Basic input field for the relationship attribute."),
 * )
 */
class Relationship extends AttributeBase {

  /**
   * {@inheritdoc}
   */
  public function buildFormElement($default_value) {
    return [
      '#type' => 'textfield',
      '#title' => t('Relationship'),
      '#default_value' => $default_value,
      '#maxlength' => 255,
      '#size' => 40,
      '#placeholder' => t('The "rel" attribute value'),
    ];
  }

}
