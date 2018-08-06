<?php

namespace Drupal\xmlsitemap;

use Drupal\Core\Entity\EntityInterface;

/**
 * Provides an interface defining a XmlSitemapLinkStorage service.
 */
interface XmlSitemapLinkStorageInterface {

  /**
   * Create a sitemap link from an entity.
   *
   * The link will be saved as $entity->xmlsitemap.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity whose sitemap link will be created.
   */
  public function create(EntityInterface $entity);

  /**
   * Saves or updates a sitemap link.
   *
   * @param array $link
   *   An array with a sitemap link.
   */
  public function save(array $link);

  /**
   * Check if there is sitemap link is changed from the existing data.
   *
   * @param array $link
   *   An array of the sitemap link.
   * @param array $original_link
   *   An optional array of the existing data. This should only contain the
   *   fields necessary for comparison. If not provided the existing data will
   *   be loaded from the database.
   * @param bool $flag
   *   An optional boolean that if TRUE, will set the regenerate needed flag if
   *   there is a match. Defaults to FALSE.
   * @return
   *   TRUE if the link is changed, or FALSE otherwise.
   */
  public function checkChangedLink(array $link, $original_link = NULL, $flag = FALSE);

  /**
   * Check if there is a visible sitemap link given a certain set of conditions.
   *
   * @param array $conditions
   *   An array of values to match keyed by field.
   * @param array $updates
   *   Updates to be made.
   * @param bool $flag
   *   An optional boolean that if TRUE, will set the regenerate needed flag if
   *   there is a match. Defaults to FALSE.
   *
   * @return
   *   TRUE if there is a visible link, or FALSE otherwise.
   */
  public function checkChangedLinks(array $conditions = array(), array $updates = array(), $flag = FALSE);

  /**
   * Delete a specific sitemap link from the database.
   *
   * If a visible sitemap link was deleted, this will automatically set the
   * regenerate needed flag.
   *
   * @param string $entity_type
   *   A string with the entity type.
   * @param $entity_id
   *   Entity ID to be deleted.
   *
   * @return
   *   The number of links that were deleted.
   */
  public function delete($entity_type, $entity_id);

  /**
   * Delete multiple sitemap links from the database.
   * If visible sitemap links were deleted, this will automatically set the
   * regenerate needed flag.
   *
   * @param array $conditions
   *   An array of conditions on the {xmlsitemap} table in the form
   *   'field' => $value.
   *
   * @return
   *   The number of links that were deleted.
   */
  public function deleteMultiple(array $conditions);

  /**
   * Perform a mass update of sitemap data.
   * If visible links are updated, this will automatically set the regenerate
   * needed flag to TRUE.
   *
   * @param array $updates
   *   An array of values to update fields to, keyed by field name.
   * @param array $conditions
   *   An array of values to match keyed by field.
   *
   * @return
   *   The number of links that were updated.
   */
  public function updateMultiple($updates = array(), $conditions = array(), $check_flag = TRUE);

  /**
   * Load a specific sitemap link from the database.
   *
   * @param string $entity_type
   *   A string with the entity type id.
   * @param $entity_id
   *   Entity ID.
   *
   * @return
   *   A sitemap link (array) or FALSE if the conditions were not found.
   */
  public function load($entity_type, $entity_id);

  /**
   * Load sitemap links from the database.
   *
   * @param array $conditions
   *   An array of conditions on the {xmlsitemap} table in the form
   *   'field' => $value.
   *
   * @return
   *   An array of sitemap link arrays.
   */
  public function loadMultiple(array $conditions = array());
}
