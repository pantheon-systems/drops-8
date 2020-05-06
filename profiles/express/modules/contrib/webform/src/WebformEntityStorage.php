<?php

namespace Drupal\webform;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Storage controller class for "webform" configuration entities.
 */
class WebformEntityStorage extends ConfigEntityStorage implements WebformEntityStorageInterface {

  /**
   * Active database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The helpers to operate on files.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Associative array container total results for all webforms.
   *
   * @var array
   */
  protected $totals;

  /**
   * Constructs a WebformEntityStorage object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid_service
   *   The UUID service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection to be used.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Cache\MemoryCache\MemoryCacheInterface $memory_cache
   *   The memory cache.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The helpers to operate on files.
   *
   * @todo Webform 8.x-6.x: Move $memory_cache right after $language_manager.
   */
  public function __construct(EntityTypeInterface $entity_type, ConfigFactoryInterface $config_factory, UuidInterface $uuid_service, LanguageManagerInterface $language_manager, Connection $database, EntityTypeManagerInterface $entity_type_manager, MemoryCacheInterface $memory_cache = NULL, FileSystemInterface $file_system) {
    parent::__construct($entity_type, $config_factory, $uuid_service, $language_manager, $memory_cache);
    $this->database = $database;
    $this->entityTypeManager = $entity_type_manager;
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('config.factory'),
      $container->get('uuid'),
      $container->get('language_manager'),
      $container->get('database'),
      $container->get('entity_type.manager'),
      $container->get('entity.memory_cache'),
      $container->get('file_system')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function doCreate(array $values) {
    $entity = parent::doCreate($values);
    // Cache new created webform entity so that it can be loaded using just the
    // webform's id.
    // @see '_webform_ui_temp_form'
    // @see \Drupal\webform_ui\Form\WebformUiElementTestForm
    // @see \Drupal\webform_ui\Form\WebformUiElementTypeFormBase
    $id = $entity->id();
    if ($id && $id === '_webform_ui_temp_form') {
      $this->setStaticCache([$id => $entity]);
    }
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  protected function doPostSave(EntityInterface $entity, $update) {
    if ($update && $entity->getAccessRules() != $entity->original->getAccessRules()) {
      // Invalidate webform_submission listing cache tags because due to the
      // change in access rules of this webform, some listings might have
      // changed for users.
      $cache_tags = $this->entityTypeManager->getDefinition('webform_submission')->getListCacheTags();
      Cache::invalidateTags($cache_tags);
    }

    parent::doPostSave($entity, $update);
  }

  /**
   * {@inheritdoc}
   */
  public function save(EntityInterface $entity) {
    $return = parent::save($entity);
    if ($return === SAVED_NEW) {
      // Insert webform database record used for transaction tracking.
      $this->database->insert('webform')
        ->fields([
          'webform_id' => $entity->id(),
          'next_serial' => 1,
        ])
        ->execute();
    }
    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $entities) {
    parent::delete($entities);
    if (!$entities) {
      // If no entities were passed, do nothing.
      return;
    }

    // Delete all webform submission log entries.
    $webform_ids = [];
    foreach ($entities as $entity) {
      $webform_ids[] = $entity->id();
    }

    // Delete all webform records used to track next serial.
    $this->database->delete('webform')
      ->condition('webform_id', $webform_ids, 'IN')
      ->execute();

    // Remove the webform specific file directory for all stream wrappers.
    // @see \Drupal\webform\Plugin\WebformElement\WebformManagedFileBase
    // @see \Drupal\webform\Plugin\WebformElement\WebformSignature
    foreach ($entities as $entity) {
      $stream_wrappers = array_keys(\Drupal::service('stream_wrapper_manager')
        ->getNames(StreamWrapperInterface::WRITE_VISIBLE));
      foreach ($stream_wrappers as $stream_wrapper) {
        $file_directory = $stream_wrapper . '://webform/' . $entity->id();

        // Clear all signature files.
        // @see \Drupal\webform\Plugin\WebformElement\WebformSignature::getImageUrl
        $files = file_scan_directory($file_directory, '/^signature-.*/');
        foreach (array_keys($files) as $uri) {
          $this->fileSystem->delete($uri);
        }

        // Clear empty webform directory.
        if (file_exists($file_directory) && empty(file_scan_directory($file_directory, '/.*/'))) {
          $this->fileSystem->deleteRecursive($file_directory);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCategories($template = NULL) {
    $webforms = $this->loadMultiple();
    $categories = [];
    foreach ($webforms as $webform) {
      if ($template !== NULL && $webform->get('template') != $template) {
        continue;
      }
      if ($category = $webform->get('category')) {
        $categories[$category] = $category;
      }
    }
    ksort($categories);
    return $categories;
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions($template = NULL) {
    /** @var \Drupal\webform\WebformInterface[] $webforms */
    $webforms = $this->loadMultiple();
    @uasort($webforms, [$this->entityType->getClass(), 'sort']);

    $uncategorized_options = [];
    $categorized_options = [];
    foreach ($webforms as $id => $webform) {
      // Skip templates.
      if ($template !== NULL && $webform->get('template') != $template) {
        continue;
      }
      // Skip archived.
      if ($webform->isArchived()) {
        continue;
      }

      if ($category = $webform->get('category')) {
        $categorized_options[$category][$id] = $webform->label();
      }
      else {
        $uncategorized_options[$id] = $webform->label();
      }
    }

    // Merge uncategorized options with categorized options.
    $options = $uncategorized_options;
    foreach ($categorized_options as $optgroup => $optgroup_options) {
      // If webform id and optgroup conflict move the webform into the optgroup.
      if (isset($options[$optgroup])) {
        $options[$optgroup] = [$optgroup => $options[$optgroup]]
          + $optgroup_options;
        asort($options[$optgroup]);
      }
      else {
        $options[$optgroup] = $optgroup_options;
      }
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getNextSerial(WebformInterface $webform) {
    return $this->database->select('webform', 'w')
      ->fields('w', ['next_serial'])
      ->condition('webform_id', $webform->id())
      ->execute()
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function setNextSerial(WebformInterface $webform, $next_serial = 1) {
    $this->database->update('webform')
      ->fields(['next_serial' => $next_serial])
      ->condition('webform_id', $webform->id())
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getSerial(WebformInterface $webform) {
    // Use a transaction with SELECT … FOR UPDATE to lock the row between
    // the SELECT and the UPDATE, ensuring that multiple Webform submissions
    // at the same time do not have duplicate numbers. FOR UPDATE must be inside
    // a transaction. The return value of db_transaction() must be assigned or
    // the transaction will commit immediately.
    //
    // The transaction will commit when $transaction goes out-of-scope.
    //
    // @see \Drupal\Core\Database\Transaction
    $transaction = $this->database->startTransaction();

    // Get the next_serial value.
    $next_serial = $this->database->select('webform', 'w')
      // Only add FOR UPDATE when incrementing.
      ->forUpdate()
      ->fields('w', ['next_serial'])
      ->condition('webform_id', $webform->id())
      ->execute()
      ->fetchField();

    // $next_serial must be greater than any existing serial number.
    $next_serial = max($next_serial, $this->getMaxSerial($webform));

    // Increment the next_value.
    $this->database->update('webform')
      ->fields(['next_serial' => $next_serial + 1])
      ->condition('webform_id', $webform->id())
      ->execute();

    return $next_serial;
  }

  /**
   * {@inheritdoc}
   */
  public function getMaxSerial(WebformInterface $webform) {
    $query = $this->database->select('webform_submission');
    $query->condition('webform_id', $webform->id());
    $query->addExpression('MAX(serial)');
    return $query->execute()->fetchField() + 1;
  }

  /**
   * Get total number of results for specified webform or all webforms.
   *
   * @param string|null $webform_id
   *   (optional) A webform id.
   *
   * @return array|int
   *   If no webform id is passed, an associative array keyed by webform id
   *   contains total results for all webforms, otherwise the total number of
   *   results for specified webform
   */
  public function getTotalNumberOfResults($webform_id = NULL) {
    if (!isset($this->totals)) {
      $query = $this->database->select('webform_submission', 'ws');
      $query->fields('ws', ['webform_id']);
      $query->addExpression('COUNT(sid)', 'results');
      $query->groupBy('webform_id');
      $this->totals = array_map('intval', $query->execute()->fetchAllKeyed());
    }

    if ($webform_id) {
      return (isset($this->totals[$webform_id])) ? $this->totals[$webform_id] : 0;
    }
    else {
      return $this->totals;
    }
  }

}
