<?php

namespace Drupal\pathauto;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * Provides helper methods for accessing alias storage.
 */
interface AliasStorageHelperInterface {

  /**
   * Fetch the maximum length of the {url_alias}.alias field from the schema.
   *
   * @return int
   *   An integer of the maximum URL alias length allowed by the database.
   */
  public function getAliasSchemaMaxLength();

  /**
   * Private function for Pathauto to create an alias.
   *
   * @param array $path
   *   An associative array containing the following keys:
   *   - source: The internal system path.
   *   - alias: The URL alias.
   *   - pid: (optional) Unique path alias identifier.
   *   - language: (optional) The language of the alias.
   * @param array|bool|null $existing_alias
   *   (optional) An associative array of the existing path alias.
   * @param string $op
   *   An optional string with the operation being performed.
   *
   * @return array|bool
   *   The saved path or NULL if the path was not saved.
   */
  public function save(array $path, $existing_alias = NULL, $op = NULL);

  /**
   * Fetches an existing URL alias given a path and optional language.
   *
   * @param string $source
   *   An internal Drupal path.
   * @param string $language
   *   An optional language code to look up the path in.
   *
   * @return bool|array
   *   FALSE if no alias was found or an associative array containing the
   *   following keys:
   *   - source (string): The internal system path with a starting slash.
   *   - alias (string): The URL alias with a starting slash.
   *   - pid (int): Unique path alias identifier.
   *   - langcode (string): The language code of the alias.
   */
  public function loadBySource($source, $language = LanguageInterface::LANGCODE_NOT_SPECIFIED);

  /**
   * Delete all aliases by source url.
   *
   * @param string $source
   *   An internal Drupal path.
   *
   * @return bool
   *   The URL alias source.
   */
  public function deleteBySourcePrefix($source);

  /**
   * Delete all aliases (truncate the url_alias table).
   */
  public function deleteAll();

  /**
   * Delete an entity URL alias and any of its sub-paths.
   *
   * This function also checks to see if the default entity URI is different
   * from the current entity URI and will delete any of the default aliases.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An entity object.
   * @param string $default_uri
   *   The optional default uri path for the entity.
   */
  public function deleteEntityPathAll(EntityInterface $entity, $default_uri = NULL);

  /**
   * Fetches an existing URL alias given a path prefix.
   *
   * @param string $source
   *   An internal Drupal path prefix.
   *
   * @return integer[]
   *   An array of PIDs.
   */
  public function loadBySourcePrefix($source);


  /**
   * Returns the count of url aliases for the source.
   *
   * @param $source
   *   An internal Drupal path prefix.
   *
   * @return int
   *   Number of url aliases for the source.
   */
  public function countBySourcePrefix($source);

  /**
   * Returns the total count of the url aliases.
   *
   * @return int
   *   Total number of aliases.
   */
  public function countAll();

}
