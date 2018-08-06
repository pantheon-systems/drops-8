<?php

namespace Drupal\menu_block\EventSubscriber;

use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Alters the block library modal.
 *
 * We can't use hook_ajax_render_alter() because the #markup is rendered before
 * it is passed to that hook.
 */
class MenuBlockKernelViewSubscriber implements EventSubscriberInterface {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRouteMatch;

  /**
   * Constructs a new MenuBlockKernelViewSubscriber.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $current_route_match
   *   The current route match.
   */
  public function __construct(RouteMatchInterface $current_route_match) {
    $this->currentRouteMatch = $current_route_match;
  }

  /**
   * Alters the block library modal.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event to process.
   */
  public function onView(GetResponseEvent $event) {
    switch ($this->currentRouteMatch->getRouteName()) {
      case 'block.admin_library':
      case 'context.reaction.blocks.library':
        // Grab the render array result before it is rendered by the
        // main_content_view_subscriber.
        $result = $event->getControllerResult();
        foreach ($result['blocks']['#rows'] as $key => $row) {
          // Remove rows for any block provided by the system_menu_block plugin.
          $routeParameters = $row['operations']['data']['#links']['add']['url']->getRouteParameters();
          $plugin_id = !empty($routeParameters['plugin_id']) ? $routeParameters['plugin_id'] : $routeParameters['block_id'];
          if (strpos($plugin_id, 'system_menu_block:') === 0) {
            unset($result['blocks']['#rows'][$key]);
          }
        }
        // Override the original render array.
        $event->setControllerResult($result);
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    // Run before main_content_view_subscriber.
    $events[KernelEvents::VIEW][] = ['onView', 1];
    return $events;
  }

}
