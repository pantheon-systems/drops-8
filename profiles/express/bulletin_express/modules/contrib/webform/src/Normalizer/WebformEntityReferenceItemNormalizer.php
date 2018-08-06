<?php

namespace Drupal\webform\Normalizer;

use Drupal\hal\Normalizer\EntityReferenceItemNormalizer;
use Drupal\webform\Plugin\Field\FieldType\WebformEntityReferenceItem;

/**
 * Defines a class for normalizing WebformEntityReferenceItems.
 */
class WebformEntityReferenceItemNormalizer extends EntityReferenceItemNormalizer {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = WebformEntityReferenceItem::class;

  /**
   * {@inheritdoc}
   */
  protected function constructValue($data, $context) {
    $value = parent::constructValue($data, $context);
    if ($value) {
      $value['default_data'] = $data['default_data'];
      $value['status'] = $data['status'];
    }
    return $value;
  }

}
