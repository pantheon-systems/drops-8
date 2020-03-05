<?php

namespace Drupal\pathauto;

/**
 * @todo add class comment.
 */
interface AliasCleanerInterface {

  /**
   * Clean up an URL alias.
   *
   * Performs the following alterations:
   * - Trim duplicate, leading, and trailing back-slashes.
   * - Trim duplicate, leading, and trailing separators.
   * - Shorten to a desired length and logical position based on word boundaries.
   *
   * @param string $alias
   *   A string with the URL alias to clean up.
   *
   * @return string
   *   The cleaned URL alias.
   */
  public function cleanAlias($alias);

  /**
   * Trims duplicate, leading, and trailing separators from a string.
   *
   * @param string $string
   *   The string to clean path separators from.
   * @param string $separator
   *   The path separator to use when cleaning.
   *
   * @return string
   *   The cleaned version of the string.
   *
   * @see pathauto_cleanstring()
   * @see pathauto_clean_alias()
   */
  public function getCleanSeparators($string, $separator = NULL);

  /**
   * Clean up a string segment to be used in an URL alias.
   *
   * Performs the following possible alterations:
   * - Remove all HTML tags.
   * - Process the string through the transliteration module.
   * - Replace or remove punctuation with the separator character.
   * - Remove back-slashes.
   * - Replace non-ascii and non-numeric characters with the separator.
   * - Remove common words.
   * - Replace whitespace with the separator character.
   * - Trim duplicate, leading, and trailing separators.
   * - Convert to lower-case.
   * - Shorten to a desired length and logical position based on word boundaries.
   *
   * This function should *not* be called on URL alias or path strings
   * because it is assumed that they are already clean.
   *
   * @param string $string
   *   A string to clean.
   * @param array $options
   *   (optional) A keyed array of settings and flags to control the Pathauto
   *   clean string replacement process. Supported options are:
   *   - langcode: A language code to be used when translating strings.
   *
   * @return string
   *   The cleaned string.
   */
  public function cleanString($string, array $options = []);

  /**
   * Return an array of arrays for punctuation values.
   *
   * Returns an array of arrays for punctuation values keyed by a name,
   * including the value and a textual description.
   * Can and should be expanded to include "all" non text punctuation values.
   *
   * @return array
   *   An array of arrays for punctuation values keyed by a name, including the
   *   value and a textual description.
   */
  public function getPunctuationCharacters();

  /**
   * Clean tokens so they are URL friendly.
   *
   * @param array $replacements
   *   An array of token replacements
   *   that need to be "cleaned" for use in the URL.
   * @param array $data
   *   An array of objects used to generate the replacements.
   * @param array $options
   *   An array of options used to generate the replacements.
   */
  public function cleanTokenValues(&$replacements, $data = [], $options = []);

  /**
   * Resets internal caches.
   */
  public function resetCaches();

}
