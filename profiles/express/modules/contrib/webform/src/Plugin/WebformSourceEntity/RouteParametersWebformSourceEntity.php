<?php

namespace Drupal\webform\Plugin\WebformSourceEntity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\webform\Plugin\WebformSourceEntityInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Detect source entity by examining route parameters.
 *
 * @WebformSourceEntity(
 *   id = "route_parameters",
 *   label = @Translation("Route parameters"),
 *   weight = 100
 * )
 */
class RouteParametersWebformSourceEntity extends PluginBase implements WebformSourceEntityInterface, ContainerFactoryPluginInterface {

  /**
   * Current route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * RouteParametersWebformSourceEntity constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The "current_route_match" service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceEntity(array $ignored_types) {
    // Use current account when viewing a user's submissions.
    // @see \Drupal\webform\WebformSubmissionListBuilder
    if ($this->routeMatch->getRouteName() === 'entity.webform_submission.user') {
      return NULL;
    }

    // Get the most specific source entity available in the current route's
    // parameters.
    $parameters = $this->routeMatch->getParameters()->all();
    $parameters = array_reverse($parameters);

    if (!empty($ignored_types)) {
      $parameters = array_diff_key($parameters, array_flip($ignored_types));
    }

    foreach ($parameters as $value) {
      if ($value instanceof EntityInterface) {
        return $value;
      }
    }
    return NULL;
  }

}
