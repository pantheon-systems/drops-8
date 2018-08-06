<?php

namespace Drupal\libraries\Extension;

use Drupal\Core\Extension\Extension as CoreExtension;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;

/**
 * @todo
 */
class ExtensionHandler implements ExtensionHandlerInterface {

  /**
   * The app root.
   *
   * @var string
   */
  protected $root;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * Constructs an extension handler.
   *
   * @param string $root
   *   The app root.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $themeHandler
   *   The theme handler.
   */
  public function __construct(
    $root,
    ModuleHandlerInterface $moduleHandler,
    ThemeHandlerInterface $themeHandler
  ) {
    $this->root = $root;
    $this->moduleHandler = $moduleHandler;
    $this->themeHandler = $themeHandler;
  }

  /**
   * {@inheritdoc}
   */
  public function getExtensions() {
    foreach ($this->moduleHandler->getModuleList() as $module) {
      yield $this->wrapCoreExtension($module);
    }

    foreach ($this->themeHandler->listInfo() as $theme) {
      yield $this->wrapCoreExtension($theme);
    }
  }

  /**
   * Creates a Libraries API extension object from a core extension object.
   *
   * @param \Drupal\Core\Extension\Extension $core_extension
   *   The core extension object.
   *
   * @return \Drupal\libraries\Extension\ExtensionInterface
   *   The Libraries API extension object.
   */
  protected function wrapCoreExtension(CoreExtension $core_extension) {
    return new Extension(
      $this->root,
      $core_extension->getType(),
      $core_extension->getPathname(),
      $core_extension->getExtensionFilename()
    );
  }

}
