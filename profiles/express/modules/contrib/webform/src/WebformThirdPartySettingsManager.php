<?php

namespace Drupal\webform;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Webform third party settings manager.
 */
class WebformThirdPartySettingsManager implements WebformThirdPartySettingsManagerInterface {

  use StringTranslationTrait;

  /**
   * The configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The path validator.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * The webform add-ons manager.
   *
   * @var \Drupal\webform\WebformAddonsManagerInterface
   */
  protected $addonsManager;

  /**
   * The Webform module's default configuration settings.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Constructs a WebformThirdPartySettingsManager.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler class to use for loading includes.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The path validator.
   * @param \Drupal\webform\WebformAddonsManagerInterface $addons_manager
   *   The webform add-ons manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, PathValidatorInterface $path_validator, WebformAddonsManagerInterface $addons_manager) {
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
    $this->pathValidator = $path_validator;
    $this->addonsManager = $addons_manager;

    $this->config = $this->configFactory->getEditable('webform.settings');
    $this->loadIncludes();
  }

  /**
   * {@inheritdoc}
   */
  public function alter($type, &$data, &$context1 = NULL, &$context2 = NULL) {
    $this->moduleHandler->alter($type, $data, $context1, $context2);
  }

  /**
   * Load all third party settings includes.
   *
   * @see {module}/{module}.webform.inc
   * @see {module}/webform/{module}.webform.inc
   * @see webform/webform.{module}.inc
   */
  protected function loadIncludes() {
    $modules = array_keys($this->moduleHandler->getModuleList());
    foreach ($modules as $module) {
      $this->moduleHandler->loadInclude($module, 'webform.inc');
      $this->moduleHandler->loadInclude($module, 'webform.inc', "webform/$module");
      $this->moduleHandler->loadInclude('webform', "inc", "third_party_settings/webform.$module");
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setThirdPartySetting($module, $key, $value) {
    $this->config->set("third_party_settings.$module.$key", $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getThirdPartySetting($module, $key, $default = NULL) {
    $value = $this->config->get("third_party_settings.$module.$key");
    return (isset($value)) ? $value : $default;
  }

  /**
   * {@inheritdoc}
   */
  public function getThirdPartySettings($module) {
    $this->config->get("third_party_settings.$module") ?: [];
  }

  /**
   * {@inheritdoc}
   */
  public function unsetThirdPartySetting($module, $key) {
    $this->config->clear("third_party_settings.$module.$key");
    // If the third party is no longer storing any information, completely
    // remove the array holding the settings for this module.
    if (!$this->config->get("third_party_settings.$module")) {
      $this->config->clear("third_party_settings.$module");
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getThirdPartyProviders() {
    $third_party_settings = $this->config->get('third_party_settings') ?: [];
    return array_keys($third_party_settings);
  }

}
