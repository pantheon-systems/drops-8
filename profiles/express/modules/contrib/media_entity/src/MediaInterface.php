<?php

namespace Drupal\media_entity;

use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;

/**
 * Provides an interface defining a media entity.
 */
interface MediaInterface extends ContentEntityInterface, EntityChangedInterface, RevisionLogInterface {

  /**
   * Returns the media creation timestamp.
   *
   * @return int
   *   Creation timestamp of the media.
   */
  public function getCreatedTime();

  /**
   * Sets the media creation timestamp.
   *
   * @param int $timestamp
   *   The media creation timestamp.
   *
   * @return \Drupal\media_entity\MediaInterface
   *   The called media entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Sets a flag to indicate the thumbnail will be retrieved via a queue.
   */
  public function setQueuedThumbnailDownload();

  /**
   * Returns the media publisher user entity.
   *
   * @return \Drupal\user\UserInterface
   *   The author user entity.
   */
  public function getPublisher();

  /**
   * Returns the media publisher user ID.
   *
   * @return int
   *   The author user ID.
   */
  public function getPublisherId();

  /**
   * Sets the media publisher user ID.
   *
   * @param int $uid
   *   The author user id.
   *
   * @return \Drupal\media_entity\MediaInterface
   *   The called media entity.
   */
  public function setPublisherId($uid);

  /**
   * Returns the media published status indicator.
   *
   * Unpublished media are only visible to their authors and to administrators.
   *
   * @return bool
   *   TRUE if the media is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a media.
   *
   * @param bool $published
   *   TRUE to set this media to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\media_entity\MediaInterface
   *   The called media entity.
   */
  public function setPublished($published);

  /**
   * Returns the media type.
   *
   * @return \Drupal\media_entity\MediaTypeInterface
   *   The media type.
   */
  public function getType();

  /**
   * Automatically determines the most appropriate thumbnail and sets
   * "thumbnail" field.
   */
  public function automaticallySetThumbnail();

}
