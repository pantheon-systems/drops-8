<?php

namespace Drupal\pathauto;
use Drupal\Core\Language\LanguageInterface;

/**
 * Provides an interface for alias uniquifiers.
 */
interface AliasUniquifierInterface {

  /**
   * Check to ensure a path alias is unique and add suffix variants if necessary.
   *
   * Given an alias 'content/test' if a path alias with the exact alias already
   * exists, the function will change the alias to 'content/test-0' and will
   * increase the number suffix until it finds a unique alias.
   *
   * @param string $alias
   *   A string with the alias. Can be altered by reference.
   * @param string $source
   *   A string with the path source.
   * @param string $langcode
   *   A string with a language code.
   */
  public function uniquify(&$alias, $source, $langcode);

  /**
   * Checks if an alias is reserved.
   *
   * @param string $alias
   *   The alias.
   * @param string $source
   *   The source.
   * @param string $langcode
   *   (optional) The language code.
   *
   * @return bool
   *   Returns TRUE if the alias is reserved.
   */
  public function isReserved($alias, $source, $langcode = LanguageInterface::LANGCODE_NOT_SPECIFIED);

}
