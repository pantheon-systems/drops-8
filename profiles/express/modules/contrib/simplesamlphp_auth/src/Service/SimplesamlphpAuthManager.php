<?php

namespace Drupal\simplesamlphp_auth\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use SimpleSAML_Auth_Simple;
use SimpleSAML_Configuration;
use Drupal\simplesamlphp_auth\Exception\SimplesamlphpAttributeException;
use Drupal\Core\Site\Settings;

/**
 * Service to interact with the SimpleSAMLPHP authentication library.
 */
class SimplesamlphpAuthManager {

  /**
   * A configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * A SimpleSAML configuration instance.
   *
   * @var \SimpleSAML_Configuration
   */
  protected $simplesamlConfig;

  /**
   * A SimpleSAML instance.
   *
   * @var \SimpleSAML_Auth_Simple
   */
  protected $instance;

  /**
   * Attributes for federated user.
   *
   * @var array
   */
  protected $attributes;

  /**
   * {@inheritdoc}
   *
   * @param ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param SimpleSAML_Auth_Simple $instance
   *   SimpleSAML_Auth_Simple instance.
   * @param SimpleSAML_Configuration $config
   *   SimpleSAML_Configuration instance.
   */
  public function __construct(ConfigFactoryInterface $config_factory, SimpleSAML_Auth_Simple $instance = NULL, SimpleSAML_Configuration $config = NULL) {
    $this->checkLibrary();
    $this->config = $config_factory->get('simplesamlphp_auth.settings');
    if (!$instance) {
      $auth_source = $this->config->get('auth_source');
      $this->instance = new SimpleSAML_Auth_Simple($auth_source);
    }
    else {
      $this->instance = $instance;
    }

    if (!$config) {
      $this->simplesamlConfig = \SimpleSAML_Configuration::getInstance();
    }
    else {
      $this->simplesamlConfig = $config;
    }
  }

  /**
   * Forwards the user to the IdP for authentication.
   */
  public function externalAuthenticate() {
    $uri = \Drupal::request()->getUri();
    $this->instance->requireAuth(['ReturnTo' => $uri]);
  }

  /**
   * Get SimpleSAMLphp storage type.
   *
   * @return string
   *   The storage type.
   */
  public function getStorage() {
    return $this->simplesamlConfig->getValue('store.type');
  }

  /**
   * Check whether user is authenticated by the IdP.
   *
   * @return bool
   *   If the user is authenticated by the IdP.
   */
  public function isAuthenticated() {
    return $this->instance->isAuthenticated();
  }

  /**
   * Gets the unique id of the user from the IdP.
   *
   * @return string
   *   The authname.
   */
  public function getAuthname() {
    return $this->getAttribute($this->config->get('unique_id'));
  }

  /**
   * Gets the name attribute.
   *
   * @return string
   *   The name attribute.
   */
  public function getDefaultName() {
    return $this->getAttribute($this->config->get('user_name'));
  }

  /**
   * Gets the mail attribute.
   *
   * @return string
   *   The mail attribute.
   */
  public function getDefaultEmail() {
    return $this->getAttribute($this->config->get('mail_attr'));
  }

  /**
   * Gets all SimpleSAML attributes.
   *
   * @return array
   *   Array of SimpleSAML attributes.
   */
  public function getAttributes() {
    if (!$this->attributes) {
      $this->attributes = $this->instance->getAttributes();
    }
    return $this->attributes;
  }

  /**
   * Get a specific SimpleSAML attribute.
   *
   * @param string $attribute
   *   The name of the attribute.
   *
   * @return mixed|bool
   *   The attribute value or FALSE.
   *
   * @throws SimplesamlphpAttributeException
   *   Exception when attribute is not set.
   */
  public function getAttribute($attribute) {
    $attributes = $this->getAttributes();

    if (isset($attributes)) {
      if (!empty($attributes[$attribute][0])) {
        return $attributes[$attribute][0];
      }
    }

    throw new SimplesamlphpAttributeException(sprintf('Error in simplesamlphp_auth.module: no valid "%s" attribute set.', $attribute));
  }

  /**
   * Asks all modules if current federated user is allowed to login.
   *
   * @return bool
   *   Returns FALSE if at least one module returns FALSE.
   */
  public function allowUserByAttribute() {
    $attributes = $this->getAttributes();
    foreach (\Drupal::moduleHandler()->getImplementations('simplesamlphp_auth_allow_login') as $module) {
      if (\Drupal::moduleHandler()->invoke($module, 'simplesamlphp_auth_allow_login', [$attributes]) === FALSE) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Checks if SimpleSAMLphp_auth is enabled.
   *
   * @return bool
   *   Whether SimpleSAMLphp authentication is enabled or not.
   */
  public function isActivated() {
    if ($this->config->get('activate') == 1) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Log a user out through the SimpleSAMLphp instance.
   *
   * @param string $redirect_path
   *   The path to redirect to after logout.
   */
  public function logout($redirect_path = NULL) {
    if (!$redirect_path) {
      $redirect_path = base_path();
    }
    $this->instance->logout($redirect_path);
  }

  /**
   * Check if the SimpleSAMLphp library can be found.
   *
   * Fallback for when the library was not found via Composer.
   */
  protected function checkLibrary() {
    if (!class_exists('SimpleSAML_Configuration')) {
      $dir = Settings::get('simplesamlphp_dir');
      include_once $dir . '/lib/_autoload.php';
    }
  }

}
