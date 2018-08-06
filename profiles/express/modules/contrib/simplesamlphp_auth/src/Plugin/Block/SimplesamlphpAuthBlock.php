<?php

namespace Drupal\simplesamlphp_auth\Plugin\Block;

use Drupal\Core\Block\BlockBase;
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
    $content = [
      '#title' => $this->t('SimpleSAMLphp Auth Status'),
      '#cache' => [
        'contexts' => ['user'],
      ],
    ];

    if ($this->simplesamlAuth->isActivated()) {

      if ($this->simplesamlAuth->isAuthenticated()) {
        $content['#markup'] = $this->t('Logged in as %authname<br /><a href=":logout">Log out</a>', [
          '%authname' => $this->simplesamlAuth->getAuthname(),
          ':logout' => Url::fromRoute('user.logout')->toString(),
        ]);
      }
      else {
        $label = $this->config->get('login_link_display_name');
        $login_link = [
          '#title' => $label,
          '#type' => 'link',
          '#url' => Url::fromRoute('simplesamlphp_auth.saml_login'),
          '#attributes' => [
            'title' => $label,
            'class' => ['simplesamlphp-auth-login-link'],
          ],
        ];
        $content['link'] = $login_link;
      }
    }
    else {
      $content['#markup'] = $this->t('Warning: SimpleSAMLphp is not activated.');
    }

    return $content;
  }

}
