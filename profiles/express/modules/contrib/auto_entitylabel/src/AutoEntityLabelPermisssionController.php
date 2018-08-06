<?php

/**
 * @file
 * Contains \Drupal\auto_entitylabel\AutoEntityLabelPermisssionController.
 */

namespace Drupal\auto_entitylabel;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic permissions of the auto_entitylabel module.
 */
class AutoEntityLabelPermisssionController implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a new AutoEntityLabelPermisssionController instance.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
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
        // Create a permission for each fieldable entity to manage
        // the entity labels.
        $permissions['administer ' . $entity_type_id . ' labels'] = [
          'title' => $this->t('%entity_label: Administer Entity Labels', ['%entity_label' => $entity_type->getLabel()]),
          'restrict access' => TRUE,
        ];
    }

    return $permissions;
  }

}