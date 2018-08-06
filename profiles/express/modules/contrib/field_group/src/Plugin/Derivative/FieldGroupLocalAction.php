<?php

namespace Drupal\field_group\Plugin\Derivative;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Plugin\Derivative\DeriverBase;

/**
 * Provides local action definitions for all entity bundles.
 */
class FieldGroupLocalAction extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a FieldUiLocalAction object.
   *
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider to load routes by name.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(RouteProviderInterface $route_provider, EntityTypeManagerInterface $entity_type_manager) {
    $this->routeProvider = $route_provider;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('router.route_provider'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = array();

    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if ($entity_type->get('field_ui_base_route')) {

        $default_options = [
          'title' => $this->t('Add group'),
        ];

        $this->derivatives['field_group_add_' . $entity_type_id . '_form_display'] = [
          'route_name' => "field_ui.field_group_add_$entity_type_id.form_display",
          'appears_on' => [
            "entity.entity_form_display.{$entity_type_id}.default",
          ],
        ] + $default_options;

        $this->derivatives['field_group_add_' . $entity_type_id . '_form_display_form_mode'] = [
          'route_name' => "field_ui.field_group_add_$entity_type_id.form_display.form_mode",
          'appears_on' => [
            "entity.entity_form_display.{$entity_type_id}.form_mode",
          ],
        ] + $default_options;

        $this->derivatives['field_group_add_' . $entity_type_id . '_display'] = [
          'route_name' => "field_ui.field_group_add_$entity_type_id.display",
          'appears_on' => [
            "entity.entity_view_display.{$entity_type_id}.default",
          ],
        ] + $default_options;

        $this->derivatives['field_group_add_' . $entity_type_id . '_display_view_mode'] = [
          'route_name' => "field_ui.field_group_add_$entity_type_id.display.view_mode",
          'appears_on' => [
            "entity.entity_view_display.{$entity_type_id}.view_mode",
          ],
        ] + $default_options;
      }
    }

    foreach ($this->derivatives as &$entry) {
      $entry += $base_plugin_definition;
    }

    return $this->derivatives;
  }

}
