<?php

namespace Drupal\webform;

/**
 * Defines an interface for theme manager classes.
 */
interface WebformThemeManagerInterface {

  /**
   * Get a theme's name.
   *
   * @return string
   *   A theme's name
   */
  public function getThemeName($name);

  /**
   * Get themes as associative array.
   *
   * @return array
   *   An associative array containing theme name.
   */
  public function getThemeNames();

  /**
   * Get all active theme names.
   *
   * @return array
   *   An array containing the active theme and base theme names.
   */
  public function getActiveThemeNames();

  /**
   * Determine if the current request has an active theme.
   *
   * @return bool
   *   TRUE if the current request has an active theme.
   */
  public function hasActiveTheme();

  /**
   * Determine if a theme name is being used the active or base theme.
   *
   * @param string $theme_name
   *   A theme name.
   *
   * @return bool
   *   TRUE if a theme name is being used the active or base theme.
   */
  public function isActiveTheme($theme_name);

  /**
   * Sets the current theme the theme.
   *
   * @param string $theme_name
   *   (optional) A theme name. Defaults the default theme.
   */
  public function setCurrentTheme($theme_name = NULL);

  /**
   * Sets the current theme the active theme.
   */
  public function setActiveTheme();

  /**
   * Renders HTML given a structured array tree.
   *
   * @param array $elements
   *   The structured array describing the data to be rendered.
   * @param bool $default_theme
   *   Render using the default theme. Defaults to TRUE.
   *
   * @return \Drupal\Component\Render\MarkupInterface
   *   The rendered HTML.
   */
  public function render(array &$elements, $default_theme = TRUE);

  /**
   * Renders using the default theme final HTML in situations where no assets are needed.
   *
   * @param array $elements
   *   The structured array describing the data to be rendered.
   * @param bool $default_theme
   *   Render using the default theme. Defaults to TRUE.
   *
   * @return \Drupal\Component\Render\MarkupInterface
   *   The rendered HTML.
   */
  public function renderPlain(array &$elements, $default_theme = TRUE);

}
