<?php

namespace Drupal\pathauto;

use Drupal\Core\Entity\EntityInterface;

/**
 * Provides and interface for PathautoGenerator.
 */
interface PathautoGeneratorInterface {

  /**
   * "Do nothing. Leave the old alias intact."
   */
  const UPDATE_ACTION_NO_NEW = 0;

  /**
   * "Create a new alias. Leave the existing alias functioning."
   */
  const UPDATE_ACTION_LEAVE = 1;

  /**
   * "Create a new alias. Delete the old alias."
   */
  const UPDATE_ACTION_DELETE = 2;

  /**
   * Remove the punctuation from the alias.
   */
  const PUNCTUATION_REMOVE = 0;

  /**
   * Replace the punctuation with the separator in the alias.
   */
  const PUNCTUATION_REPLACE = 1;

  /**
   * Leave the punctuation as it is in the alias.
   */
  const PUNCTUATION_DO_NOTHING = 2;

  /**
   * Resets internal caches.
   */
  public function resetCaches();

  /**
   * Load an alias pattern entity by entity, bundle, and language.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An entity.
   *
   * @return \Drupal\pathauto\PathautoPatternInterface|null
   */
  public function getPatternByEntity(EntityInterface $entity);

  /**
   * Apply patterns to create an alias.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param string $op
   *   Operation being performed on the content being aliased
   *   ('insert', 'update', 'return', or 'bulkupdate').
   *
   * @return array|string
   *   The alias that was created.
   *
   * @see _pathauto_set_alias()
   */
  public function createEntityAlias(EntityInterface $entity, $op);

  /**
   * Creates or updates an alias for the given entity.
   *
   * @param EntityInterface $entity
   *   Entity for which to update the alias.
   * @param string $op
   *   The operation performed (insert, update)
   * @param array $options
   *   - force: will force updating the path
   *   - language: the language for which to create the alias
   *
   * @return array|null
   *   - An array with alias data in case the alias has been created or updated.
   *   - NULL if no operation performed.
   */
  public function updateEntityAlias(EntityInterface $entity, $op, array $options = []);

}
