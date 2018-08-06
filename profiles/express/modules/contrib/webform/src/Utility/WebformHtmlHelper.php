<?php

namespace Drupal\webform\Utility;

/**
 * Provides HTML helper functions.
 */
class WebformHtmlHelper {

  /**
   * Determine if a string value contains HTML markup or entities.
   *
   * @param string $string
   *   A string.
   *
   * @return bool
   *   TRUE if the string value contains HTML markup or entities.
   */
  public static function containsHtml($string) {
    return (preg_match('/(<[a-z][^>]*>|&(?:[a-z]+|#\d+);)/i', $string)) ? TRUE : FALSE;
  }

  /**
   * Check if text has HTML (block level) tags.
   *
   * @param string $string
   *   Text that may contain HTML tags.
   *
   * @return bool
   *   TRUE is text contains HTML (block level) tags.
   */
  public static function hasBlockTags($string) {
    $re_block = '/<(?:table|thead|tfoot|caption|colgroup|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|select|form|blockquote|address|p|h[1-6]|hr|br)/i';
    return (preg_match($re_block, $string)) ? TRUE : FALSE;
  }

}
