<?php

namespace Drupal\focal_point;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Image\ImageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\crop\CropInterface;
use Drupal\crop\CropStorageInterface;
use Drupal\crop\Entity\Crop;
use Drupal\image\Plugin\ImageEffect\ResizeImageEffect;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a base class for image effects.
 */
abstract class FocalPointEffectBase extends ResizeImageEffect implements ContainerFactoryPluginInterface {

  /**
   * Crop storage.
   *
   * @var \Drupal\crop\CropStorageInterface
   */
  protected $cropStorage;

  /**
   * Focal point configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $focalPointConfig;

  /**
   * The original image dimensions before any effects are applied.
   *
   * @var array
   */
  protected $originalImageSize;

  /**
   * Focal point manager object.
   *
   * @var \Drupal\focal_point\FocalPointManager
   */
  protected $focalPointManager;

  /**
   * The current request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  public $request;

  /**
   * Constructs a \Drupal\focal_point\FocalPointEffectBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Psr\Log\LoggerInterface $logger
   *   Image logger.
   * @param \Drupal\focal_point\FocalPointManager $focal_point_manager
   *   Focal point manager.
   * @param \Drupal\crop\CropStorageInterface $crop_storage
   *   Crop storage.
   * @param \Drupal\Core\Config\ImmutableConfig $focal_point_config
   *   Focal point configuration object.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Current request object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerInterface $logger, FocalPointManager $focal_point_manager, CropStorageInterface $crop_storage, ImmutableConfig $focal_point_config, Request $request) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger);
    $this->focalPointManager = $focal_point_manager;
    $this->cropStorage = $crop_storage;
    $this->focalPointConfig = $focal_point_config;
    $this->request = $request;
  }

  /**
   * {@inheritdoc}
   *
   * @codeCoverageIgnore
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory')->get('image'),
      new FocalPointManager($container->get('entity_type.manager')),
      $container->get('entity_type.manager')->getStorage('crop'),
      $container->get('config.factory')->get('focal_point.settings'),
      \Drupal::request()
    );
  }

  /**
   * {@inheritdoc}
   */
  public function applyEffect(ImageInterface $image) {
    // @todo: Get the original image in case there are multiple scale/crop effects?
    $this->setOriginalImageSize($image->getWidth(), $image->getHeight());
    return TRUE;
  }

  /**
   * Calculate the resize dimensions of an image.
   *
   * The calculated dimensions are based on the longest crop dimension (length
   * or width) so that the aspect ratio is preserved in all cases and that there
   * is always enough image available to the crop.
   *
   * @param int $image_width
   *   Image width.
   * @param int $image_height
   *   Image height.
   * @param int $crop_width
   *   Crop width.
   * @param int $crop_height
   *   Crop height.
   *
   * @return array
   *   Resize data.
   */
  public static function calculateResizeData($image_width, $image_height, $crop_width, $crop_height) {
    $resize_data = [];

    if ($crop_width > $crop_height) {
      $resize_data['width'] = (int) $crop_width;
      $resize_data['height'] = (int) ($crop_width * $image_height / $image_width);

      // Ensure there is enough area to crop.
      if ($resize_data['height'] < $crop_height) {
        $resize_data['width'] = (int) ($crop_height * $resize_data['width'] / $resize_data['height']);
        $resize_data['height'] = (int) $crop_height;
      }
    }
    else {
      $resize_data['width'] = (int) ($crop_height * $image_width / $image_height);
      $resize_data['height'] = (int) $crop_height;

      // Ensure there is enough area to crop.
      if ($resize_data['width'] < $crop_width) {
        $resize_data['height'] = (int) ($crop_width * $resize_data['height'] / $resize_data['width']);
        $resize_data['width'] = (int) $crop_width;
      }
    }

    return $resize_data;
  }

