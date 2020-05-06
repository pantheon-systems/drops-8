<?php

namespace Drupal\webform;

use Drupal\Core\Session\AccountInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\UserInterface;

/**
 * Provides an interface defining a webform submission entity.
 */
interface WebformSubmissionInterface extends ContentEntityInterface, EntityOwnerInterface {

  /**
   * Return status for new submission.
   */
  const STATE_UNSAVED = 'unsaved';

  /**
   * Return status for submission in draft created.
   */
  const STATE_DRAFT = 'draft';

  /**
   * Return status for submission in draft created.
   */
  const STATE_DRAFT_CREATED = 'draft_created';

  /**
   * Return status for submission in draft updated.
   */
  const STATE_DRAFT_UPDATED = 'draft_updated';

  /**
   * Return status for submission that has been completed.
   */
  const STATE_COMPLETED = 'completed';

  /**
   * Return status for submission that has been locked.
   */
  const STATE_LOCKED = 'locked';

  /**
   * Return status for submission that has been updated.
   */
  const STATE_UPDATED = 'updated';

  /**
   * Return status for submission that has been deleted.
   */
  const STATE_DELETED = 'deleted';

  /**
   * Return status for submission that has been converted from anonymous to authenticated.
   */
  const STATE_CONVERTED = 'converted';

  /**
   * Gets the serial number.
   *
   * @return int
   *   The serial number.
   */
  public function serial();

  /**
   * Returns the time that the submission was created.
   *
   * @return int
   *   The timestamp of when the submission was created.
   */
  public function getCreatedTime();

  /**
   * Sets the creation date of the submission.
   *
   * @param int $created
   *   The timestamp of when the submission was created.
   *
   * @return $this
   */
  public function setCreatedTime($created);

  /**
   * Gets the timestamp of the last submission change.
   *
   * @return int
   *   The timestamp of the last submission save operation.
   */
  public function getChangedTime();

  /**
   * Sets the timestamp of the last submission change.
   *
   * @param int $timestamp
   *   The timestamp of the last submission save operation.
   *
   * @return $this
   */
  public function setChangedTime($timestamp);

  /**
   * Gets the timestamp of the submission completion.
   *
   * @return int
   *   The timestamp of the submission completion.
   */
  public function getCompletedTime();

  /**
   * Sets the timestamp of the submission completion.
   *
   * @param int $timestamp
   *   The timestamp of the submission completion.
   *
   * @return $this
   */
  public function setCompletedTime($timestamp);

  /**
   * Get the submission's notes.
   *
   * @return string
   *   The submission's notes.
   */
  public function getNotes();

  /**
   * Sets the submission's notes.
   *
   * @param string $notes
   *   The submission's notes.
   *
   * @return $this
   */
  public function setNotes($notes);

  /**
   * Get the submission's sticky flag.
   *
   * @return string
   *   The submission's stick flag.
   */
  public function getSticky();

  /**
   * Sets the submission's sticky flag.
   *
   * @param bool $sticky
   *   The submission's stick flag.
   *
   * @return $this
   */
  public function setSticky($sticky);

  /**
   * Get the submission's locked status.
   *
   * @return string
   *   The submission's lock status.
   */
  public function isLocked();

  /**
   * Sets the submission's locked flag.
   *
   * @param bool $locked
   *   The submission's locked flag.
   *
   * @return $this
   */
  public function setLocked($locked);

  /**
   * Gets the remote IP address of the submission.
   *
   * @return string
   *   The remote IP address of the submission
   */
  public function getRemoteAddr();

  /**
   * Sets remote IP address of the submission.
   *
   * @param string $ip_address
   *   The remote IP address of the submission.
   *
   * @return $this
   */
  public function setRemoteAddr($ip_address);

  /**
   * Gets the submission's current page.
   *
   * @return string
   *   The submission's current page.
   */
  public function getCurrentPage();

  /**
   * Sets the submission's current page.
   *
   * @param string $current_page
   *   The submission's current page.
   *
   * @return $this
   */
  public function setCurrentPage($current_page);

  /**
   * Get the submission's current page title.
   *
   * @return string
   *   The submission's current page title.
   */
  public function getCurrentPageTitle();

  /**
   * Is the current submission in draft.
   *
   * @return bool
   *   TRUE if the current submission is a draft.
   */
  public function isDraft();

  /**
   * Is the current submission being converted from anonymous to authenticated.
   *
   * @return bool
   *   TRUE if the current submission being converted from anonymous to
   *   authenticated.
   */
  public function isConverting();

