<?php

namespace Drupal\webform\Theme;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;
use Drupal\webform\WebformRequestInterface;

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
   * Webform request handler.
   *
   * @var \Drupal\webform\WebformRequestInterface
   */
  protected $requestHandler;

  /**
   * Creates a new AdminNegotiator instance.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The current user.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\webform\WebformRequestInterface $request_handler
   *   The webform request handler.
   */
  public function __construct(AccountInterface $user, ConfigFactoryInterface $config_factory, WebformRequestInterface $request_handler = NULL) {
    $this->user = $user;
    $this->configFactory = $config_factory;
    // @todo Webform 8.x-6.x: Require request handler.
    $this->requestHandler = $request_handler ?: \Drupal::service('webform.request');

  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    $route_name = $route_match->getRouteName();
    if (strpos($route_name, 'webform') === FALSE) {
      return FALSE;
    }

    $webform = $this->requestHandler->getCurrentWebform();
    if (empty($webform)) {
      return FALSE;
    }

    $is_webform_route = in_array($route_name, [
      'entity.webform.canonical',
      'entity.webform.test_form',
      'entity.webform.confirmation',
      'entity.node.webform.test_form',
    ]);
    $is_user_submission_route = (strpos($route_name, 'entity.webform.user.') === 0);

    // If page is disabled, apply admin theme to the webform routes.
    if (!$webform->getSetting('page') && $is_webform_route) {
      return ($this->user->hasPermission('view the administration theme'));
    }

    // If admin theme is enabled, apply it to webform and user submission routes.
    if ($webform->getSetting('page_admin_theme')
      && ($is_webform_route || $is_user_submission_route)) {
      return ($this->user->hasPermission('view the administration theme'));
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    return $this->configFactory->get('system.theme')->get('admin');
  }

}
