<?php

namespace Drupal\Driver;

/**
 * Driver interface.
 */
interface DriverInterface {

  /**
   * Returns a random generator.
   */
  public function getRandom();

  /**
   * Bootstraps operations, as needed.
   */
  public function bootstrap();

  /**
   * Determines if the driver has been bootstrapped.
   */
  public function isBootstrapped();

  /**
   * Creates a user.
   */
  public function userCreate(\stdClass $user);

  /**
   * Deletes a user.
   */
  public function userDelete(\stdClass $user);

  /**
   * Processes a batch of actions.
   */
  public function processBatch();

  /**
   * Adds a role for a user.
   *
   * @param object $user
   *   A user object.
   * @param string $role
   *   The role name to assign.
   */
  public function userAddRole(\stdClass $user, $role);

  /**
   * Retrieves watchdog entries.
   *
   * @param int $count
   *   Number of entries to retrieve.
   * @param string $type
   *   Filter by watchdog type.
   * @param string $severity
   *   Filter by watchdog severity level.
   *
   * @return string
   *   Watchdog output.
   */
  public function fetchWatchdog($count = 10, $type = NULL, $severity = NULL);

  /**
   * Clears Drupal caches.
   *
   * @param string $type
   *   Type of cache to clear defaults to all.
   */
  public function clearCache($type = NULL);

  /**
   * Clears static Drupal caches.
   */
  public function clearStaticCaches();

  /**
   * Creates a node.
   *
   * @param object $node
   *   Fully loaded node object.
   *
   * @return object
   *   The node object including the node ID in the case of new nodes.
   */
  public function createNode($node);

  /**
   * Deletes a node.
   *
   * @param object $node
   *   Fully loaded node object.
   */
  public function nodeDelete($node);

  /**
   * Runs cron.
   */
  public function runCron();

  /**
   * Creates a taxonomy term.
   *
   * @param object $term
   *   Term object.
   *
   * @return object
   *   The term object including the term ID in the case of new terms.
   */
  public function createTerm(\stdClass $term);

  /**
   * Deletes a taxonomy term.
   *
   * @param object $term
   *   Term object to delete.
   *
   * @return bool
   *   Status constant indicating deletion.
   */
  public function termDelete(\stdClass $term);

  /**
   * Creates a role.
   *
   * @param array $permissions
   *   An array of permissions to create the role with.
   *
   * @return string
   *   Role name of newly created role.
   */
  public function roleCreate(array $permissions);

  /**
   * Deletes a role.
   *
   * @param string $rid
   *   A role name to delete.
   */
  public function roleDelete($rid);

  /**
   * Check if the specified field is an actual Drupal field.
   *
   * @param string $entity_type
   *   The entity type to which the field should belong.
   * @param string $field_name
   *   The name of the field.
   *
   * @return bool
   *   TRUE if the field exists in the entity type, FALSE if not.
   */
  public function isField($entity_type, $field_name);

  /**
   * Returns a configuration item.
   *
   * @param string $name
   *   The name of the configuration object to retrieve.
   * @param string $key
   *   A string that maps to a key within the configuration data.
   *
   * @return mixed
   *   The data that was requested.
   */
  public function configGet($name, $key);

  /**
   * Sets a value in a configuration object.
   *
   * @param string $name
   *   The name of the configuration object.
   * @param string $key
   *   Identifier to store value in configuration.
   * @param mixed $value
   *   Value to associate with identifier.
   */
  public function configSet($name, $key, $value);

  /**
   * Creates an entity of a given type.
   *
   * @param string $entity_type
   *   The entity type ID.
   * @param object $entity
   *   The entity to create.
   *
   * @return object
   *   The created entity with `id` set.
   */
  public function createEntity($entity_type, \stdClass $entity);

  /**
   * Deletes an entity of a given type.
   *
   * @param string $entity_type
   *   The entity type ID.
   * @param object $entity
   *   The entity to delete.
   */
  public function entityDelete($entity_type, \stdClass $entity);

  /**
   * Enable the test mail collector.
   */
  public function startCollectingMail();

  /**
   * Restore normal operation of outgoing mail.
   */
  public function stopCollectingMail();

  /**
   * Get any mail collected by the test mail collector.
   *
   * @return \stdClass[]
   *   An array of collected emails, each formatted as a Drupal 8
   *   \Drupal\Core\Mail\MailInterface::mail $message array.
   */
  public function getMail();

  /**
   * Empty the test mail collector store of any collected mail.
   */
  public function clearMail();

  /**
   * Send a mail.
   *
   * @param string $body
   *   The body of the mail.
   * @param string $subject
   *   The subject of the mail.
   * @param string $to
   *   The recipient's email address, passing PHP email validation filter.
   * @param string $langcode
   *   The language used in subject and body.
   *
   * @return bool
   *   Whether the email was sent successfully.
   */
  public function sendMail($body, $subject, $to, $langcode);

}
