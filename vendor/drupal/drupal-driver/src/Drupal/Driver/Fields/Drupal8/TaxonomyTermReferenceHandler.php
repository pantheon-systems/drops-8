<?php

namespace Drupal\Driver\Fields\Drupal8;

/**
 * Field handler for taxonomy term references in Drupal 8.
 */
class TaxonomyTermReferenceHandler extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function expand($values) {
    $return = [];
    foreach ($values as $name) {
      $terms = \Drupal::entityTypeManager()
        ->getStorage('taxonomy_term')
        ->loadByProperties(['name' => $name]);
      if ($terms) {
        $return[] = array_shift($terms)->id();
      }
      else {
        throw new \Exception(sprintf("No term '%s' exists.", $name));
      }
    }
    return $return;
  }

}
