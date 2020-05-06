<?php

namespace Drupal\webform\Utility;

use Drupal\Core\Datetime\DateHelper;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\OptGroup;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

/**
 * Helper class webform date helper methods.
 */
class WebformDateHelper {

  /**
   * Cached interval options.
   *
   * @var array
   */
  protected static $intervalOptions;

  /**
   * Cached interval options flattened.
   *
   * @var array
   */
  protected static $intervalOptionsFlattened;

  /**
   * Wrapper for DateFormatter that return an empty string for empty timestamps.
   *
   * @param int $timestamp
   *   A UNIX timestamp to format.
   * @param string $type
   *   (optional) The data format to use.
   * @param string $format
   *   (optional) If $type is 'custom', a PHP date format string suitable for
   *   element to date(). Use a backslash to escape ordinary text, so it does
   *   not get interpreted as date format characters.
   * @param string|null $timezone
   *   (optional) Time zone identifier, as described at
   *   http://php.net/manual/timezones.php Defaults to the time zone used to
   *   display the page.
   * @param string|null $langcode
   *   (optional) Language code to translate to. NULL (default) means to use
   *   the user interface language for the page.
   *
   * @return string
   *   A translated date string in the requested format. An empty string will be
   *   returned for empty timestamps.
   *
   * @see \Drupal\Core\Datetime\DateFormatterInterface::format
   */
  public static function format($timestamp, $type = 'fallback', $format = '', $timezone = NULL, $langcode = NULL) {
    /** @var \Drupal\Core\Datetime\DateFormatterInterface $date_formatter */
    $date_formatter = \Drupal::service('date.formatter');
    return $timestamp ? $date_formatter->format($timestamp, $type, $format, $timezone, $langcode) : '';
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
    return $date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
  }

  /**
   * Get days of the week.
   *
   * @return array
   *   Associative array of days of the week.
   */
  public static function getDaysOfWeek() {
    return [
      '0' => t('Sunday'),
      '1' => t('Monday'),
      '2' => t('Tuesday'),
      '3' => t('Wednesday'),
      '4' => t('Thursday'),
      '5' => t('Friday'),
      '6' => t('Saturday'),
    ];
  }

  /**
   * Creates a date object from an input format with a translated date string.
   *
   * @param string $format
   *   PHP date() type format for parsing the input.
   * @param mixed $time
   *   A date string.
   * @param mixed $timezone
   *   PHP DateTimeZone object, string or NULL allowed.
   * @param array $settings
   *   An array of settings.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime|bool
   *   A new DateTimePlus object or FALSE if invalid date string.
   *
   * @see \Drupal\Core\Datetime\DrupalDateTime::__construct
   */
  public static function createFromFormat($format, $time, $timezone = NULL, array $settings = []) {
    $english_time = WebformDateHelper::convertDateStringToEnglish($format, $time);
    try {
      return DrupalDateTime::createFromFormat($format, $english_time, $timezone, $settings);
    }
    catch (\Exception $exception) {
      return FALSE;
    }
  }

  /**
   * Get interval options used by submission limits.
   *
   * @return array
   *   An associative array of interval options.
   */
  public static function getIntervalOptions() {
    self::initIntervalOptions();
    return self::$intervalOptions;
  }

  /**
   * Get interval options used by submission limits.
   *
   * @return array
   *   An associative array of interval options.
   */
  public static function getIntervalOptionsFlattened() {
    self::initIntervalOptions();
    return self::$intervalOptionsFlattened;
  }

  /**
   * Get interval text.
   *
   * @param int|null $interval
   *   An interval.
   *
   * @return string
   *   An intervals' text.
   */
  public static function getIntervalText($interval) {
    $interval = ((string) $interval) ?: '';
    $intervals = self::getIntervalOptionsFlattened();
    return (isset($intervals[$interval])) ? $intervals[$interval] : $intervals[''];
  }

