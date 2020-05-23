<?php

namespace Drupal\Driver\Fields\Drupal8;

/**
 * Address field handler for Drupal 8.
 */
class AddressHandler extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function expand($values) {
    $return = [];
    $overrides = $this->fieldConfig->getSettings()['field_overrides'];
    $addressFields = [
      "given_name" => 1,
      "additional_name" => 1,
      "family_name" => 1,
      "organization" => 1,
      "address_line1" => 1,
      "address_line2" => 1,
      "postal_code" => 1,
      "sorting_code" => 1,
      "locality" => 1,
      "administrative_area" => 1,
    ];
    // Any overrides that set field inputs to hidden will be skipped.
    foreach ($overrides as $key => $value) {
      preg_match('/[A-Z]/', $key, $matches, PREG_OFFSET_CAPTURE);
      if (count($matches) > 0) {
        $fieldName = strtolower(substr_replace($key, '_', $matches[0][1], 0));
      }
      else {
        $fieldName = $key;
      }
      if ($value['override'] == 'hidden') {
        unset($addressFields[$fieldName]);
      }
    }
    // The remaining field components will be populated in order, using
    // values as they are ordered in feature step.
    foreach ($values as $value) {
      $idx = 0;
      foreach ($addressFields as $k => $v) {
        // If the values array contains only one item, assign it to the first
        // field component and break.
        if (is_string($value)) {
          $return[$k] = $value;
          break;
        }
        if ($idx < count($value)) {
          // Gracefully handle users providing too few field component values.
          $return[$k] = $value[$idx];
          $idx++;
        }
      }
      // Set the country code to the first available as configured in this
      // instance of the field.
      $return['country_code'] = reset($this->fieldConfig->getSettings()['available_countries']);
    }
    return [$return];
  }

}
