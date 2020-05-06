<?php

namespace Drupal\webform_entity_print\EventSubscriber;

use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event subscriber to alter requests.
 */
class WebformEntityPrintRequestSubscriber implements EventSubscriberInterface {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new WebformEntityPrintRequestSubscriber.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(RouteMatchInterface $route_match) {
    $this->routeMatch = $route_match;
  }

  /**
   * Set custom webform entity print submission view mode.
   */
  public function requestSetViewMode(GetResponseEvent $event) {
    if ($event->getRequestType() != HttpKernelInterface::MASTER_REQUEST) {
      return;
    }

    // Check if current route is an entity print view.
    $route_name = $this->routeMatch->getRouteName();
    if (!in_array($route_name, ['entity_print.view.debug', 'entity_print.view'])) {
      return;
    }

    // Get view mode from current request.
    // @see _webform_entity_print_webform_submission_links()
    $request = $event->getRequest();
    if ($view_mode = $request->query->get('view_mode')) {
      $request->request->set('_webform_submissions_view_mode', $view_mode);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::REQUEST => 'requestSetViewMode',
    ];
  }

}
