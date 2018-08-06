<?php

namespace Drupal\Tests\focal_point\Unit;

use Drupal\crop\CropStorageInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Image\ImageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\focal_point\FocalPointManager;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\focal_point\Plugin\ImageEffect\FocalPointCropImageEffect;
use Psr\Log\LoggerInterface;
use Drupal\Core\Config\ImmutableConfig;

/**
 * Focal point unit test case.
 *
 * @group Focal Point
 */
abstract class FocalPointUnitTestCase extends UnitTestCase {

  /**
   * Drupal container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * Focal point manager.
   *
   * @var \Drupal\focal_point\FocalPointManagerInterface
   */
  protected $focalPointManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $crop_storage = $this->prophesize(CropStorageInterface::class);
    $entity_type_manager = $this->prophesize(EntityTypeManager::class);
    $entity_type_manager->getStorage('crop')->willReturn($crop_storage);

    $this->container = $this->prophesize(ContainerInterface::class);
    $this->container->get('entity_type.manager')->willReturn($entity_type_manager);

    $request = $this->prophesize(Request::class);
    $this->container->get('request')->willReturn($request);

    $this->focalPointManager = new FocalPointManager($entity_type_manager->reveal());
    $this->container->get('focal_point.manager')->willReturn($this->focalPointManager);

    \Drupal::setContainer($this->container->reveal());
  }

  /**
   * Get the test effects.
   *
   * @param \Drupal\Core\Image\ImageInterface|null $original_image
   *   Original Image.
   *
   * @return \Drupal\focal_point\Plugin\ImageEffect\FocalPointCropImageEffect
   *   Effect.
   */
  protected function getTestEffect(ImageInterface $original_image = NULL) {
    if (is_null($original_image)) {
      $original_image = $this->getTestImage(0, 0);
    }

    $logger = $this->prophesize(LoggerInterface::class);
    $crop_storage = $this->prophesize(CropStorageInterface::class);
    $immutable_config = $this->prophesize(ImmutableConfig::class);
    $request = $this->prophesize(Request::class);

    $effect = new FocalPointCropImageEffect([], 'plugin_id', [], $logger->reveal(), $this->focalPointManager, $crop_storage->reveal(), $immutable_config->reveal(), $request->reveal());
    $effect->setOriginalImageSize($original_image->getWidth(), $original_image->getHeight());

    return $effect;
  }

  /**
   * Get the test image.
   *
   * @param int $width
   *   Width.
   * @param int $height
   *   Height.
   *
   * @return object
   *   The image.
   */
  protected function getTestImage($width, $height) {
    $image = $this->prophesize(ImageInterface::class);
    $image->getWidth()->willReturn($width);
    $image->getHeight()->willReturn($height);

    return $image->reveal();
  }

}
