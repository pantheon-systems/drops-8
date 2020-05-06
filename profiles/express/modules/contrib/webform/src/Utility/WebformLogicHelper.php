<?php

namespace Drupal\webform\Utility;

/**
 * Provides helper to handle logic related issues.
 */
class WebformLogicHelper {

  /**
   * Track recursions.
   *
   * @var array
   */
  static private $recursionTracker = [];

  /**
   * Track recursions by counting how many times a value is called.
   *
   * @param string $value
   *   A string value typically a token.
   * @param bool $increment
   *   TRUE to increment tracking and FALSE to deincrement tracking.
   *
   * @return bool
   *   FALSE when recursion is detected.
   */
  protected static function trackRecursion($value, $increment = TRUE) {
    self::$recursionTracker += [$value => 0];
    if (self::$recursionTracker[$value] === FALSE) {
      return FALSE;
    }

    if ($increment) {
      self::$recursionTracker[$value]++;
      if (self::$recursionTracker[$value] > 100) {
        // Cancel processing by setting the recursion tracker value to FALSE.
        self::$recursionTracker[$value] = FALSE;
        throw new \LogicException(sprintf('The "%s" is being called recursively.', $value));
      }
    }
    else {
      self::$recursionTracker[$value]--;
    }
  }

  /**
   * Start recursion tracking.
   *
   * @param string $value
   *   A string value typically a token.
   *
   * @return bool
   *   FALSE when recursion is detected.
   */
  public static function startRecursionTracking($value) {
    return self::trackRecursion($value, TRUE);
  }

  /**
   * Stop recursion tracking.
   *
   * @param string $value
   *   A string value typically a token.
   *
   * @return bool
   *   FALSE when recursion is detected.
   */
  public static function stopRecursionTracking($value) {
    return self::trackRecursion($value, FALSE);
  }

}
