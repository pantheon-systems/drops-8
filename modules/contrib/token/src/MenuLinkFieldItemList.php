<?php

namespace Drupal\token;

use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;

/**
 * Defines a menu link list class for storen menu link information.
 *
 * @see token_entity_base_field_info()
 */
class MenuLinkFieldItemList extends EntityReferenceFieldItemList {

  use ComputedItemListTrait;

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    // This field does not really compute anything, it is used to store
    // the referenced menu link.
    return NULL;
  }

}
