<?php

namespace Drupal\crop;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining the crop entity.
 */
interface CropInterface extends ContentEntityInterface {

  /**
   * Gets position of crop's center.
   *
   * @return array
   *   Array with two keys (x, y) and center coordinates as values.
   */
  public function position();

  /**
   * Sets position of crop's center.
   *
   * @param int $x
   *   X coordinate of the crop's center.
   * @param int $y
   *   Y coordinate of the crop's center.
   *
   * @return \Drupal\crop\CropInterface
   *   Crop object this is being called on.
   */
  public function setPosition($x, $y);

  /**
   * Gets crop's size.
   *
   * @return array
   *   Array with two keys (width, height) each side dimensions as values.
   */
  public function size();

  /**
   * Sets crop's size.
   *
   * @param int $width
   *   Crop's width.
   * @param int $height
   *   Crop's height.
   *
   * @return \Drupal\crop\CropInterface
   *   Crop object this is being called on.
   */
  public function setSize($width, $height);

  /**
   * Gets crop anchor (top-left corner of crop area).
   *
   * @return array
   *   Array with two keys (x, y) and anchor coordinates as values.
   */
  public function anchor();

  /**
   * Gets entity provider for the crop.
   *
   * @return \Drupal\crop\EntityProviderInterface
   *   Entity provider.
   *
   * @throws \Drupal\crop\EntityProviderNotFoundException
   *   Thrown if entity provider not found.
   */
  public function provider();

  /**
   * Checks whether crop exists for an image.
   *
   * @param string $uri
   *   URI of image to check for.
   * @param string $type
   *   (Optional) Type of crop. Function will check across all available types
   *   if omitted.
   *
   * @return bool
   *   Boolean TRUE if crop exists and FALSE if not.
   */
  public static function cropExists($uri, $type = NULL);

  /**
   * Loads crop based on image URI and crop type.
   *
   * @param string $uri
   *   URI of the image.
   * @param string $type
   *   Crop type.
   *
   * @return \Drupal\crop\CropInterface|null
   *   Crop entity or NULL if crop doesn't exist.
   */
  public static function findCrop($uri, $type);

}
