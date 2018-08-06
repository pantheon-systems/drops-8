<?php

namespace Drupal\auto_entitylabel\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AutoEntityLabelConfigTask extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The entity manager
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * Creates an FieldUiLocalTask object.
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
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = array();

    foreach ($this->entityManager->getDefinitions() as $entity_type_id => $entity_type) {
       // Special handling of Taxonomy. See https://www.drupal.org/node/2822546
       if ($entity_type_id == "taxonomy_vocabulary") {
         $base_route = "entity.{$entity_type_id}.overview_form";
       }
       else {
         $base_route = "entity.{$entity_type_id}.edit_form";
       }
       if ($entity_type->hasLinkTemplate('auto-label')) {
        $this->derivatives["$entity_type_id.auto_label_tab"] = array(
          'route_name' => "entity.{$entity_type_id}.auto_label",
          'title' => 'Automatic label',
          'base_route' => $base_route,
          'weight' => 100,
        );
      }
    }

    foreach ($this->derivatives as &$entry) {
      $entry += $base_plugin_definition;
    }
    return $this->derivatives;
  }
}
