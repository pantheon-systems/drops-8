<?php

namespace Drupal\webform;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Language\LanguageManagerInterface;
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
   */
  public function __construct(EntityTypeInterface $entity_type, ConfigFactoryInterface $config_factory, UuidInterface $uuid_service, LanguageManagerInterface $language_manager, Connection $database) {
    parent::__construct($entity_type, $config_factory, $uuid_service, $language_manager);
    $this->database = $database;
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
      $container->get('database')
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
    if ($id = $entity->id()) {
      $this->entities[$id] = $entity;
    }
    return $entity;
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
   *
   * Config entities are not cached and there is no easy way to enable static
   * caching. See: Issue #1885830: Enable static caching for config entities.
   *
   * Overriding just EntityStorageBase::load is much simpler
   * than completely re-writting EntityStorageBase::loadMultiple. It is also
   * worth noting that EntityStorageBase::resetCache() does purge all cached
   * webform config entities.
   *
   * Webforms need to be cached when they are being loading via
   * a webform submission, which requires a webform's elements and meta data to be
   * initialized via Webform::initElements().
   *
   * @see https://www.drupal.org/node/1885830
   * @see \Drupal\Core\Entity\EntityStorageBase::resetCache()
   * @see \Drupal\webform\Entity\Webform::initElements()
   */
  public function load($id) {
    if (isset($this->entities[$id])) {
      return $this->entities[$id];
    }

    $this->entities[$id] = parent::load($id);
    return $this->entities[$id];
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
    $this->database->delete('webform_submission_log')
      ->condition('webform_id', $webform_ids, 'IN')
      ->execute();

    // Delete all webform records used to track next serial.
    $this->database->delete('webform')
      ->condition('webform_id', $webform_ids, 'IN')
      ->execute();
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
    $webforms = $this->loadMultiple();
    @uasort($webforms, [$this->entityType->getClass(), 'sort']);

    $uncategorized_options = [];
    $categorized_options = [];
    foreach ($webforms as $id => $webform) {
      if ($template !== NULL && $webform->get('template') != $template) {
        continue;
      }
      if ($category = $webform->get('category')) {
        $categorized_options[$category][$id] = $webform->label();
      }
      else {
        $uncategorized_options[$id] = $webform->label();
      }
    }
    return $uncategorized_options + $categorized_options;
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
    // Use a transaction with SELECT ... FOR UPDATE to lock the row between
    // the SELECT and the UPDATE, ensuring that multiple Webform submissions
    // at the same time do not have duplicate numbers. FOR UPDATE must be inside
    // a transaction. The return value of db_transaction() must be assigned or the
    // transaction will commit immediately. The transaction will commit when $txn
    // goes out-of-scope.
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

}