  /**
   * Applies the crop effect to an image.
   *
   * @param ImageInterface $image
   *   The image resource to crop.
   * @param CropInterface $crop
   *   A crop object containing the relevant crop information.
   *
   * @return bool
   *   TRUE if the image is successfully cropped, otherwise FALSE.
   */
  public function applyCrop(ImageInterface $image, CropInterface $crop) {
    // Get the top-left anchor position of the crop area.
    $anchor = $this->getAnchor($image, $crop);

    if (!$image->crop($anchor['x'], $anchor['y'], $this->configuration['width'], $this->configuration['height'])) {
      $this->logger->error(
        'Focal point scale and crop failed while scaling and cropping using the %toolkit toolkit on %path (%mimetype, %dimensions, anchor: %anchor)',
        [
          '%toolkit' => $image->getToolkitId(),
          '%path' => $image->getSource(),
          '%mimetype' => $image->getMimeType(),
          '%dimensions' => $image->getWidth() . 'x' . $image->getHeight(),
          '%anchor' => $anchor,
        ]
      );
      return FALSE;
    }

    return TRUE;
  }

  /**
   * @param \Drupal\Core\Image\ImageInterface $image
   *   The image resource whose crop is being requested.
   *
   * @return \Drupal\crop\CropInterface
   */
  public function getCrop(ImageInterface $image) {
    $crop_type = $this->focalPointConfig->get('crop_type');

    /** @var \Drupal\crop\CropInterface $crop */
    if ($crop = Crop::findCrop($image->getSource(), $crop_type)) {
      // An existing crop has been found; set the size.
      $crop->setSize($this->configuration['width'], $this->configuration['height']);
    }
    else {
      // No existing crop could be found; create a new one using the size.
      $crop = $this->cropStorage->create([
        'type' => $crop_type,
        'x' => (int) round($this->originalImageSize['width'] / 2),
        'y' => (int) round($this->originalImageSize['height'] / 2),
        'width' => $this->configuration['width'],
        'height' => $this->configuration['height'],
      ]);
    }

    return $crop;
  }

  /**
   * Get the top-left anchor position of the crop area.
   *
   * @param \Drupal\Core\Image\ImageInterface $image
   *   Image object representing original image.
   * @param \Drupal\crop\CropInterface $crop
   *   Crop entity.
   *
   * @return array
   *   Array with two keys (x, y) and anchor coordinates as values.
   *
   * @codeCoverageIgnore
   */
  public function getAnchor(ImageInterface $image, CropInterface $crop) {
    $original_focal_point = $this->getOriginalFocalPoint($crop, $this->focalPointManager);
    $focal_point = $this->transformFocalPoint($image, $original_focal_point);

    return $this->calculateAnchor($focal_point, $image, $crop);
  }

  /**
   * Set original image size.
   *
   * @param int $width
   *   The original image width.
   * @param int $height
   *   The original image height.
   */
  public function setOriginalImageSize($width, $height) {
    $this->originalImageSize = [
      'width' => $width,
      'height' => $height,
    ];
  }

  /**
   * Return the original image dimensions.
   *
   * @return array
   *   An array with keys 'width' and 'height'.
   */
  public function getOriginalImageSize() {
    // @todo: check if originalImageSize exists and if not throw an exception.
    return $this->originalImageSize;
  }

  /**
   * Calculate the top left coordinates of crop rectangle.
   *
   * This is based on Crop's anchor function with additional logic to ensure
   * that crop area doesn't fall outside of the original image. Note that the
   * image modules crop effect expects the top left coordinate of the crop
   * rectangle.
   *
   * @param array $focal_point
   *   The focal point value.
   * @param ImageInterface $image
   *   The original image to be cropped.
   * @param CropInterface $crop
   *   The crop object used to define the crop.
   *
   * @return array
   *   An array with the keys 'x' and 'y'.
   */
  protected function calculateAnchor($focal_point, ImageInterface $image, CropInterface $crop) {
    $crop_size = $crop->size();

    // The anchor must be the top-left coordinate of the crop area but the focal
    // point is expressed as the center coordinates of the crop area.
    $anchor = [
      'x' => (int) ($focal_point['x'] - ($crop_size['width'] / 2)),
      'y' => (int) ($focal_point['y'] - ($crop_size['height'] / 2)),
    ];

    $anchor = $this->constrainCropArea($anchor, $image, $crop);

    return $anchor;
  }

