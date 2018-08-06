<?php

namespace Drupal\view_unpublished;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\Entity\NodeType;

/**
 * Provides dynamic permissions for viewing unpublished nodes per type.
 */
class ViewUnpublishedPermissions {

  use StringTranslationTrait;

  /**
   * Returns an array of view unpublished permissions per node type.
   *
   * @return array
   *   The node type view unpublished permissions.
   *
   * @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function permissions() {
    $perms = array();
    // Generate view unpublished permissions for all node types.
    foreach (NodeType::loadMultiple() as $type) {
      $perms += $this->buildPermissions($type);
    }

    return $perms;
  }

  /**
   * Returns a list of view unpublished permissions for a given node type.
   *
   * @param \Drupal\node\Entity\NodeType $type
   *   The node type.
   *
   * @return array
   *   An associative array of permission names and descriptions.
   */
  protected function buildPermissions(NodeType $type) {
    $type_id = $type->id();
    $type_params = array('%type_name' => $type->label());

    return array(
      "view any unpublished $type_id content" => array(
        'title' => $this->t('%type_name: View any unpublished content', $type_params),
      ),
    );
  }

}
