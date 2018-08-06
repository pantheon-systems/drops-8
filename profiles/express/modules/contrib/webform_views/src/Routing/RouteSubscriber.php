<?php

namespace Drupal\webform_views\Routing;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\State\StateInterface;
use Symfony\Component\Routing\RouteCollection;

/**
 * Alter the routes in order to allow multiple views on the same route.
 *
 * Alter the routes so '{webform}' parameter is present and defaults to the
 * corresponding webform for all views that are related to webform submissions.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * @var StateInterface
   */
  protected $state;

  /**
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * RouteSubscriber constructor.
   */
  public function __construct(StateInterface $state, EntityTypeManagerInterface $entity_type_manager) {
    $this->state = $state;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $view_route_names = $this->state->get('views.view_route_names');

    foreach (webform_views_applicable_views() as $trio) {
      list($view_id, $display_id, $path) = $trio;

      $route_name = $view_route_names[$view_id . '.' . $display_id];

      if (($route = $collection->get($route_name)) && ($webform_id = webform_views_webform_id_from_path($path))) {
        $route->setPath($route->getPath() . '/{webform}');
        $route->setDefault('webform', $webform_id);
        $options = $route->getOptions();
        $options['parameters']['webform']['type'] = 'entity:webform';
        $route->setOptions($options);
      }
    }
  }

}
