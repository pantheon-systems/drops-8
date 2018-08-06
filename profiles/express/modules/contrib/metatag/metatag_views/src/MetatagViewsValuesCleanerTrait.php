<?php

namespace Drupal\metatag_views;

/**
 * Collection of helper methods when handling raw tag values.
 */
trait MetatagViewsValuesCleanerTrait {

  /**
   * Clears the metatag form state values from illegal elements.
   *
   * @param array $metatags
   *   Array of values to submit.
   *
   * @return array
   *   Filtered metatag array.
   */
  public function clearMetatagViewsDisallowedValues(array $metatags) {
    // Get all legal tags.
    $tags = $this->metatagManager->sortedTags();

    // Return only common elements.
    $metatags = array_intersect_key($metatags, $tags);

    return $metatags;
  }

  /**
   * Removes tags that are empty.
   */
  public function removeEmptyTags($metatags) {
    $metatags = array_filter($metatags, function ($value) {
      if (is_array($value)) {
        return count(array_filter($value)) > 0;
      }
      else {
        return $value !== '';
      }
    });
    return $metatags;
  }

}
