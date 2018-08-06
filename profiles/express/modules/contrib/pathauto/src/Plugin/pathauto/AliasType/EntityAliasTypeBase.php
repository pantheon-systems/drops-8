<?php

namespace Drupal\pathauto\Plugin\pathauto\AliasType;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\ContextAwarePluginBase;
use Drupal\pathauto\AliasTypeBatchUpdateInterface;
use Drupal\pathauto\AliasTypeInterface;
use Drupal\pathauto\PathautoState;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A pathauto alias type plugin for entities with canonical links.
 *
 * @AliasType(
 *   id = "canonical_entities",
 *   deriver = "\Drupal\pathauto\Plugin\Deriver\EntityAliasTypeDeriver"
 * )
 */
class EntityAliasTypeBase extends ContextAwarePluginBase implements AliasTypeInterface, AliasTypeBatchUpdateInterface, ContainerFactoryPluginInterface {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The key/value manager service.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueFactoryInterface
   */
  protected $keyValue;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The path prefix for this entity type.
   *
   * @var string
   */
  protected $prefix;

  /**
   * Constructs a EntityAliasTypeBase instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager service.
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $key_value
   *   The key/value manager service.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ModuleHandlerInterface $module_handler, LanguageManagerInterface $language_manager, EntityTypeManagerInterface $entity_type_manager, KeyValueFactoryInterface $key_value, Connection $database) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moduleHandler = $module_handler;
    $this->languageManager = $language_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->keyValue = $key_value;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler'),
      $container->get('language_manager'),
      $container->get('entity_type.manager'),
      $container->get('keyvalue'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    $definition = $this->getPluginDefinition();
    // Cast the admin label to a string since it is an object.
    // @see \Drupal\Core\StringTranslation\TranslationWrapper
    return (string) $definition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getTokenTypes() {
    $definition = $this->getPluginDefinition();
    return $definition['types'];
  }

  /**
   * {@inheritdoc}
   */
  public function batchUpdate($action, &$context) {
    if (!isset($context['sandbox']['current'])) {
      $context['sandbox']['count'] = 0;
      $context['sandbox']['current'] = 0;
    }

    $entity_type = $this->entityTypeManager->getDefinition($this->getEntityTypeId());
    $id_key = $entity_type->getKey('id');

    $query = $this->database->select($entity_type->get('base_table'), 'base_table');
    $query->leftJoin('url_alias', 'ua', "CONCAT('" . $this->getSourcePrefix() . "' , base_table.$id_key) = ua.source");
    $query->addField('base_table', $id_key, 'id');

    switch ($action) {
      case 'create':
        $query->isNull('ua.source');
        break;
      case 'update':
        $query->isNotNull('ua.source');
        break;
      case 'all':
        // Nothing to do. We want all paths.
        break;
      default:
        // Unknown action. Abort!
        return;
    }
    $query->condition('base_table.' . $id_key, $context['sandbox']['current'], '>');
    $query->orderBy('base_table.' . $id_key);
    $query->addTag('pathauto_bulk_update');
    $query->addMetaData('entity', $this->getEntityTypeId());

    // Get the total amount of items to process.
    if (!isset($context['sandbox']['total'])) {
      $context['sandbox']['total'] = $query->countQuery()->execute()->fetchField();

      // If there are no entities to update, then stop immediately.
      if (!$context['sandbox']['total']) {
        $context['finished'] = 1;
        return;
      }
    }

    $query->range(0, 25);
    $ids = $query->execute()->fetchCol();

    $updates = $this->bulkUpdate($ids);
    $context['sandbox']['count'] += count($ids);
    $context['sandbox']['current'] = max($ids);
    $context['results']['updates'] += $updates;
    $context['message'] = $this->t('Updated alias for %label @id.', array('%label' => $entity_type->getLabel(), '@id' => end($ids)));

    if ($context['sandbox']['count'] != $context['sandbox']['total']) {
      $context['finished'] = $context['sandbox']['count'] / $context['sandbox']['total'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function batchDelete(&$context) {
    if (!isset($context['sandbox']['current'])) {
      $context['sandbox']['count'] = 0;
      $context['sandbox']['current'] = 0;
    }

    $entity_type = $this->entityTypeManager->getDefinition($this->getEntityTypeId());
    $id_key = $entity_type->getKey('id');

    $query = $this->database->select($entity_type->get('base_table'), 'base_table');
    $query->innerJoin('url_alias', 'ua', "CONCAT('" . $this->getSourcePrefix() . "' , base_table.$id_key) = ua.source");
    $query->addField('base_table', $id_key, 'id');
    $query->addField('ua', 'pid');
    $query->condition('ua.pid', $context['sandbox']['current'], '>');
    $query->orderBy('ua.pid');
    $query->addTag('pathauto_bulk_delete');
    $query->addMetaData('entity', $this->getEntityTypeId());

    // Get the total amount of items to process.
    if (!isset($context['sandbox']['total'])) {
      $context['sandbox']['total'] = $query->countQuery()->execute()->fetchField();

      // If there are no entities to delete, then stop immediately.
      if (!$context['sandbox']['total']) {
        $context['finished'] = 1;
        return;
      }
    }

    $query->range(0, 100);
    $pids_by_id = $query->execute()->fetchAllKeyed();

    $this->bulkDelete($pids_by_id);
    $context['sandbox']['count'] += count($pids_by_id);
    $context['sandbox']['current'] = max($pids_by_id);
    $context['results']['deletions'][] = $this->getLabel();

    if ($context['sandbox']['count'] != $context['sandbox']['total']) {
      $context['finished'] = $context['sandbox']['count'] / $context['sandbox']['total'];
    }
  }

  /**
   * Returns the entity type ID.
   *
   * @return string
   *   The entity type ID.
   */
  protected function getEntityTypeId() {
    return $this->getDerivativeId();
  }

  /**
   * Update the URL aliases for multiple entities.
   *
   * @param array $ids
   *   An array of entity IDs.
   * @param array $options
   *   An optional array of additional options.
   *
   * @return int
   *  The number of updated URL aliases.
   */
  protected function bulkUpdate(array $ids, array $options = array()) {
    $options += array('message' => FALSE);
    $updates = 0;

    $entities = $this->entityTypeManager->getStorage($this->getEntityTypeId())->loadMultiple($ids);
    foreach ($entities as $entity) {
      // Update aliases for the entity's default language and its translations.
      foreach ($entity->getTranslationLanguages() as $langcode => $language) {
        $translated_entity = $entity->getTranslation($langcode);
        $result = \Drupal::service('pathauto.generator')->updateEntityAlias($translated_entity, 'bulkupdate', $options);
        if ($result) {
          $updates++;
        }
      }
    }

    if (!empty($options['message'])) {
      drupal_set_message(\Drupal::translation()->formatPlural(count($ids), 'Updated 1 %label URL alias.', 'Updated @count %label URL aliases.'), array('%label' => $this->getLabel()));
    }

    return $updates;
  }

  /**
   * Deletes the URL aliases for multiple entities.
   *
   * @param int[] $pids_by_id
   *   A list of path IDs keyed by entity ID.
   */
  protected function bulkDelete(array $pids_by_id) {
    $collection = 'pathauto_state.' . $this->getEntityTypeId();
    $states = $this->keyValue->get($collection)->getMultiple(array_keys($pids_by_id));

    $pids = [];
    foreach ($pids_by_id as $id => $pid) {
      // Only delete aliases that were created by this module.
      if (isset($states[$id]) && $states[$id] == PathautoState::CREATE) {
        $pids[] = $pid;
      }
    }
    \Drupal::service('pathauto.alias_storage_helper')->deleteMultiple($pids);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = [];
    $dependencies['module'][] = $this->entityTypeManager->getDefinition($this->getEntityTypeId())->getProvider();
    return $dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function applies($object) {
    return $object instanceof FieldableEntityInterface && $object->getEntityTypeId() == $this->getEntityTypeId();
  }

  /**
   * {@inheritdoc}
   */
  public function getSourcePrefix() {
    if (empty($this->prefix)) {
      $entity_type = $this->entityTypeManager->getDefinition($this->getEntityTypeId());
      $path = $entity_type->getLinkTemplate('canonical');
      $this->prefix = substr($path, 0, strpos($path, '{'));
    }
    return $this->prefix;
  }

  /**
   * {@inheritdoc}
   */
  public function setContextValue($name, $value) {
    // Overridden to avoid merging existing cacheability metadata, which is not
    // relevant for alias type plugins.
    $this->context[$name] = new Context($this->getContextDefinition($name), $value);
    return $this;
  }


}
