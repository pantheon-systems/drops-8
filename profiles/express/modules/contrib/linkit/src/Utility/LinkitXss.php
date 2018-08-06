<?php

/**
 * @file
 * Contains \Drupal\linkit\Utility\LinkitXss.
 */

namespace Drupal\linkit\Utility;

use Drupal\Component\Utility\Xss;

/**
 * Extends the default XSS protection to simplify it for Linkits needs.
 */
class LinkitXss extends Xss {

  /**
   * Description filter helper.
   *
   * @param $string
   *   The string with raw HTML in it. It will be stripped of everything that
   *   can cause an XSS attack.
   *
   * @return string
   *   An XSS safe version of $string, or an empty string if $string is not
   *   valid UTF-8.
   *
   * @see \Drupal\Component\Utility\Xss::filter()
   */
  public static function descriptionFilter($string) {
    return parent::filter($string, ['img'] + Xss::getHtmlTagList());
  }

}
