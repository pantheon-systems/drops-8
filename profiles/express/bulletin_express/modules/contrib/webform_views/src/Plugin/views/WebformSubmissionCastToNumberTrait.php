<?php

namespace Drupal\webform_views\Plugin\views;

/**
 * Trait to cast a SQL expression to numeric data type.
 */
trait WebformSubmissionCastToNumberTrait {

  /**
   * Cast a given SQL snippet to data type expected by this handler.
   *
   * @param string $value
   *   SQL snippet that should be casted
   *
   * @return string
   *   SQL snippet that casts provided $value to necessary data type
   */
  protected function castToDataType($value) {
    return "($value + 0)";
  }

}
