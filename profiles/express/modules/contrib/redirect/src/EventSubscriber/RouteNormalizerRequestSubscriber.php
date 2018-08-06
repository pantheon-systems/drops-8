<?php

namespace Drupal\redirect\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Routing\RequestHelper;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\redirect\RedirectChecker;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Normalizes GET requests performing a redirect if required.
 *
 * The normalization can be disabled by setting the "_disable_route_normalizer"
 * request parameter to TRUE. However, this should be done before
 * onKernelRequestRedirect() method is executed.
 */
class RouteNormalizerRequestSubscriber implements EventSubscriberInterface {

  /**
   * Module specific configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The URL generator service.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * The path matcher service.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * The redirect checker service.
   *
   * @var \Drupal\redirect\RedirectChecker
   */
  protected $redirectChecker;

  /**
   * Constructs a RouteNormalizerRequestSubscriber object.
   *
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The URL generator service.
   * @param \Drupal\Core\Path\PathMatcherInterface $path_matcher
   *   The path matcher service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The config.
   * @param \Drupal\redirect\RedirectChecker $redirect_checker
   *   The redirect checker service.
   *   The value of the route_normalizer_enabled container parameter.
   */
  public function __construct(UrlGeneratorInterface $url_generator, PathMatcherInterface $path_matcher, ConfigFactoryInterface $config, RedirectChecker $redirect_checker) {
    $this->urlGenerator = $url_generator;
    $this->pathMatcher = $path_matcher;
    $this->redirectChecker = $redirect_checker;
    $this->config = $config->get('redirect.settings');
  }

  /**
   * Performs a redirect if the URL changes in routing.
   *
   * The redirect happens if a URL constructed from the current route is
   * different from the requested one. Examples:
   * - Language negotiation system detected a language to use, and that language
   *   has a path prefix: perform a redirect to the language prefixed URL.
   * - A route that's set as the front page is requested: redirect to the front
   *   page.
   * - Requested path has an alias: redirect to alias.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The Event to process.
   */
  public function onKernelRequestRedirect(GetResponseEvent $event) {

    if (!$this->config->get('route_normalizer_enabled') || !$event->isMasterRequest()) {
      return;
    }

    $request = $event->getRequest();
    if ($request->attributes->get('_disable_route_normalizer')) {
      return;
    }

    if ($this->redirectChecker->canRedirect($request)) {
      // The "<current>" placeholder can be used for all routes except the front
      // page because it's not a real route.
      $route_name = $this->pathMatcher->isFrontPage() ? '<front>' : '<current>';

      // Don't pass in the query here using $request->query->all()
      // since that can potentially modify the query parameters.
      $options = ['absolute' => TRUE];
      $redirect_uri = $this->urlGenerator->generateFromRoute($route_name, [], $options);

      // Strip off query parameters added by the route such as a CSRF token.
      if (strpos($redirect_uri, '?') !== FALSE) {
        $redirect_uri  = strtok($redirect_uri, '?');
      }

      // Append back the request query string from $_SERVER.
      $query_string = $request->server->get('QUERY_STRING');
      if ($query_string) {
        $redirect_uri .= '?' . $query_string;
      }

      // Remove /index.php from redirect uri the hard way.
      if (!RequestHelper::isCleanUrl($request)) {
        // This needs to be fixed differently.
        $redirect_uri = str_replace('/index.php', '', $redirect_uri);
      }

      $original_uri = $request->getSchemeAndHttpHost() . $request->getRequestUri();
      $original_uri = urldecode($original_uri);
      $redirect_uri = urldecode($redirect_uri);
      if ($redirect_uri != $original_uri) {
        $response = new RedirectResponse($redirect_uri, $this->config->get('default_status_code'));
        $response->headers->set('X-Drupal-Route-Normalizer', 1);
        $event->setResponse($response);
        // Disable page cache for redirects as that results in unpredictable
        // behavior, e.g. when a trailing ? without query parameters is
        // involved.
        // @todo Remove when https://www.drupal.org/node/2761639 is fixed in
        //   Drupal core.
        \Drupal::service('page_cache_kill_switch')->trigger();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('onKernelRequestRedirect', 30);
    return $events;
  }

}
