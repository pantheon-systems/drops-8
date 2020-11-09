<?php

namespace Drupal\metatag\Plugin\Field;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;

/**
 * Defines a metatag list class for better normalization targeting.
 */
class MetatagEntityFieldItemList extends FieldItemList {

  use ComputedItemListTrait;

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    // This field does not really compute anything, it is solely used as a base
    // for normalizers.
    // @see \Drupal\metatag\Normalizer\MetatagNormalizer
    return NULL;
  }


}
