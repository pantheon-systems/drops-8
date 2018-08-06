<?php

namespace Drupal\entity;

use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\EntityOwnerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides generic entity permissions which are still cacheable.
 *
 * This includes:
 *
 * - administer $entity_type
 * - access $entity_type overview
 * - view $entity_type
 * - view own unpublished $entity_type
 * - update (own|any) ($bundle) $entity_type
 * - delete (own|any) ($bundle) $entity_type
 * - create $bundle $entity_type
 *
 * This class does not support "view own ($bundle) $entity_type", because this
 * results in caching per user. If you need this use case, please use
 * \Drupal\entity\UncacheableEntityPermissionProvider instead.
 *
 * Intended for content entity types, since config entity types usually rely
 * on a single "administer" permission.
 * Example annotation:
 * @code
 *  handlers = {
 *    "access" = "Drupal\entity\EntityAccessControlHandler",
 *    "permission_provider" = "Drupal\entity\EntityPermissionProvider",
 *  }
 * @endcode
 *
 * @see \Drupal\entity\EntityAccessControlHandler
 * @see \Drupal\entity\EntityPermissions
 */
class EntityPermissionProvider extends EntityPermissionProviderBase {

  /**
   * {@inheritdoc}
   */
  public function buildPermissions(EntityTypeInterface $entity_type) {
    $entity_type_id = $entity_type->id();
    $plural_label = $entity_type->getPluralLabel();

    $permissions = parent::buildPermissions($entity_type);

    // View permissions are the same for both granularities.
    $permissions["view {$entity_type_id}"] = [
      'title' => $this->t('View @type', [
        '@type' => $plural_label,
      ]),
    ];

    return $this->processPermissions($permissions, $entity_type);
  }


}
