<?php

namespace Drupal\diff;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * Transforms an entity into an array of strings for diff.
 */
class DiffEntityParser {

  /**
   * The diff field builder plugin manager.
   *
   * @var \Drupal\diff\DiffBuilderManager
   */
  protected $diffBuilderManager;

  /**
   * Wrapper object for simple configuration from diff.settings.yml.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Wrapper object for simple configuration from diff.plugins.yml.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $pluginsConfig;

  /**
   * Constructs a DiffEntityParser object.
   *
   * @param \Drupal\diff\DiffBuilderManager $diff_builder_manager
   *   The diff builder manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(DiffBuilderManager $diff_builder_manager, ConfigFactoryInterface $config_factory) {
    $this->config = $config_factory->get('diff.settings');
    $this->pluginsConfig = $config_factory->get('diff.plugins');
    $this->diffBuilderManager = $diff_builder_manager;
  }

  /**
   * Transforms an entity into an array of strings.
   *
   * Parses an entity's fields and for every field it builds an array of string
   * to be compared. Basically this function transforms an entity into an array
   * of strings.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   An entity containing fields.
   *
   * @return array
   *   Array of strings resulted by parsing the entity.
   */
  public function parseEntity(ContentEntityInterface $entity) {
    $result = array();
    $langcode = \Drupal::languageManager()->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
    // Load entity of current language, otherwise fields are always compared by
    // their default language.
    if ($entity->hasTranslation($langcode)) {
      $entity = $entity->getTranslation($langcode);
    }
    $entity_type_id = $entity->getEntityTypeId();
    // Loop through entity fields and transform every FieldItemList object
    // into an array of strings according to field type specific settings.
    /** @var \Drupal\Core\Field\FieldItemListInterface $field_items */
    foreach ($entity as $field_items) {
      // Define if the current field should be displayed as a diff change.
      $show_diff = $this->diffBuilderManager->showDiff($field_items->getFieldDefinition()->getFieldStorageDefinition());
      if (!$show_diff || !$entity->get($field_items->getFieldDefinition()->getName())->access('view')) {
        continue;
      }
      // Create a plugin instance for the field definition.
      $plugin = $this->diffBuilderManager->createInstanceForFieldDefinition($field_items->getFieldDefinition());
      if ($plugin) {
        // Create the array with the fields of the entity. Recursive if the
        // field contains entities.
        if ($plugin instanceof FieldReferenceInterface) {
          foreach ($plugin->getEntitiesToDiff($field_items) as $entity_key => $reference_entity) {
            foreach ($this->parseEntity($reference_entity) as $key => $build) {
              $result[$key] = $build;
              $result[$key]['label'] = $field_items->getFieldDefinition()->getLabel() . ' > ' . $result[$key]['label'];
            };
          }
        }
        else {
          $build = $plugin->build($field_items);
          if (!empty($build)) {
            $result[$entity->id() . ':' . $entity_type_id . '.' . $field_items->getName()] = $build;
            $result[$entity->id() . ':' . $entity_type_id . '.' . $field_items->getName()]['label'] = $field_items->getFieldDefinition()->getLabel();
          }
        }
      }
    }

    $this->diffBuilderManager->clearCachedDefinitions();
    return $result;
  }

}
