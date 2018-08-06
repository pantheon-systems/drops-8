<?php

namespace Drupal\webform\Utility;

use Drupal\Component\Utility\Html;

/**
 * Helper class webform options based methods.
 */
class WebformOptionsHelper {

  /**
   * Option description delimiter.
   *
   * @var string
   */
  const DESCRIPTION_DELIMITER = ' -- ';

  /**
   * Determine if the options has a specified value..
   *
   * @param string $value
   *   An value to look for in the options.
   * @param array $options
   *   An associative array of options.
   *
   * @return bool
   *   TRUE if the options has a specified value.
   */
  public static function hasOption($value, array $options) {
    foreach ($options as $option_value => $option_text) {
      if (is_array($option_text)) {
        if ($has_value = self::hasOption($value, $option_text)) {
          return $has_value;
        }
      }
      elseif ($value == $option_value) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Replace associative array of option values with option text.
   *
   * @param array $values
   *   The option value.
   * @param array $options
   *   An associative array of options.
   *
   * @return array
   *   An associative array of option values with option text.
   */
  public static function getOptionsText(array $values, array $options) {
    foreach ($values as &$value) {
      $value = self::getOptionText($value, $options);
    }
    return $values;
  }

  /**
   * Get the text string for an option value.
   *
   * @param string $value
   *   The option value.
   * @param array $options
   *   An associative array of options.
   * @param bool $options_description
   *   Remove description which is delimited using ' -- '.
   *
   * @return string
   *   The option text if found or the option value.
   */
  public static function getOptionText($value, array $options, $options_description = FALSE) {
    foreach ($options as $option_value => $option_text) {
      if (is_array($option_text)) {
        if ($text = self::getOptionText($value, $option_text, $options_description)) {
          return $text;
        }
      }
      elseif ($value == $option_value) {
        if ($options_description && strpos($option_text, static::DESCRIPTION_DELIMITER) !== FALSE) {
          list($option_text) = explode(static::DESCRIPTION_DELIMITER, $option_text);
          return $option_text;
        }
        else {
          return $option_text;
        }
      }
    }
    return $value;
  }

  /**
   * Convert options with TranslatableMarkup into strings.
   *
   * @param array $options
   *   An associative array of options with TranslatableMarkup.
   *
   * @return string
   *   An associative array of options of strings,
   */
  public static function convertOptionsToString(array $options) {
    $string = [];
    foreach ($options as $option_value => $option_text) {
      if (is_array($option_text)) {
        $string[(string) $option_value] = self::convertOptionsToString($option_text);
      }
      else {
        $string[(string) $option_value] = (string) $option_text;
      }
    }
    return $string;
  }

  /**
   * Decode HTML entities in options.
   *
   * Issue #2826451: TermSelection returning HTML characters in select list.
   *
   * @param array $options
   *   An associative array of options.
   *
   * @return string
   *   An associative array of options with HTML entities decoded.
   */
  public static function decodeOptions(array $options) {
    foreach ($options as $option_value => $option_text) {
      if (is_array($option_text)) {
        $options[$option_value] = self::decodeOptions($option_text);
      }
      else {
        $options[$option_value] = Html::decodeEntities((string) $option_text);
      }
    }
    return $options;
  }

  /**
   * Build an associative array containing a range of options.
   *
   * @param int|string $min
   *   First value of the sequence.
   * @param int $max
   *   The sequence is ended upon reaching the end value.
   * @param int $step
   *   Increments between the range. Default value is 1.
   * @param int $pad_length
   *   Number of character to be prepended to the range.
   * @param string $pad_str
   *   The character to default the string.
   *
   * @return array
   *   An associative array containing a range of options.
   */
  public static function range($min = 1, $max = 100, $step = 1, $pad_length = NULL, $pad_str = '0') {
    // Create range.
    $range = range($min, $max, $step);

    // Pad left on range.
    if ($pad_length) {
      $range = array_map(function ($item) use ($pad_length, $pad_str) {
        return str_pad($item, $pad_length, $pad_str, STR_PAD_LEFT);
      }, $range);
    }

    // Return associative array of range options.
    return array_combine($range, $range);
  }

  /**
   * Convert options to array that can serialized to Drupal's configuration management system.
   *
   * @param array $options
   *   An associative array containing options value and text.
   *
   * @return array
   *   An array contain option text and value.
   */
  public static function encodeConfig(array $options) {
    $config = [];
    foreach ($options as $value => $text) {
      $config[] = [
        'value' => $value,
        'text' => $text,
      ];
    }
    return $config;
  }

  /**
   * Convert config from Drupal's configuration management system to options array.
   *
   * @param array $config
   *   An array contain option text and value.
   *
   * @return array
   *   An associative array containing options value and text.
   */
  public static function decodeConfig(array $config) {
    $options = [];
    foreach ($config as $option) {
      $options[$option['value']] = $option['text'];
    }
    return $options;
  }

}
