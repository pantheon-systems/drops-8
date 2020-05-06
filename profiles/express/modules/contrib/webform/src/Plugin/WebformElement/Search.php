<?php

namespace Drupal\webform\Plugin\WebformElement;

/**
 * Provides a 'search' element.
 *
 * @WebformElement(
 *   id = "search",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Search.php/class/Search",
 *   label = @Translation("Search"),
 *   description = @Translation("Provides form element for entering a search phrase."),
 *   category = @Translation("Advanced elements"),
 * )
 */
class Search extends TextBase {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    $properties = parent::defineDefaultProperties();
    unset(
      $properties['format_items'],
      $properties['format_items_html'],
      $properties['format_items_text']
    );
    return $properties;
  }

}
