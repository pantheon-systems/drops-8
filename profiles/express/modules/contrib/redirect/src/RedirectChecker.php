<?php

namespace Drupal\redirect;

use Drupal\Core\Access\AccessManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Cmf\Component\Routing\RouteProviderInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Redirect checker class.
 */
class RedirectChecker {

  /**
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * @var \Drupal\Core\Access\AccessManager
   */
  protected $accessManager;

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  public function __construct(ConfigFactoryInterface $config, StateInterface $state, AccessManager $access_manager, AccountInterface $account, RouteProviderInterface $route_provider) {
    $this->config = $config->get('redirect.settings');
    $this->accessManager = $access_manager;
    $this->state = $state;
    $this->account = $account;
    $this->routeProvider = $route_provider;
  }

  /**
   * Determines if redirect may be performed.
   *
   * @param Request $request
   *   The current request object.
   * @param string $route_name
   *   The current route name.
   *
   * @return bool
   *   TRUE if redirect may be performed.
   */
  public function canRedirect(Request $request, $route_name = NULL) {
    $can_redirect = TRUE;
    if (isset($route_name)) {
      $route = $this->routeProvider->getRouteByName($route_name);
      if ($this->config->get('access_check')) {
        // Do not redirect if is a protected page.
        $can_redirect = $this->accessManager->checkNamedRoute($route_name, [], $this->account);
      }
    }
    else {
      $route = $request->attributes->get(RouteObjectInterface::ROUTE_OBJECT);
    }

    if (!preg_match('/index\.php$/', $request->getScriptName())) {
      // Do not redirect if the root script is not /index.php.
      $can_redirect = FALSE;
    }
    elseif (!($request->isMethod('GET') || $request->isMethod('HEAD'))) {
      // Do not redirect if this is other than GET request.
      $can_redirect = FALSE;
    }
    elseif ($this->state->get('system.maintenance_mode') || defined('MAINTENANCE_MODE')) {
      // Do not redirect in offline or maintenance mode.
      $can_redirect = FALSE;
    }
    elseif ($request->query->has('destination')) {
      $can_redirect = FALSE;
    }
    elseif ($this->config->get('ignore_admin_path') && isset($route)) {
      // Do not redirect on admin paths.
      $can_redirect &= !(bool) $route->getOption('_admin_route');
    }

    return $can_redirect;
  }

}
