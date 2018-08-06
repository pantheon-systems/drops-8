<?php

/**
 * @file
 * Contains \Drupal\linkit\Plugin\Derivative\EntityMatcherDeriver.
 */

namespace Drupal\linkit\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 *
 * @see plugin_api
 */
class EntityMatcherDeriver extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Creates an EntityMatcherDeriver object.
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
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach ($this->entityManager->getDefinitions() as $entity_type_id => $entity_type) {
      $has_canonical = $entity_type->hasLinkTemplate('canonical');

      if ($has_canonical) {
        $this->derivatives[$entity_type_id] = $base_plugin_definition;
        $this->derivatives[$entity_type_id]['id'] = $base_plugin_definition['id'] . ':' . $entity_type_id;
        $this->derivatives[$entity_type_id]['label'] = $entity_type->getLabel();
        $this->derivatives[$entity_type_id]['target_entity'] = $entity_type_id;
        $this->derivatives[$entity_type_id]['base_plugin_label'] = (string) $base_plugin_definition['label'];
      }
    }

    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
