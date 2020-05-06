<?php

namespace Drupal\webform\Utility;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Render\Markup;
use Drupal\webform\Element\WebformHtmlEditor;

/**
 * Provides HTML helper functions.
 */
class WebformHtmlHelper {

  /**
   * Convert HTML markup to plain text.
   *
   * @param string $string
   *   Text that may contain HTML markup and encode characters.
   *
   * @return string
   *   Text with HTML markup removed and special characters decoded.
   */
  public static function toPlainText($string) {
    if (static::containsHtml($string)) {
      $string = strip_tags($string);
      $string = Html::decodeEntities($string);
      return $string;
    }
    else {
      return $string;
    }
  }

  /**
   * Convert string to safe HTML markup.
   *
   * @param string $string
   *   Text to be converted to safe HTML markup.
   * @param array $html_tags
   *   An array of HTML tags.
   *
   * @return \Drupal\Component\Render\MarkupInterface|string
   *   Safe HTML markup or a plain text string.
   */
  public static function toHtmlMarkup($string, array $html_tags = NULL) {
    $html_tags = $html_tags ?: WebformHtmlEditor::getAllowedTags();
    if (static::containsHtml($string)) {
      return Markup::create(Xss::filter($string, $html_tags));
    }
    else {
      return $string;
    }
  }

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
