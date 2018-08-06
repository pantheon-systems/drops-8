<?php

/**
 * @file
 * Contains \Drupal\linkit\Plugin\Linkit\Attribute\Clazz.
 */

namespace Drupal\linkit\Plugin\Linkit\Attribute;

use Drupal\linkit\AttributeBase;

/**
 * Class attribute.
 *
 * @TODO: For now Drupal filter_html wont support class attributes with
 * wildcards.
 * See: \Drupal\filter\Plugin\Filter\FilterHtml::getHTMLRestrictions
 * See: core/modules/filter/filter.filter_html.admin.js
 *
 * @Attribute(
 *   id = "class",
 *   label = @Translation("Class"),
 *   html_name = "class",
 *   description = @Translation("Basic input field for the class attribute."),
 * )
 */
//class Clazz extends AttributeBase {
//
//  /**
//   * {@inheritdoc}
//   */
//  public function buildFormElement($default_value) {
//    return [
//      '#type' => 'textfield',
//      '#title' => t('Class'),
//      '#maxlength' => 255,
//      '#size' => 40,
//    ];
//  }
//
//}
