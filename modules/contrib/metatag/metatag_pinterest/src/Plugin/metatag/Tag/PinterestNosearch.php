<?php

namespace Drupal\metatag_pinterest\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Pinterest "nosearch" meta tag.
 *
 * @MetatagTag(
 *   id = "pinterest_nosearch",
 *   label = @Translation("No search"),
 *   description = @Translation("Do not allow Pinterest visual search to happen from this page."),
 *   name = "pinterest",
 *   group = "pinterest",
 *   weight = 3,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class PinterestNosearch extends MetaNameBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $element = []) {
    $form = [
      '#type' => 'checkbox',
      '#title' => $this->label(),
      '#description' => $this->description(),
      '#default_value' => ($this->value === 'nosearch') ?: '',
      '#required' => isset($element['#required']) ? $element['#required'] : FALSE,
      '#element_validate' => [[get_class($this), 'validateTag']],
      '#return_value' => 'nosearch',
    ];

    return $form;
  }

}
