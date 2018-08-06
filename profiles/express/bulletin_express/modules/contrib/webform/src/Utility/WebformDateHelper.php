<?php

namespace Drupal\webform\Utility;

use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Helper class webform date helper methods.
 */
class WebformDateHelper {

  /**
   * Wrapper for DateFormatter that return an empty string for empty timestamps.
   *
   * @param int $timestamp
   *   A UNIX timestamp to format.
   * @param string $type
   *   (optional) The data format to use.
   * @param string $format
   *   (optional) If $type is 'custom', a PHP date format string suitable for
   *   element to date(). Use a backslash to escape ordinary text, so it does not
   *   get interpreted as date format characters.
   * @param string|null $timezone
   *   (optional) Time zone identifier, as described at
   *   http://php.net/manual/timezones.php Defaults to the time zone used to
   *   display the page.
   * @param string|null $langcode
   *   (optional) Language code to translate to. NULL (default) means to use
   *   the user interface language for the page.
   *
   * @return string
   *   A translated date string in the requested format.  An empty string
   *   will be returned for empty timestamps.
   *
   * @see \Drupal\Core\Datetime\DateFormatterInterface::format
   */
  public static function format($timestamp, $type = 'fallback', $format = '', $timezone = NULL, $langcode = NULL) {
    /** @var \Drupal\Core\Datetime\DateFormatterInterface $date_formatter */
    $date_formatter = \Drupal::service('date.formatter');
    return $timestamp ? $date_formatter->format($timestamp, $type) : '';
  }

  /**
   * Format date/time object to be written to the database using 'Y-m-d\TH:i:s'.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $date
   *   A DrupalDateTime object.
   *
   * @return string
   *   The date/time object format as 'Y-m-d\TH:i:s'.
   */
  public static function formatStorage(DrupalDateTime $date) {
    return $date->format(DATETIME_DATETIME_STORAGE_FORMAT);
  }

  /**
   * Check if date/time string is using a valid date/time format.
   *
   * @param string $time
   *   A date/time string.
   * @param string $format
   *   Format accepted by date().
   *
   * @return bool
   *   TRUE is $time is in the accepted format.
   *
   * @see http://stackoverflow.com/questions/19271381/correctly-determine-if-date-string-is-a-valid-date-in-that-format
   */
  public static function isValidDateFormat($time, $format = 'Y-m-d') {
    $datetime = \DateTime::createFromFormat($format, $time);
    return ($datetime && $datetime->format($format) === $time);
  }

}
