<?php

namespace Drupal\metatag\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'metatag_empty_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "metatag_empty_formatter",
 *   module = "metatag",
 *   label = @Translation("Empty formatter"),
 *   field_types = {
 *     "metatag"
 *   }
 * )
 */
class MetatagEmptyFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    // Does not actually output anything.
    return [];
  }

}
