<?php

namespace Drupal\redirect_domain\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Drupal\redirect\RedirectChecker;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Redirect subscriber for controller requests.
 */
class DomainRedirectRequestSubscriber implements EventSubscriberInterface {

  /**
   * @var \Drupal\redirect\RedirectChecker
   */
  protected $redirectChecker;

  /**
   * Domain redirect configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $domainConfig;

  /**
   * The path matcher.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * Redirect configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected  $redirectConfig;

  /**
   * Constructs a \Drupal\redirect\EventSubscriber\RedirectRequestSubscriber object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\redirect\RedirectChecker $redirect_checker
   *   The redirect checker service.
   * @param \Drupal\Core\Path\PathMatcherInterface $path_matcher
   *   The path matcher.
   */
  public function __construct(ConfigFactoryInterface $config_factory, RedirectChecker $redirect_checker, PathMatcherInterface $path_matcher) {
    $this->domainConfig = $config_factory->get('redirect_domain.domains');
    $this->redirectConfig = $config_factory->get('redirect.settings');
    $this->redirectChecker = $redirect_checker;
    $this->pathMatcher = $path_matcher;
  }

  /**
   * Handles the domain redirect if any found.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event to process.
   */
  public function onKernelRequestCheckDomainRedirect(GetResponseEvent $event) {
    $request = clone $event->getRequest();

    if (!$this->redirectChecker->canRedirect($request)) {
      return;
    }

    // Redirect between domains configuration.
    $domains = $this->domainConfig->get('domain_redirects');
    if (!empty($domains)) {
      $host = $request->getHost();
      $path = $request->getPathInfo();
      $protocol = $request->getScheme() . '://';
      $destination = NULL;

      // Checks if there is a redirect domain in the configuration.
      if (isset($domains[str_replace('.', ':', $host)])) {
        foreach ($domains[str_replace('.', ':', $host)] as $item) {
          if ($this->pathMatcher->matchPath($path, $item['sub_path'])) {
            $destination = $item['destination'];
            break;
          }
        }
        if ($destination) {
          // Use the default status code from Redirect.
          $response = new TrustedRedirectResponse(
            $protocol . $destination,
            $this->redirectConfig->get('default_status_code')
          );
          $event->setResponse($response);
          return;
        }
      }
    }
  }

  /**
   * Prior to set the response it check if we can redirect.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event object.
   * @param \Drupal\Core\Url $url
   *   The Url where we want to redirect.
   */
  protected function setResponse(GetResponseEvent $event, Url $url) {
    $request = $event->getRequest();

    parse_str($request->getQueryString(), $query);
    $url->setOption('query', $query);
    $url->setAbsolute(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // This needs to run before RouterListener::onKernelRequest(), which has
    // a priority of 32 and
    // RedirectRequestSubscriber::onKernelRequestCheckRedirect(), which has
    // a priority of 33. Otherwise, that aborts the request if no matching
    // route is found.
    $events[KernelEvents::REQUEST][] = ['onKernelRequestCheckDomainRedirect', 34];
    return $events;
  }

}
