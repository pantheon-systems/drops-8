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
 * Provides generic entity permissions which are cached per user.
 *
 * This includes:
 *
 * - administer $entity_type
 * - access $entity_type overview
 * - view an ($bundle) $entity_type
 * - view own ($bundle) $entity_type
 * - view own unpublished $entity_type
 * - update (own|any) ($bundle) $entity_type
 * - delete (own|any) ($bundle) $entity_type
 * - create $bundle $entity_type
 *
 * As this class supports "view own ($bundle) $entity_type" it is just cacheable
 * per user, which might harm performance of sites. Given that please use 
 * \Drupal\entity\EntityPermissionProvider unless you need the feature, or your
 * entity type is not really user facing (commerce orders for example).
 *
 * Intended for content entity types, since config entity types usually rely
 * on a single "administer" permission.
 * Example annotation:
 * @code
 *  handlers = {
 *    "access" = "Drupal\entity\UncacheableEntityAccessControlHandler",
 *    "permission_provider" = "Drupal\entity\UncacheableEntityPermissionProvider",
 *  }
 * @endcode
 *
 * @see \Drupal\entity\EntityAccessControlHandler
 * @see \Drupal\entity\EntityPermissions
 */
class UncacheableEntityPermissionProvider extends EntityPermissionProviderBase {

  /**
   * Builds permissions for the entity_type granularity.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return array
   *   The permissions.
   */
  protected function buildEntityTypePermissions(EntityTypeInterface $entity_type) {
    $permissions = parent::buildEntityTypePermissions($entity_type);

    $entity_type_id = $entity_type->id();
    $has_owner = $entity_type->entityClassImplements(EntityOwnerInterface::class);
    $plural_label = $entity_type->getPluralLabel();

    if ($has_owner) {
      $permissions["view any {$entity_type_id}"] = [
        'title' => $this->t('View any @type', [
          '@type' => $plural_label,
        ]),
      ];
      $permissions["view own {$entity_type_id}"] = [
        'title' => $this->t('View own @type', [
          '@type' => $plural_label,
        ]),
      ];
    }
    else {
      $permissions["view any {$entity_type_id}"] = [
        'title' => $this->t('View any @type', [
          '@type' => $plural_label,
        ]),
      ];
    }

    return $permissions;
  }

  /**
   * Builds permissions for the bundle granularity.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return array
   *   The permissions.
   */
  protected function buildBundlePermissions(EntityTypeInterface $entity_type) {
    $permissions = parent::buildBundlePermissions($entity_type);
    $entity_type_id = $entity_type->id();
    $bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);
    $has_owner = $entity_type->entityClassImplements(EntityOwnerInterface::class);
    $plural_label = $entity_type->getPluralLabel();

    $permissions["view any {$entity_type_id}"] = [
      'title' => $this->t('View any @type', [
        '@type' => $plural_label,
      ]),
    ];
    if ($has_owner) {
      $permissions["view own {$entity_type_id}"] = [
        'title' => $this->t('View own @type', [
          '@type' => $plural_label,
        ]),
      ];
    }

    foreach ($bundles as $bundle_name => $bundle_info) {
      if ($has_owner) {
        $permissions["view any {$bundle_name} {$entity_type_id}"] = [
          'title' => $this->t('@bundle: View any @type', [
            '@bundle' => $bundle_info['label'],
            '@type' => $plural_label,
          ]),
        ];
        $permissions["view own {$bundle_name} {$entity_type_id}"] = [
          'title' => $this->t('@bundle: View own @type', [
            '@bundle' => $bundle_info['label'],
            '@type' => $plural_label,
          ]),
        ];
      }
      else {
        $permissions["view any {$bundle_name} {$entity_type_id}"] = [
          'title' => $this->t('@bundle: View any @type', [
            '@bundle' => $bundle_info['label'],
            '@type' => $plural_label,
          ]),
        ];
      }
    }

    return $permissions;
  }

}
