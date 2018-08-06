<?php

/**
 * @file
 * Contains \Drupal\auto_entitylabel\EntityDecorator.
 */

namespace Drupal\auto_entitylabel;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Utility\Token;

/**
 * Provides an content entity decorator for automatic label generation.
 */
class EntityDecorator implements EntityDecoratorInterface {

  /**
   * The content entity that is decorated.
   *
   * @var ContentEntityInterface
   */
  protected $entity;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Automatic label configuration for the entity.
   *
   * @var array
   */
  protected $config;

  /**
   * Constructs an EntityDecorator object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Configuration factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager
   * @param \Drupal\Core\Utility\Token $token
   *   Token manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, Token $token) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->token = $token;
  }

  /**
   * {@inheritdoc}
   */
  public function decorate(ContentEntityInterface $entity) {
    $this->entity = new AutoEntityLabelManager($entity, $this->configFactory, $this->entityTypeManager, $this->token);
    return $this->entity;
  }
}
