<?php

/**
 * @file
 * Contains \Drupal\auto_entitylabel\AutoEntityLabelPermissionController.
 */

namespace Drupal\auto_entitylabel;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Config\Entity\ConfigEntityType;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic permissions of the auto_entitylabel module.
 */
class AutoEntityLabelPermissionController implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a new AutoEntityLabelPermissionController instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity.manager'));
  }

  /**
   * Returns an array of auto_entitylabel permissions
   *
   * @return array
   */
  public function autoEntityLabelPermissions() {
    $permissions = [];

    foreach ($this->entityManager->getDefinitions() as $entity_type_id => $entity_type) {
      // Create a permission for each entity type to manage the entity
      // labels.
      if ($entity_type->hasLinkTemplate('auto-label') && $entity_type->hasKey('label')) {
        $permissions['administer ' . $entity_type_id . ' labels'] = [
          'title' => $this->t('%entity_label: Administer automatic entity labels', ['%entity_label' => $entity_type->getLabel()]),
          'restrict access' => TRUE,
        ];
      }
    }
    // Create permission to use PHP in entity label patterns.
    $permissions['use PHP for auto entity labels'] = [
      'title' => $this->t('Use PHP for automatic entity label patterns'),
      'restrict access' => TRUE,
    ];
    return $permissions;
  }

}
