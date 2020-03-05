<?php

namespace Drupal\pathauto\Plugin\Deriver;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\token\TokenEntityMapperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Deriver that exposes content entities as alias type plugins.
 */
class EntityAliasTypeDeriver extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * @var \Drupal\token\TokenEntityMapperInterface
   */
  protected $tokenEntityMapper;

  /**
   * Constructs new EntityAliasTypeDeriver.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \Drupal\Token\TokenEntityMapperInterface $token_entity_mapper
   *   The token entity mapper.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, TranslationInterface $string_translation, TokenEntityMapperInterface $token_entity_mapper) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->stringTranslation = $string_translation;
    $this->tokenEntityMapper = $token_entity_mapper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('string_translation'),
      $container->get('token.entity_mapper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      // An entity type must have a canonical link template and support fields.
      if ($entity_type->hasLinkTemplate('canonical') && is_subclass_of($entity_type->getClass(), FieldableEntityInterface::class)) {
        $base_fields = $this->entityFieldManager->getBaseFieldDefinitions($entity_type_id);
        if (!isset($base_fields['path'])) {
          // The entity type does not have a path field and is therefore not
          // supported.
          continue;
        }
        $this->derivatives[$entity_type_id] = $base_plugin_definition;
        $this->derivatives[$entity_type_id]['label'] = $entity_type->getLabel();
        $this->derivatives[$entity_type_id]['types'] = [$this->tokenEntityMapper->getTokenTypeForEntityType($entity_type_id)];
        $this->derivatives[$entity_type_id]['provider'] = $entity_type->getProvider();
        $this->derivatives[$entity_type_id]['context'] = [
          $entity_type_id => EntityContextDefinition::fromEntityType($entity_type)->setLabel($this->t('@label being aliased', ['@label' => $entity_type->getLabel()]))
        ];
      }
    }
    return $this->derivatives;
  }

}