  /**
   * Is the current submission completed.
   *
   * @return bool
   *   TRUE if the current submission has been completed.
   */
  public function isCompleted();

  /**
   * Returns the submission sticky status.
   *
   * @return bool
   *   TRUE if the submission is sticky.
   */
  public function isSticky();

  /**
   * Test whether the provided account is owner of this webform submission.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Account whose ownership to test.
   *
   * @return bool
   *   Whether the provided account is owner of this webform submission.
   */
  public function isOwner(AccountInterface $account);

  /**
   * Checks submission notes.
   *
   * @return bool
   *   TRUE if the submission has notes.
   */
  public function hasNotes();

  /**
   * Track the state of a submission.
   *
   * @return string
   *   Either STATE_UNSAVED, STATE_DRAFT_CREATED, STATE_DRAFT_UPDATED,
   *   STATE_COMPLETED, STATE_UPDATED, STATE_LOCKED, or STATE_CONVERTED
   *   depending on the last save operation performed.
   */
  public function getState();

  /**
   * Get a webform submission element's data.
   *
   * @param string $key
   *   An webform submission element's key.
   *
   * @return mixed
   *   An webform submission element's data/value.
   */
  public function getElementData($key);

  /**
   * Set a webform submission element's data.
   *
   * @param string $key
   *   An webform submission element's key.
   * @param mixed $value
   *   A value.
   *
   * @return $this
   */
  public function setElementData($key, $value);

  /**
   * Gets the webform submission's raw data.
   *
   * @return array
   *   The webform submission raw data.
   */
  public function getRawData();

  /**
   * Gets the webform submission's data with computed valued.
   *
   * @return array
   *   The webform submission data with computed valued.
   */
  public function getData();

  /**
   * Set the webform submission's data.
   *
   * @param array $data
   *   The webform submission data.
   *
   * @return $this
   */
  public function setData(array $data);

  /**
   * Gets the webform submission's original data before any changes.
   *
   * @return array
   *   The webform submission original data.
   */
  public function getOriginalData();

  /**
   * Set the webform submission's original data.
   *
   * @param array $data
   *   The webform submission data.
   *
   * @return $this
   */
  public function setOriginalData(array $data);

  /**
   * Get a webform submission element's original data.
   *
   * @param string $key
   *   An webform submission element's key.
   *
   * @return mixed
   *   An webform submission element's original data/value.
   */
  public function getElementOriginalData($key);

  /**
   * Gets the webform submission's token.
   *
   * @return array
   *   The webform submission data.
   */
  public function getToken();

  /**
   * Gets the webform submission's webform entity.
   *
   * @return \Drupal\webform\WebformInterface
   *   The webform entity.
   */
  public function getWebform();

  /**
   * Gets the webform submission's source entity.
   *
   * @param bool $translate
   *   (optional) If TRUE the source entity will be translated.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity that this webform submission was created from.
   */
  public function getSourceEntity($translate = FALSE);

  /**
   * Gets the webform submission's source URL.
   *
   * @return \Drupal\Core\Url|false
   *   The source URL.
   */
  public function getSourceUrl();

  /**
   * Gets the webform submission's secure tokenized URL.
   *
   * @param string $operation
   *   Token URL's operation. Defaults to update.
   *
   * @return \Drupal\Core\Url
   *   The webform submission's secure tokenized URL.
   */
  public function getTokenUrl($operation = 'update');

  /**
   * Invoke all webform handlers method.
   *
   * @param string $method
   *   The webform handler method to be invoked.
   *
   * @return \Drupal\Core\Access\AccessResult|null
   *   If 'access' method is invoked an AccessResult is returned.
   */
  public function invokeWebformHandlers($method);

  /**
   * Invoke a webform element elements method.
   *
   * @param string $method
   *   The webform element method to be invoked.
   */
  public function invokeWebformElements($method);

  /**
   * Convert anonymous submission to authenticated.
   *
   * @param \Drupal\user\UserInterface $account
   *   An authenticated user account.
   */
  public function convert(UserInterface $account);

  /**
   * Resave a webform submission without trigger any hooks or handlers.
   */
  public function resave();

  /**
   * Gets an array of all property values.
   *
   * @param bool $custom
   *   If TRUE, return customized array that contains simplified properties
   *   and webform submission (element) data.
   * @param bool $check_access
   *   If $custom and $check_access is TRUE, view access is checked
   *   for webform submission (element) data.
   *
   * @return mixed[]
   *   An array of property values, keyed by property name.
   */
  public function toArray($custom = FALSE, $check_access = FALSE);

}
