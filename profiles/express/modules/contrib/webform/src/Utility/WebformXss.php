<?php

namespace Drupal\webform\Utility;

use Drupal\Component\Utility\Xss;

/**
 * Provides webform helper to filter for cross-site scripting.
 */
class WebformXss {

  /**
   * Gets the list of HTML tags allowed by Xss::filterAdmin() with missing <label>, <fieldset>, <legend>, <font> tags.
   *
   * @return array
   *   The list of HTML tags allowed by filterAdmin() with missing
   *   <label>, <fieldset>, <legend>, <font> tags.
   */
  public static function getAdminTagList() {
    $allowed_tags = Xss::getAdminTagList();
    $allowed_tags[] = 'label';
    $allowed_tags[] = 'fieldset';
    $allowed_tags[] = 'legend';
    $allowed_tags[] = 'font';
    return $allowed_tags;
  }

  /**
   * Gets the standard list of HTML tags allowed by Xss::filter() with missing <font> tag.
   *
   * @return array
   *   The list of HTML tags allowed by Xss::filter() with missing <font> tag.
   */
  public static function getHtmlTagList() {
    $allowed_tags = Xss::getHtmlTagList();
    $allowed_tags[] = 'font';
    return $allowed_tags;
  }

}
