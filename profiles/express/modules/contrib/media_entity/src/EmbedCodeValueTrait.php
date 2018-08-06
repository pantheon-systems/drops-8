<?php

namespace Drupal\media_entity;

use Drupal\Core\Field\FieldItemInterface;

/**
 * A trait to assist with handling external embed codes.
 */
trait EmbedCodeValueTrait {

  /**
   * Extracts the raw embed code from input which may or may not be wrapped.
   *
   * @param mixed $value
   *   The input value. Can be a normal string or a value wrapped by the
   *   Typed Data API.
   *
   * @return string|null
   *   The raw embed code.
   */
  protected function getEmbedCode($value) {
    if (is_string($value)) {
      return $value;
    }
    elseif ($value instanceof FieldItemInterface) {
      $class = get_class($value);
      $property = $class::mainPropertyName();
      if ($property) {
        return $value->$property;
      }
    }
  }

}
