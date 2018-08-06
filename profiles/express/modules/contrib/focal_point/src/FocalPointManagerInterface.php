<?php

namespace Drupal\focal_point;

use Drupal\file\FileInterface;
use Drupal\crop\CropInterface;

/**
 * Defines an interface for focal point manager.
 */
interface FocalPointManagerInterface {

  /**
   * Validates focal point string representation.
   *
   * @param string $focal_point
   *   Focal point as submitted in the form. For example: 23,64 is valid while
   *   123,942 and foo,bar are not.
   *
   * @return bool
   *   TRUE if valid and FALSE if not.
   */
  public function validateFocalPoint($focal_point);

  /**
   * Converts relative focal point coordinates to absolute coordinates.
   *
   * Absolute coordinates are always specified in the context of the original
   * image. Relative coordinates are percentages from the top left of the image
   * so that using 50 for both x and y would mean to put the focal point in the
   * center of the image.
   *
   * @param float $x
   *   Relative X coordinate of the focal point. Maximum is 100.
   * @param float $y
   *   Relative Y coordinate of the focal point. Maximum is 100.
   * @param int $width
   *   Width of the original image.
   * @param int $height
   *   Height of the original image.
   *
   * @return array
   *   The absolute coordinates of the focal point on the original image. 'x'
   *   and 'y' are used for array keys and corresponding coordinates as values.
   *
   * @see absoluteToRelative
   */
  public function relativeToAbsolute($x, $y, $width, $height);

  /**
   * Converts absolute focal point coordinates to relative coordinates.
   *
   * @param int $x
   *   Absolute X coordinate of the focal point on the original image.
   * @param int $y
   *   Absolute Y coordinate of the focal point on the original image.
   * @param int $width
   *   Width of the original image.
   * @param int $height
   *   Height of the original image.
   *
   * @return array
   *   The relative coordinates of the focal point where each coordinate is a
   *   percentage. 'x' and 'y' are used for array keys and corresponding
   *   coordinates as values.
   *
   * @see relativeToAbsolute
   */
  public function absoluteToRelative($x, $y, $width, $height);

  /**
   * Gets a crop entity for the given file.
   *
   * If an existing crop entity is not found then a new one is created.
   *
   * @param \Drupal\file\FileInterface $file
   *   File this focal point applies to.
   * @param string $crop_type
   *   Crop type to be used.
   *
   * @return \Drupal\crop\CropInterface
   *   Created crop entity.
   */
  public function getCropEntity(FileInterface $file, $crop_type);

  /**
   * Creates (or updates) a crop entity using relative focal point coordinates.
   *
   * Relative coordinates are percentages from the top left of the image
   * so that using 50 for both x and y would mean to put the focal point in the
   * center of the image.
   *
   * @param float $x
   *   Relative X coordinate of the focal point. Maximum is 100.
   * @param float $y
   *   Relative Y coordinate of the focal point. Maximum is 100.
   * @param int $width
   *   Width of the original image.
   * @param int $height
   *   Height of the original image.
   * @param \Drupal\crop\CropInterface $crop
   *   Crop entity for the given file.
   *
   * @return \Drupal\crop\CropInterface
   *   Saved crop entity.
   */
  public function saveCropEntity($x, $y, $width, $height, CropInterface $crop);

}
