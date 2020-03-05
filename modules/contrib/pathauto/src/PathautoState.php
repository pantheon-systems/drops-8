<?php

namespace Drupal\pathauto;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\TypedData\TypedData;

/**
 * A property that stores in keyvalue whether an entity should receive an alias.
 */
class PathautoState extends TypedData {

  /**
   * An automatic alias should not be created.
   */
  const SKIP = 0;

  /**
   * An automatic alias should be created.
   */
  const CREATE = 1;

  /**
   * Pathauto state.
   *
   * @var int
   */
  protected $value;

  /**
   * @var \Drupal\Core\Field\FieldItemInterface
   */
  protected $parent;

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    if ($this->value === NULL) {
      // If no value has been set or loaded yet, try to load a value if this
      // entity has already been saved.
      $this->value = \Drupal::keyValue($this->getCollection())
        ->get(static::getPathautoStateKey($this->parent->getEntity()->id()));
      // If it was not yet saved or no value was found, then set the flag to
      // create the alias if there is a matching pattern.
      if ($this->value === NULL) {
        $entity = $this->parent->getEntity();
        $pattern = \Drupal::service('pathauto.generator')->getPatternByEntity($entity);
        $this->value = !empty($pattern) ? static::CREATE : static::SKIP;
      }
    }
    return $this->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($value, $notify = TRUE) {
    $this->value = $value;
    // Notify the parent of any changes.
    if ($notify && isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }

  /**
   * Returns TRUE if a value was set.
   */
  public function hasValue() {
    return $this->value !== NULL;
  }

  /**
   * Persists the state.
   */
  public function persist() {
    \Drupal::keyValue($this->getCollection())
      ->set(static::getPathautoStateKey($this->parent->getEntity()->id()), $this->getValue());
  }

  /**
   * Deletes the stored state.
   */
  public function purge() {
    \Drupal::keyValue($this->getCollection())
      ->delete(static::getPathautoStateKey($this->parent->getEntity()->id()));
  }

  /**
   * Returns the key value collection that should be used for the given entity.
   *
   * @return string
   */
  protected function getCollection() {
    return 'pathauto_state.' . $this->parent->getEntity()->getEntityTypeId();
  }

  /**
   * Deletes the URL aliases for multiple entities of the same type.
   *
   * @param string $entity_type_id
   *   The entity type ID of entities being deleted.
   * @param int[] $pids_by_id
   *   A list of path IDs keyed by entity ID.
   */
  public static function bulkDelete($entity_type_id, array $pids_by_id) {
    foreach ($pids_by_id as $id => $pid) {
      // Some key-values store entries have computed keys.
      $key = static::getPathautoStateKey($id);
      if ($key !== $id) {
        $pids_by_id[$key] = $pid;
        unset($pids_by_id[$id]);
      }
    }
    $states = \Drupal::keyValue("pathauto_state.$entity_type_id")
      ->getMultiple(array_keys($pids_by_id));

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
   * Gets the key-value store entry key for 'pathauto_state.*' collections.
   *
   * Normally we want to use the entity ID as key for 'pathauto_state.*'
   * collection entries. But some entity types may use string IDs. When such IDs
   * are exceeding 128 characters, which is the limit for the 'name' column in
   * the {key_value} table, the insertion of the ID in {key_value} will fail.
   * Thus we test if we can use the plain ID or we need to store a hashed
   * version of the entity ID. Also, it is not possible to rely on the UUID as
   * entity types might not have one or might use a non-standard format.
   *
   * The code is inspired by
   * \Drupal\Core\Cache\DatabaseBackend::normalizeCid().
   *
   * @param int|string $entity_id
   *   The entity id for which to compute the key.
   *
   * @return int|string
   *   The key used to store the value in the key-value store.
   *
   * @see \Drupal\Core\Cache\DatabaseBackend::normalizeCid()
   */
  public static function getPathautoStateKey($entity_id) {
    $entity_id_is_ascii = mb_check_encoding($entity_id, 'ASCII');
    if ($entity_id_is_ascii && strlen($entity_id) <= 128) {
      // The original entity ID, if it's an ASCII of 128 characters or less.
      return $entity_id;
    }
    return Crypt::hashBase64($entity_id);
  }

}
