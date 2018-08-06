<?php

namespace Drupal\config_update;

use Drupal\Core\Config\ExtensionInstallStorage;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;

/**
 * Provides methods related to config listing, including provider calculation.
 */
class ConfigListerWithProviders extends ConfigLister implements ConfigListByProviderInterface {

  /**
   * List of providers of config, keyed by config item name.
   *
   * The array elements are each arrays, with the type of extension providing
   * the config object as the first element (module, theme, profile), and the
   * name of the provider as the second element.
   *
   * This is not set up until ConfigListerWithProviders::listProviders() has
   * been called.
   *
   * @var array
   */
  protected $providers = [];

  /**
   * List of extensions that provide configuration, keyed by type.
   *
   * This is not set up until ConfigListerWithProviders::listProviders() has
   * been called.
   *
   * @var array
   */
  protected $extensionsWithConfig = [];

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
   * Constructs a ConfigListerWithProviders.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Config\StorageInterface $active_config_storage
   *   The active config storage.
   * @param \Drupal\Core\Config\ExtensionInstallStorage $extension_config_storage
   *   The extension config storage.
   * @param \Drupal\Core\Config\ExtensionInstallStorage $extension_optional_config_storage
   *   The extension config storage for optional config items.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager, StorageInterface $active_config_storage, ExtensionInstallStorage $extension_config_storage, ExtensionInstallStorage $extension_optional_config_storage, ModuleHandlerInterface $module_handler, ThemeHandlerInterface $theme_handler) {
    $this->entityManager = $entity_manager;
    $this->activeConfigStorage = $active_config_storage;
    $this->extensionConfigStorage = $extension_config_storage;
    $this->extensionOptionalConfigStorage = $extension_optional_config_storage;
    $this->moduleHandler = $module_handler;
    $this->themeHandler = $theme_handler;
  }

  /**
   * Sets up and returns the config providers list.
   */
  public function listProviders() {
    // Return the list if it has already been set up.
    if (count($this->providers)) {
      return $this->providers;
    }

    // Calculate if it hasn't been set up yet.
    // List all of the profile, modules, and themes.
    $extensionsToDo = [];
    $profile = $this->getProfileName();
    $extensionsToDo[] = ['profile', $profile];
    $modules = $this->moduleHandler->getModuleList();
    foreach ($modules as $machine_name => $module) {
      if ($machine_name != $profile) {
        $extensionsToDo[] = ['module', $machine_name];
      }
    }

    $themes = $this->themeHandler->listInfo();
    foreach ($themes as $machine_name => $theme) {
      $extensionsToDo[] = ['theme', $machine_name];
    }

    // For each extension, figure out if it has config, and make an index of
    // config item => provider.
    $this->extensionsWithConfig = [
      'profile' => [],
      'module' => [],
      'theme' => [],
    ];

    foreach ($extensionsToDo as $item) {
      $type = $item[0];
      $name = $item[1];
      $configs = array_merge($this->listProvidedItems($type, $name),
        $this->listProvidedItems($type, $name, TRUE));
      if (!empty($configs)) {
        $this->extensionsWithConfig[$type][$name] = $name;
        foreach ($configs as $config) {
          $this->providers[$config] = [$type, $name];
        }
      }
    }

    return $this->providers;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigProvider($name) {
    $providers = $this->listProviders();

    return (isset($providers[$name])) ? $providers[$name] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function providerHasConfig($type, $name) {
    // Make sure the list of extensions with configuration has been generated.
    $this->listProviders();

    return isset($this->extensionsWithConfig[$type][$name]);
  }

}
