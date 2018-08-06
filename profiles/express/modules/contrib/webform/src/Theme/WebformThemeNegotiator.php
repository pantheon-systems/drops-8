<?php

namespace Drupal\webform\Theme;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;

/**
 * Sets the admin theme on a webform that does not have a public canonical URL.
 */
class WebformThemeNegotiator implements ThemeNegotiatorInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $user;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Creates a new AdminNegotiator instance.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The current user.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(AccountInterface $user, ConfigFactoryInterface $config_factory) {
    $this->user = $user;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    if ($route_match->getRouteName() !== 'entity.webform.canonical' && $route_match->getRouteName() !== 'entity.webform.test_form') {
      return FALSE;
    }

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $route_match->getParameter('webform');
    if ($webform->getSetting('page')) {
      return FALSE;
    }

    return ($this->user->hasPermission('view the administration theme'));
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    return $this->configFactory->get('system.theme')->get('admin');
  }

}
