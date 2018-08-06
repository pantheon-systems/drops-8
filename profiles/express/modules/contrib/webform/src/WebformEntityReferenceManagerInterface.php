<?php

namespace Drupal\webform;

use Drupal\Core\Entity\EntityInterface;

/**
 * Defines an interface for webform entity manager classes.
 */
interface WebformEntityReferenceManagerInterface {

  /****************************************************************************/
  // User data methods.
  /****************************************************************************/

  /**
   * Is the current request a webform croute where the user can specific a webform.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A fieldable content entity.
   *
   * @return bool
   *   TRUE if the current request a webform croute where the user can
   *   specific a webform.
   */
  public function isUserWebformRoute(EntityInterface $entity);

  /**
   * Set user specified webform for a source entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A fieldable content entity.
   * @param string $webform_id
   *   A webform id.
   */
  public function setUserWebformId(EntityInterface $entity, $webform_id);

  /**
   * Get user specified webform for a source entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A fieldable content entity.
   *
   * @return string|null
   *   A webform id or NULL.
   */
  public function getUserWebformId(EntityInterface $entity);

  /**
   * Delete user specified webform for a source entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A fieldable content entity.
   */
  public function deleteUserWebformId(EntityInterface $entity);

  /****************************************************************************/
  // Field methods.
  /****************************************************************************/

  /**
   * Determine if the entity has a webform entity reference field.
   *
   * @param \Drupal\Core\Entity\EntityInterface|NULL $entity
   *   A fieldable content entity.
   *
   * @return bool
   *   TRUE if the entity has a webform entity reference field.
   */
  public function hasField(EntityInterface $entity = NULL);

  /**
   * Get an entity's webform field names.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A fieldable content entity.
   *
   * @return array
   *   An array of webform fields associate with an entity.
   */
  public function getFieldNames(EntityInterface $entity = NULL);

  /**
   * Get an entity's webform field name.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A fieldable content entity.
   *
   * @return string
   *   The name of the webform field or an empty string.
   */
  public function getFieldName(EntityInterface $entity = NULL);

  /**
   * Get an entity's target webform.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A fieldable content entity.
   *
   * @return \Drupal\webform\WebformInterface|null
   *   The entity's target webform or NULL.
   */
  public function getWebform(EntityInterface $entity = NULL);

  /**
   * Get an entity's target webform.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   A fieldable content entity.
   *
   * @return array
   *   An array of webforms.
   */
  public function getWebforms(EntityInterface $entity = NULL);

  /****************************************************************************/
  // Table methods.
  /****************************************************************************/

  /**
   * Get the table names for all webform field instances.
   *
   * @return array
   *   An associative array of webform field table names and webform field names.
   */
  public function getTableNames();

}
