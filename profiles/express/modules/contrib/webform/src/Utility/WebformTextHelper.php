<?php

namespace Drupal\webform\Utility;

use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;

/**
 * Provides helper to operate on strings.
 */
class WebformTextHelper {

  /**
   * CamelCase to Underscore name converter.
   *
   * @var \Symfony\Component\Serializer\NameConverter\NameConverterInterface
   */
  protected static $converter;

  /**
   * Get camel case to snake case converter.
   *
   * @return \Symfony\Component\Serializer\NameConverter\NameConverterInterface
   *   Camel case to snake case converter.
   */
  protected static function getCamelCaseToSnakeCaseNameConverter() {
    if (!isset(static::$converter)) {
      static::$converter = new CamelCaseToSnakeCaseNameConverter();
    }
    return static::$converter;
  }

  /**
   * Converts camel case to snake case (i.e. underscores).
   *
   * @param string $string
   *   String to be converted.
   *
   * @return string
   *   String with camel case converted to snake case.
   */
  public static function camelToSnake($string) {
    return static::getCamelCaseToSnakeCaseNameConverter()->normalize($string);
  }

  /**
   * Converts snake case to camel case.
   *
   * @param string $string
   *   String to be converted.
   *
   * @return string
   *   String with snake case converted to camel case.
   */
  public static function snakeToCamel($string) {
    return static::getCamelCaseToSnakeCaseNameConverter()->denormalize($string);
  }

  /**
   * Counts the number of words inside a string.
   *
   * This counts the number of words by counting the space between the words.
   *
   * str_word_count() is locale dependent and returns varying word counts
   * based on the current language.
   *
   * This approach matches how the jQuery Text Counter Plugin counts words.
   *
   * @param string $string
   *   The string.
   *
   * @return int
   *   The number of words inside the string.
   *
   * @see str_word_count()
   * @see https://github.com/ractoon/jQuery-Text-Counter
   * @see $.textcounter.wordCount
   */
  public static function wordCount($string) {
    return count(explode(' ', preg_replace('#\s+#', ' ', trim($string))));
  }

}