  /**
   * Initialize interval options used by submission limits.
   */
  protected static function initIntervalOptions() {
    if (!isset(self::$intervalOptions)) {
      $options = ['' => t('ever')];

      // Seconds.
      $seconds_optgroup = (string) t('Second');
      $increment = 0;
      while ($increment < 55) {
        $increment += 5;
        $options[$seconds_optgroup][($increment)] = t('every @increment seconds', ['@increment' => $increment]);
      }

      // Minute.
      $minute = 60;
      $minute_optgroup = (string) t('Minute');
      $options[$minute_optgroup][$minute] = t('every minute');
      $increment = 5;
      while ($increment < 55) {
        $increment += 5;
        $options[$minute_optgroup][($increment * $minute)] = t('every @increment minutes', ['@increment' => $increment]);
      }

      // Hour.
      $hour = $minute * 60;
      $hour_optgroup = (string) t('Hour');
      $options[$hour_optgroup][$hour] = t('every hour');
      $increment = 1;
      while ($increment < 23) {
        $increment += 1;
        $options[$hour_optgroup][($increment * $hour)] = t('every @increment hours', ['@increment' => $increment]);
      }

      // Day.
      $day = $hour * 24;
      $day_optgroup = (string) t('Day');
      $options[$day_optgroup][$day] = t('every day');
      $increment = 1;
      while ($increment < 6) {
        $increment += 1;
        $options[$day_optgroup][($increment * $day)] = t('every @increment days', ['@increment' => $increment]);
      }

      // Week.
      $week = $day * 7;
      $week_optgroup = (string) t('Week');
      $options[$week_optgroup][$week] = t('every week');
      $increment = 1;
      while ($increment < 51) {
        $increment += 1;
        $options[$week_optgroup][($increment * $week)] = t('every @increment weeks', ['@increment' => $increment]);
      }

      // Year.
      $year = $day * 365;
      $year_optgroup = (string) t('Year');
      $options[$year_optgroup][$year] = t('every year');
      $increment = 1;
      while ($increment < 10) {
        $increment += 1;
        $options[$year_optgroup][($increment * $year)] = t('every @increment years', ['@increment' => $increment]);
      }

      self::$intervalOptions = $options;
      self::$intervalOptionsFlattened = OptGroup::flattenOptions($options);
    }
  }

  /**
   * Convert date string to English so that it can be parsed.
   *
   * @param string $format
   *   PHP date() type format for parsing the input.
   * @param string $value
   *   A date string.
   *
   * @return string
   *   A date string translated to English.
   *
   * @see https://stackoverflow.com/questions/36498186/php-datetimecreatefromformat-and-multi-languages
   * @see core/modules/locale/locale.datepicker.js
   */
  protected static function convertDateStringToEnglish($format, $value) {
    // Do not convert English date strings.
    if (\Drupal::languageManager()->getCurrentLanguage()->getId() === 'en') {
      return $value;
    }

    // F = A full textual representation of a month, such as January or March.
    if (strpos($format, 'F') !== FALSE) {
      $month_names_untranslated = DateHelper::monthNamesUntranslated();
      $month_names_translated = DateHelper::monthNames();
      foreach ($month_names_untranslated as $index => $month_name_untranslated) {
        $value = str_ireplace((string) $month_names_translated[$index], $month_name_untranslated, $value);
      }

    }

    // M =	A short textual representation of a month, three letters.
    if (strpos($format, 'M') !== FALSE) {
      $month_names_abbr_untranslated = DateHelper::monthNamesAbbrUntranslated();
      $month_names_abbr_translated = DateHelper::monthNamesAbbr();
      foreach ($month_names_abbr_untranslated as $index => $month_name_abbr_untranslated) {
        $value = str_ireplace((string) $month_names_abbr_translated[$index], $month_name_abbr_untranslated, $value);
      }
    }

    // l = A full textual representation of the day of the week.
    if (strpos($format, 'l') !== FALSE) {
      $week_days_untranslated = DateHelper::weekDaysUntranslated();
      $week_days_translated = DateHelper::weekDays();
      foreach ($week_days_untranslated as $index => $week_day_untranslated) {
        $value = str_ireplace((string) $week_days_translated[$index], $week_day_untranslated, $value);
      }
    }

    // D = A textual representation of a day, three letters.
    if (strpos($format, 'D') !== FALSE) {
      $week_days_abbr_untranslated = DateHelper::weekDaysUntranslated();
      $week_days_abbr_translated = DateHelper::weekDaysAbbr();
      foreach ($week_days_abbr_untranslated as $index => $week_day_abbr_untranslated) {
        $week_days_abbr_untranslated[$index] = (string) substr($week_days_abbr_untranslated[$index], 0, 3);
        $value = str_ireplace((string) $week_days_abbr_translated[$index], $week_days_abbr_untranslated[$index], $value);
      }
    }

    return $value;
  }

}
