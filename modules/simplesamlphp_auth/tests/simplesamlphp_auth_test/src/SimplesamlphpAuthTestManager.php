<?php

/**
 * @file
 * Contains \Drupal\simplesamlphp_auth_test\SimplesamlphpAuthTestManager.
 */

namespace Drupal\simplesamlphp_auth_test;

use Drupal\simplesamlphp_auth\Service\SimplesamlphpAuthManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use SimpleSAML_Auth_Simple;
use SimpleSAML_Configuration;

/**
 * Mock SimplesamlphpAuthManager class for testing purposes.
 */
class SimplesamlphpAuthTestManager extends SimplesamlphpAuthManager {

  /**
   * Keeps track of whether the user is authenticated.
   *
   * @var bool
   */
  protected $authenticated;

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
    $this->config = $config_factory->get('simplesamlphp_auth.settings');
    $this->authenticated = FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function externalAuthenticate() {
    $this->authenticated = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getStorage() {
    return 'sql';
  }

  /**
   * {@inheritdoc}
   */
  public function isAuthenticated() {
    return $this->authenticated;
  }

  /**
   * {@inheritdoc}
   */
  public function getAttributes() {
    return [
      'uid' => [0 => 'saml_user'],
      'displayName' => [0 => 'Test Saml User'],
      'mail' => [0 => 'saml@example.com'],
      'roles' => [0 => ['employee', 'test_role']],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function logout($redirect_path = NULL) {
    $this->authenticated = FALSE;
    return FALSE;
  }

}
