<?php

namespace Drupal\metatag\Normalizer;

/**
 * Converts the Metatag field item object structure to Metatag array structure.
 */
class MetatagHalNormalizer extends MetatagNormalizer {

  /**
   * {@inheritdoc}
   */
  protected $format = ['hal_json'];

  /**
   * {@inheritdoc}
   */
  public function normalize($field_item, $format = NULL, array $context = []) {
    $normalized = parent::normalize($field_item, $format, $context);

    // Mock the field array similar to the other fields.
    return [
      'metatag' => [$normalized],
    ];
  }

}
