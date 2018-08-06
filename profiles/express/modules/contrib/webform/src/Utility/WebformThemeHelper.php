<?php

namespace Drupal\webform\Utility;

/**
 * Helper class webform theme management.
 */
class WebformThemeHelper {

  /**
   * Get all active theme names.
   *
   * @return array
   *   An array containing the active theme and base theme names.
   */
  public static function getActiveThemeNames() {
    $active_theme = \Drupal::theme()->getActiveTheme();
    // Note: Reversing the order so that base themes are first.
    return array_reverse(array_merge([$active_theme->getName()], array_keys($active_theme->getBaseThemes())));
  }

  /**
   * Determine if a theme name is being used the active or base theme.
   *
   * @param string $theme_name
   *   A theme name.
   *
   * @return bool
   *   TRUE if a theme name is being used the active or base theme.
   */
  public static function isActiveTheme($theme_name) {
    return in_array($theme_name, self::getActiveThemeNames());
  }

}
