<?php

namespace Drupal\media_entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\entity\Entity\RevisionableEntityBundleInterface;

/**
 * Provides an interface defining a media bundle entity.
 */
interface MediaBundleInterface extends ConfigEntityInterface, RevisionableEntityBundleInterface {

  /**
   * Returns the label.
   *
   * @param \Drupal\media_entity\MediaInterface $media
   *   The Media entity.
   *
   * @return string|bool
   *   Returns the label of the bundle that entity belongs to.
   */
  public static function getLabel(MediaInterface $media);

  /**
   * Checks if the bundle exists.
   *
   * @param int $id
   *   The Media bundle ID.
   *
   * @return bool
   *   TRUE if the bundle with the given ID exists, FALSE otherwise.
   */
  public static function exists($id);

  /**
   * Returns whether thumbnail downloads are queued.
   *
   * @return bool
   *   Returns download now or later.
   */
  public function getQueueThumbnailDownloads();

  /**
   * Sets a flag to indicate that thumbnails should be downloaded via a queue.
   *
   * @param bool $queue_thumbnail_downloads
   *   The queue downloads flag.
   */
  public function setQueueThumbnailDownloads($queue_thumbnail_downloads);

  /**
   * Returns the Media bundle description.
   *
   * @return string
   *   Returns the Media bundle description.
   */
  public function getDescription();

  /**
   * Returns the media type plugin.
   *
   * @return \Drupal\media_entity\MediaTypeInterface
   *   The type.
   */
  public function getType();

  /**
   * Returns the media type configuration.
   *
   * @return array
   *   The type configuration.
   */
  public function getTypeConfiguration();

  /**
   * Sets the media type configuration.
   *
   * @param array $configuration
   *   The type configuration.
   */
  public function setTypeConfiguration($configuration);

  /**
   * Returns the media type status.
   *
   * @return bool
   *   The status.
   */
  public function getStatus();

  /**
   * Sets whether a new revision should be created by default.
   *
   * @param bool $new_revision
   *   TRUE if a new revision should be created by default.
   */
  public function setNewRevision($new_revision);

}
