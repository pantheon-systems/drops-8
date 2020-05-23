<?php

namespace Drupal\Driver\Fields\Drupal7;

/**
 * ListText field handler for Drupal 7.
 */
class ListTextHandler extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function expand($values) {
    $return = [];
    $allowed_values = [];
    if (!empty($this->fieldInfo['settings']['allowed_values_function'])) {
      $cacheable = TRUE;
      $callback = $this->fieldInfo['settings']['allowed_values_function'];
      $options = call_user_func($callback, $this->fieldInfo, $this, $this->entityType, $this->entity, $cacheable);
    }
    else {
      $options = $this->fieldInfo['settings']['allowed_values'];
    }
    foreach ($values as $value) {
      if (array_key_exists($value, $options)) {
        $allowed_values[$value] = $value;
      }
      elseif (in_array($value, $options)) {
        $key = array_search($value, $options);
        $allowed_values[$value] = $key;
      }
    }
    foreach ($values as $value) {
      $return[$this->language][] = ['value' => $allowed_values[$value]];
    }
    return $return;
  }

}
