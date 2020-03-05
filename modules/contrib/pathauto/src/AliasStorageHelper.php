<?php

namespace Drupal\pathauto;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Path\AliasRepositoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Provides helper methods for accessing alias storage.
 */
class AliasStorageHelper implements AliasStorageHelperInterface {

  use StringTranslationTrait;

  /**
   * Alias schema max length.
   *
   * @var int
   */
  protected $aliasSchemaMaxLength = 255;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The alias repository.
   *
   * @var \Drupal\Core\Path\AliasRepositoryInterface
   */
  protected $aliasRepository;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The messenger.
   *
   * @var \Drupal\pathauto\MessengerInterface
   */
  protected $messenger;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The config factory.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Path\AliasRepositoryInterface $alias_repository
   *   The alias repository.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manger.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AliasRepositoryInterface $alias_repository, Connection $database, MessengerInterface $messenger, TranslationInterface $string_translation, EntityTypeManagerInterface $entity_type_manager = NULL) {
    $this->configFactory = $config_factory;
    $this->aliasRepository = $alias_repository;
    $this->database = $database;
    $this->messenger = $messenger;
    $this->stringTranslation = $string_translation;
    $this->entityTypeManager = $entity_type_manager ?: \Drupal::service('entity_type.manager');
  }

  /**
   * {@inheritdoc}
   */
  public function getAliasSchemaMaxLength() {
    return $this->aliasSchemaMaxLength;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $path, $existing_alias = NULL, $op = NULL) {
    $config = $this->configFactory->get('pathauto.settings');

    // Set up all the variables needed to simplify the code below.
    $source = $path['source'];
    $alias = $path['alias'];
    $langcode = $path['language'];
    if ($existing_alias) {
      /** @var \Drupal\path_alias\PathAliasInterface $existing_alias */
      $existing_alias = $this->entityTypeManager->getStorage('path_alias')->load($existing_alias['pid']);
    }

    // Alert users if they are trying to create an alias that is the same as the
    // internal system path.
    if ($source == $alias) {
      $this->messenger->addMessage($this->t('Ignoring alias %alias because it is the same as the internal path.', ['%alias' => $alias]));
      return NULL;
    }

    // Update the existing alias if there is one and the configuration is set to
    // replace it.
    if ($existing_alias && $config->get('update_action') == PathautoGeneratorInterface::UPDATE_ACTION_DELETE) {
      // Skip replacing the current alias with an identical alias.
      if ($existing_alias->getAlias() == $alias) {
        return NULL;
      }

      $old_alias = $existing_alias->getAlias();
      $existing_alias->setAlias($alias)->save();

      $this->messenger->addMessage($this->t('Created new alias %alias for %source, replacing %old_alias.', [
        '%alias' => $alias,
        '%source' => $source,
        '%old_alias' => $old_alias,
      ]));

      $return = $existing_alias;
    }
    else {
      // Otherwise, create a new alias.
      $path_alias = $this->entityTypeManager->getStorage('path_alias')->create([
        'path' => $source,
        'alias' => $alias,
        'langcode' => $langcode,
      ]);
      $path_alias->save();

      $this->messenger->addMessage($this->t('Created new alias %alias for %source.', [
        '%alias' => $path_alias->getAlias(),
        '%source' => $path_alias->getPath(),
      ]));

      $return = $path_alias;
    }

    return [
      'source' => $return->getPath(),
      'alias' => $return->getAlias(),
      'pid' => $return->id(),
      'langcode' => $return->language()->getId(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function loadBySource($source, $language = LanguageInterface::LANGCODE_NOT_SPECIFIED) {
    $alias = $this->aliasRepository->lookupBySystemPath($source, $language);
    if ($alias) {
      return [
        'pid' => $alias['id'],
        'alias' => $alias['alias'],
        'source' => $alias['path'],
        'langcode' => $alias['langcode'],
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteBySourcePrefix($source) {
    $pids = $this->loadBySourcePrefix($source);
    if ($pids) {
      $this->deleteMultiple($pids);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll() {
    /** @var \Drupal\Core\Entity\Sql\TableMappingInterface $table_mapping */
    $table_mapping = $this->entityTypeManager->getStorage('path_alias')->getTableMapping();
    foreach ($table_mapping->getTableNames() as $table_name) {
      $this->database->truncate($table_name)->execute();
    }
    $this->entityTypeManager->getStorage('path_alias')->resetCache();
  }

  /**
   * {@inheritdoc}
   */
  public function deleteEntityPathAll(EntityInterface $entity, $default_uri = NULL) {
    $this->deleteBySourcePrefix('/' . $entity->toUrl('canonical')->getInternalPath());
    if (isset($default_uri) && $entity->toUrl('canonical')->toString() != $default_uri) {
      $this->deleteBySourcePrefix($default_uri);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function loadBySourcePrefix($source) {
    return $this->entityTypeManager->getStorage('path_alias')->getQuery('OR')
      ->condition('path', $source, '=')
      ->condition('path', rtrim($source, '/') . '/', 'STARTS_WITH')
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function countBySourcePrefix($source) {
    return $this->entityTypeManager->getStorage('path_alias')->getQuery('OR')
      ->condition('path', $source, '=')
      ->condition('path', rtrim($source, '/') . '/', 'STARTS_WITH')
      ->count()
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function countAll() {
    return $this->entityTypeManager->getStorage('path_alias')->getQuery()
      ->count()
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function deleteMultiple($pids) {
    $this->entityTypeManager->getStorage('path_alias')->delete($this->entityTypeManager->getStorage('path_alias')->loadMultiple($pids));
  }

}
