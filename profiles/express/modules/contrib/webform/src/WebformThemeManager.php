<?php

namespace Drupal\webform;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Core\Theme\ThemeInitializationInterface;

/**
 * Defines a class to manage webform theming.
 */
class WebformThemeManager implements WebformThemeManagerInterface {

  use StringTranslationTrait;

  /**
   * The configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * The theme initialization.
   *
   * @var \Drupal\Core\Theme\ThemeInitializationInterface
   */
  protected $themeInitialization;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Contains the current active theme.
   *
   * @var \Drupal\Core\Theme\ActiveTheme
   */
  protected $activeTheme;

  /**
   * Constructs a WebformTokenManager object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   * @param \Drupal\Core\Theme\ThemeInitializationInterface $theme_initialization
   *   The theme initialization.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   *
   * @todo Webform 8.x-6.x: Move $route_match first.
   */
  public function __construct(ConfigFactoryInterface $config_factory, RendererInterface $renderer, ThemeManagerInterface $theme_manager, ThemeHandlerInterface $theme_handler, ThemeInitializationInterface $theme_initialization, RouteMatchInterface $route_match = NULL) {
    $this->configFactory = $config_factory;
    $this->renderer = $renderer;
    $this->themeManager = $theme_manager;
    $this->themeHandler = $theme_handler;
    $this->themeInitialization = $theme_initialization;
    $this->routeMatch = $route_match ?: \Drupal::routeMatch();
  }

  /**
   * Get a theme's name.
   *
   * @return string
   *   A theme's name
   */
  public function getThemeName($name) {
    return $this->themeHandler->getName($name);
  }

  /**
   * Get themes as associative array.
   *
   * @return array
   *   An associative array containing theme name.
   */
  public function getThemeNames() {
    $themes = ['' => $this->t('Default')];
    foreach ($this->themeHandler->listInfo() as $name => $theme) {
      if ($theme->status === 1) {
        $themes[$name] = $theme->info['name'];
      }
    }
    return $themes;
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveThemeNames() {
    $active_theme = $this->themeManager->getActiveTheme();
    // Note: Reversing the order so that base themes are first.
    return array_reverse(array_merge([$active_theme->getName()], array_keys($active_theme->getBaseThemeExtensions())));
  }

  /**
   * {@inheritdoc}
   */
  public function hasActiveTheme() {
    // If there is no route match, then there is no active theme.
    // If there is no route match the admin theme can't be initialized.
    // @see \Drupal\Core\Theme\ThemeManager::initTheme
    // @see \Drupal\Core\Theme\ThemeNegotiator::determineActiveTheme
    // @see \Drupal\user\Theme\AdminNegotiator::applies
    return (\Drupal::routeMatch()->getRouteName()) ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isActiveTheme($theme_name) {
    return in_array($theme_name, $this->getActiveThemeNames());
  }

  /**
   * {@inheritdoc}
   */
  public function setCurrentTheme($theme_name = NULL) {
    if (!isset($this->activeTheme)) {
      $this->activeTheme = $this->themeManager->getActiveTheme();
    }
    $current_theme_name = $theme_name ?: $this->configFactory->get('system.theme')->get('default');
    $current_theme = $this->themeInitialization->getActiveThemeByName($current_theme_name);
    $this->themeManager->setActiveTheme($current_theme);
  }

  /**
   * {@inheritdoc}
   */
  public function setActiveTheme() {
    if ($this->activeTheme) {
      $this->themeManager->setActiveTheme($this->activeTheme);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render(array &$elements, $theme_name = NULL) {
    if ($theme_name !== NULL) {
      $this->setCurrentTheme($theme_name);
    }
    $markup = $this->renderer->render($elements);
    if ($theme_name !== NULL) {
      $this->setActiveTheme();
    }
    return $markup;
  }

  /**
   * {@inheritdoc}
   */
  public function renderPlain(array &$elements, $theme_name = NULL) {
    if ($theme_name !== NULL) {
      $this->setCurrentTheme($theme_name);
    }
    $markup = $this->renderer->renderPlain($elements);
    if ($theme_name !== NULL) {
      $this->setActiveTheme();
    }
    return $markup;
  }

}
