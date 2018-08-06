<?php

namespace Drupal\simplesamlphp_auth\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\simplesamlphp_auth\Service\SimplesamlphpAuthManager;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Provides a 'SimpleSAMLphp authentication status' block.
 *
 * @Block(
 *   id = "simplesamlphp_auth_block",
 *   admin_label = @Translation("SimpleSAMLphp Auth Status"),
 * )
 */
class SimplesamlphpAuthBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * SimpleSAMLphp Authentication helper.
   *
   * @var SimplesamlphpAuthManager
   */
  protected $simplesamlAuth;

  /**
   * A configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('simplesamlphp_auth.manager'),
      $container->get('config.factory')
    );
  }

  /**
   * Creates a LocalActionsBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param SimplesamlphpAuthManager $simplesaml_auth
   *   The SimpleSAML Authentication helper service.
   * @param ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, SimplesamlphpAuthManager $simplesaml_auth, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->simplesamlAuth = $simplesaml_auth;
    $this->config = $config_factory->get('simplesamlphp_auth.settings');

  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    if ($this->simplesamlAuth->isActivated()) {
      if ($this->simplesamlAuth->isAuthenticated()) {
        $content = $this->t('Logged in as %authname<br /><a href=":logout">Log out</a>', array(
          '%authname' => $this->simplesamlAuth->getAuthname(),
          ':logout' => Url::fromRoute('user.logout')->toString(),
        ));
      }
      else {
        $label = $this->config->get('login_link_display_name');
        $content = Link::createFromRoute($label, 'simplesamlphp_auth.saml_login', array(), array(
          'attributes' => array(
            'class' => array('simplesamlphp-auth-login-link'),
          ),
        ));
      }
    }
    else {
      $content = $this->t('Warning: SimpleSAMLphp is not activated.');
    }

    return array(
      '#title' => $this->t('SimpleSAMLphp Auth Status'),
      '#markup' => $content,
      '#cache' => array(
        'contexts' => array('user'),
      ),
    );
  }

}
