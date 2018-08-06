<?php

namespace Drupal\field_group;
use Drupal\Core\Url;
use Drupal\field_ui\FieldUI;

/**
 * Static methods for fieldgroup UI.
 */
class FieldgroupUi {

  /**
   * Get the field ui route that should be used for given arguments.
   * @param stdClass $group
   *   The group to get the field ui route for.
   *
   * @return \Drupal\Core\Url
   *   A URL object.
   */
  public static function getFieldUiRoute($group) {

    $entity_type = \Drupal::entityTypeManager()->getDefinition($group->entity_type);
    if ($entity_type->get('field_ui_base_route')) {

      $context_route_name = "";
      $mode_route_name = "default";
      $route_parameters = FieldUI::getRouteBundleParameter($entity_type, $group->bundle);

      // Get correct route name based on context and mode.
      if ($group->context == 'form') {
        $context_route_name = 'entity_form_display';

        if ($group->mode != 'default') {
          $mode_route_name = 'form_mode';
          $route_parameters['form_mode_name'] = $group->mode;
        }

      }
      else {
        $context_route_name = 'entity_view_display';

        if ($group->mode != 'default') {
          $mode_route_name = 'view_mode';
          $route_parameters['view_mode_name'] = $group->mode;
        }

      }

      return new Url("entity.{$context_route_name}.{$group->entity_type}.{$mode_route_name}", $route_parameters);
    }
  }

  /**
   * Get the field group delete route for a given group.
   * @param \stdClass $group
   *
   * @return \Drupal\Core\Url
   *   A URL object.
   */
  public static function getDeleteRoute($group) {

    $entity_type_id = $group->entity_type;
    $entity_type = \Drupal::entityTypeManager()->getDefinition($entity_type_id);
    if ($entity_type->get('field_ui_base_route')) {

      $mode_route_name = '';
      $route_parameters = FieldUI::getRouteBundleParameter($entity_type, $group->bundle);
      $route_parameters['field_group_name'] = $group->group_name;

      // Get correct route name based on context and mode.
      if ($group->context == 'form') {

        $context_route_name = 'form_display';
        if ($group->mode != 'default') {
          $mode_route_name = '.form_mode';
          $route_parameters['form_mode_name'] = $group->mode;
        }

      }
      else {

        $context_route_name = 'display';
        if ($group->mode != 'default') {
          $mode_route_name = '.view_mode';
          $route_parameters['view_mode_name'] = $group->mode;
        }

      }

      return new Url('field_ui.field_group_delete_' . $entity_type_id . '.' . $context_route_name . $mode_route_name, $route_parameters);
    }

    throw new \InvalidArgumentException('The given group is not a valid.');

  }

}