<?php

namespace Drupal\redirect_404;

/**
 * Interface for redirect 404 services.
 */
interface RedirectNotFoundStorageInterface {

  /**
   * Merges a 404 request log in the database.
   *
   * @param string $path
   *   The path of the current request.
   * @param string $langcode
   *   The ID of the language code.
   */
  public function logRequest($path, $langcode);

  /**
   * Marks a 404 request log as resolved.
   *
   * @param string $path
   *   The path of the current request.
   * @param string $langcode
   *   The ID of the language code.
   */
  public function resolveLogRequest($path, $langcode);

  /**
   * Returns the 404 request data.
   *
   * @param array $header
   *   An array containing arrays of the redirect_404 fields data.
   * @param string $search
   *   The search text. It is possible to have multiple '*' as a wildcard.
   *
   * @return array
   *   A list of objects with the properties:
   *   - path
   *   - count
   *   - timestamp
   *   - langcode
   *   - resolved
   */
  public function listRequests(array $header = [], $search = NULL);

  /**
   * Cleans the irrelevant 404 request logs.
   */
  public function purgeOldRequests();

}
