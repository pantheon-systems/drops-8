<?php

/**
 * @file
 * Provide views data for view_unpublished.module.
 */

/**
 * Implements hook_views_data_alter().
 */
function view_unpublished_views_data_alter(array &$data) {
  // The published status + extra handler is taken over by our handler.
  $data['node_field_data']['status_extra']['filter']['id'] = 'view_unpublished_node_status';
}