  /**
   * Calculate the anchor such that the crop will not exceed the image boundary.
   *
   * Given the top-left anchor (in pixels), the crop size and the image size,
   * reposition the anchor to ensure the crop area does not exceed the bounds of
   * the image.
   *
   * @param array $anchor
   *   An array with the keys 'x' and 'y'. Values are in pixels representing the
   *   top left corner of the of the crop area relative to the image.
   * @param ImageInterface $image
   *   The image to which the crop area must be constrained.
   * @param CropInterface $crop
   *   The crop object used to define the crop.
   *
   * @return array
   *   An array with the keys 'x' and 'y'.
   */
  protected function constrainCropArea($anchor, ImageInterface $image, CropInterface $crop) {
    $image_size = [
      'width' => $image->getWidth(),
      'height' => $image->getHeight(),
    ];
    $crop_size = $crop->size();

    // Ensure that the crop area doesn't fall off the bottom right of the image.
    $anchor = [
      'x' => $anchor['x'] + $crop_size['width'] <= $image_size['width'] ? $anchor['x'] : $image_size['width'] - $crop_size['width'],
      'y' => $anchor['y'] = $anchor['y'] + $crop_size['height'] <= $image_size['height'] ? $anchor['y'] : $image_size['height'] - $crop_size['height'],
    ];

    // Ensure that the crop area doesn't fall off the top left of the image.
    $anchor = [
      'x' => max(0, $anchor['x']),
      'y' => max(0, $anchor['y']),
    ];

    return $anchor;
  }

  /**
   * Returns the focal point value (in pixels) relative to the original image.
   *
   * @param \Drupal\crop\CropInterface $crop
   *   The crop object used to define the crop.
   * @param \Drupal\focal_point\FocalPointManager $focal_point_manager
   *   The focal point manager.
   *
   * @return array
   *   An array with the keys 'x' and 'y'. Values are in pixels.
   */
  protected function getOriginalFocalPoint(CropInterface $crop, FocalPointManager $focal_point_manager) {
    $focal_point = $crop->position();

    // Check if we are generating a preview image. If so get the focal point
    // from the query parameter, otherwise use the crop position.
    $preview_value = $this->getPreviewValue();
    if (!is_null($preview_value)) {
      // @todo: should we check that preview_value is valid here? If it's invalid it gets converted to 0,0.
      $original_image_size = $this->getOriginalImageSize();
      list($x, $y) = explode('x', $preview_value);
      $focal_point = $focal_point_manager->relativeToAbsolute($x, $y, $original_image_size['width'], $original_image_size['height']);
    }

    return $focal_point;
  }

  /**
   * Returns the focal point value (in pixels) relative to the provided image.
   *
   * @param ImageInterface $image
   *   Image object that the focal point must be applied to.
   * @param array $original_focal_point
   *   An array with keys 'x' and 'y' which represent the focal point in pixels
   *   relative to the original image.
   *
   * @return array
   *   An array with the keys 'x' and 'y'. Values are in pixels.
   */
  protected function transformFocalPoint(ImageInterface $image, $original_focal_point) {
    $image_size = [
      'width' => $image->getWidth(),
      'height' => $image->getHeight(),
    ];
    $original_image_size = $this->getOriginalImageSize();

    $relative_focal_point = [
      'x' => (int) round($original_focal_point['x'] / $original_image_size['width'] * $image_size['width']),
      'y' => (int) round($original_focal_point['y'] / $original_image_size['height'] * $image_size['height']),
    ];

    return $relative_focal_point;
  }

  /**
   * Get the 'focal_point_preview_value' query string value.
   *
   * @return string|null
   *   Safely return the value of the focal_point_preview_value query string if
   *   it exists.
   *
   * @codeCoverageIgnore
   */
  protected function getPreviewValue() {
    return $this->request->query->get('focal_point_preview_value');
  }

}
