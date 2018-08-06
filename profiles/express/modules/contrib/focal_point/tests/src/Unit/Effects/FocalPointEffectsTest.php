<?php

namespace Drupal\Tests\focal_point\Unit\Effects;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\crop\CropInterface;
use Drupal\crop\CropStorageInterface;
use Drupal\focal_point\Plugin\ImageEffect\FocalPointCropImageEffect;
use Drupal\focal_point\FocalPointEffectBase;
use Psr\Log\LoggerInterface;
use Drupal\Tests\focal_point\Unit\FocalPointUnitTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the Focal Point image effects.
 *
 * @group Focal Point
 *
 * @coversDefaultClass \Drupal\focal_point\FocalPointEffectBase
 */
class FocalPointEffectsTest extends FocalPointUnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Test the construct method.
   *
   * @covers ::__construct
   */
  public function testEffectConstructor() {
    // We can't use $this->getTestEffect here because the attributes tested
    // below won't match.
    $logger = $this->prophesize(LoggerInterface::class);
    $crop_storage = $this->prophesize(CropStorageInterface::class);
    $focal_point_config = $this->prophesize(ImmutableConfig::class);
    $request = $this->prophesize(Request::class);

    $effect = new FocalPointCropImageEffect([], 'plugin_id', [], $logger->reveal(), $this->focalPointManager, $crop_storage->reveal(), $focal_point_config->reveal(), $request->reveal());
    $this->assertAttributeEquals($crop_storage->reveal(), 'cropStorage', $effect);
    $this->assertAttributeEquals($focal_point_config->reveal(), 'focalPointConfig', $effect);
  }

  /**
   * Test the resize calculation.
   *
   * @covers ::calculateResizeData
   *
   * @dataProvider calculateResizeDataProvider
   */
  public function testCalculateResizeData($image_width, $image_height, $crop_width, $crop_height, $expected) {
    $this->assertSame($expected, FocalPointEffectBase::calculateResizeData($image_width, $image_height, $crop_width, $crop_height));
  }

  /**
   * Data provider for testCalculateResizeData().
   *
   * @see FocalPointEffectsTest::testCalculateResizeData()
   */
  public function calculateResizeDataProvider() {
    $data = [];
    // @codingStandardsIgnoreStart
    $data['horizontal_image_horizontal_crop'] = [640, 480, 300, 100, ['width' => 300, 'height' => 225]];
    $data['horizontal_image_vertical_crop'] = [640, 480, 100, 300, ['width' => 400, 'height' => 300]];
    $data['vertical_image_horizontal_crop'] = [480, 640, 300, 100, ['width' => 300, 'height' => 400]];
    $data['vertical_image_vertical_crop'] = [480, 640, 100, 300, ['width' => 225, 'height' => 300]];
    $data['horizontal_image_too_large_crop'] = [640, 480, 3000, 1000, ['width' => 3000, 'height' => 2250]];
    $data['image_too_narrow_to_crop_after_resize'] = [1920, 1080, 400, 300, ['width' => 533, 'height' => 300]];
    $data['image_too_short_to_crop_after_resize'] = [200, 400, 1000, 1000, ['width' => 1000, 'height' => 2000]];
    // @codingStandardsIgnoreEnd
    return $data;
  }

  /**
   * Test the getting and setting of the original image size.
   *
   * @covers ::setOriginalImageSize
   * @covers ::getOriginalImageSize
   */
  public function testSetGetOriginalImageSize() {
    $original_image_dimensions = ['width' => 131, 'height' => 313];
    $original_image = $this->getTestImage($original_image_dimensions['width'], $original_image_dimensions['height']);

    $effect = $this->getTestEffect($original_image);

    $this->assertArrayEquals($original_image_dimensions, $effect->getOriginalImageSize());
  }

  /**
   * Test the focal point transformation.
   *
   * @covers ::transformFocalPoint
   *
   * @dataProvider transformFocalPointProvider
   */
  public function testTransformFocalPoint($image_dimensions, $original_image_dimensions, $original_focal_point, $expected_focal_point) {
    $image = $this->getTestImage($image_dimensions['width'], $image_dimensions['height']);
    $original_image = $this->getTestImage($original_image_dimensions['width'], $original_image_dimensions['height']);

    // Use reflection to test a private/protected method.
    $effect = $this->getTestEffect($original_image);
    $effect_reflection = new \ReflectionClass(TestFocalPointEffectBase::class);
    $method = $effect_reflection->getMethod('transformFocalPoint');
    $method->setAccessible(TRUE);

    $this->assertSame($expected_focal_point, $method->invokeArgs($effect, [$image, $original_focal_point]));
  }

  /**
   * Data provider for testTransformFocalPoint().
   *
   * @see FocalPointEffectsTest::testTransformFocalPoint()
   */
  public function transformFocalPointProvider() {
    $data = [];
    // @codingStandardsIgnoreStart
    $data['no_scale'] = [['width' => 800, 'height' => 600], ['width' => 800, 'height' => 600], ['x' => 300, 'y' => 400], ['x' => 300, 'y' => 400]];
    $data['scaled_down'] = [['width' => 800, 'height' => 600], ['width' => 2500, 'height' => 4000], ['x' => 100, 'y' => 100], ['x' => 32, 'y' => 15]];
    $data['scaled_up'] = [['width' => 800, 'height' => 600], ['width' => 460, 'height' => 313], ['x' => 500, 'y' => 900], ['x' => 870, 'y' => 1725]];
    $data['different_orientation'] = [['width' => 350, 'height' => 200], ['width' => 5000, 'height' => 4000], ['x' => 2100, 'y' => 313], ['x' => 147, 'y' => 16]];
    // @codingStandardsIgnoreEnd
    return $data;
  }

  /**
   * Test getting the original focal point.
   *
   * @covers ::getOriginalFocalPoint
   */
  public function testGetOriginalFocalPoint() {
    $original_image = $this->getTestImage(50, 50);

    // Create a instance of TestFocalPointEffectBase since we need to override
    // the getPreviewValue method.
    $logger = $this->prophesize(LoggerInterface::class);
    $crop_storage = $this->prophesize(CropStorageInterface::class);
    $immutable_config = $this->prophesize(ImmutableConfig::class);
    $request = $this->prophesize(Request::class);

    $effect = new TestFocalPointEffectBase([], 'plugin_id', [], $logger->reveal(), $this->focalPointManager, $crop_storage->reveal(), $immutable_config->reveal(), $request->reveal());
    $effect->setOriginalImageSize($original_image->getWidth(), $original_image->getHeight());

    // Use reflection to test a private/protected method.
    $effect_reflection = new \ReflectionClass(TestFocalPointEffectBase::class);
    $method = $effect_reflection->getMethod('getOriginalFocalPoint');
    $method->setAccessible(TRUE);

    // Mock crop object.
    $expected = ['x' => 313, 'y' => 404];
    $crop = $this->prophesize(CropInterface::class);
    $crop->position()->willReturn($expected);

    // Non-preview.
    $this->assertSame($expected, $method->invokeArgs($effect, [$crop->reveal(), $this->focalPointManager]));

    // Preview test.
    $query_string = '500x250';
    $expected = ['x' => 250, 'y' => 125];
    $effect->setPreviewValue($query_string);
    $this->assertSame($expected, $method->invokeArgs($effect, [$crop->reveal(), $this->focalPointManager]));
  }

  /**
   * Test constrain logic.
   *
   * @covers ::constrainCropArea
   *
   * @dataProvider constrainCropAreaProvider
   */
  public function testConstrainCropArea($anchor, $image_size, $crop_size, $expected) {
    $image = $this->getTestImage($image_size['width'], $image_size['height']);
    $crop = $this->prophesize(CropInterface::class);
    $crop->size()->willReturn($crop_size);

    // Use reflection to test a private/protected method.
    $effect = $this->getTestEffect();
    $effect_reflection = new \ReflectionClass(TestFocalPointEffectBase::class);
    $method = $effect_reflection->getMethod('constrainCropArea');
    $method->setAccessible(TRUE);

    $args = [$anchor, $image, $crop->reveal()];
    $this->assertSame($expected, $method->invokeArgs($effect, $args));
  }

  /**
   * Data provider for testConstrainCropArea().
   *
   * @see FocalPointEffectsTest::testConstrainCropArea()
   */
  public function constrainCropAreaProvider() {
    $data = [];
    // @codingStandardsIgnoreStart
    $data['constrained-top-left'] = [['x' => -10, 'y' => -10], ['width' => 1000, 'height' => 1000], ['width' => 100, 'height' => 100], ['x' => 0, 'y' => 0]];
    $data['constrained-top-center'] = [['x' => 10, 'y' => -10], ['width' => 1000, 'height' => 1000], ['width' => 100, 'height' => 100], ['x' => 10, 'y' => 0]];
    $data['constrained-top-right'] = [['x' => 2000, 'y' => -10], ['width' => 1000, 'height' => 1000], ['width' => 100, 'height' => 100], ['x' => 900, 'y' => 0]];
    $data['constrained-center-left'] = [['x' => -10, 'y' => 313], ['width' => 1000, 'height' => 1000], ['width' => 100, 'height' => 100], ['x' => 0, 'y' => 313]];
    $data['unconstrained'] = [['x' => 500, 'y' => 500], ['width' => 1000, 'height' => 1000], ['width' => 100, 'height' => 100], ['x' => 500, 'y' => 500]];
    $data['constrained-center-right'] = [['x' => 3000, 'y' => 313], ['width' => 1000, 'height' => 1000], ['width' => 100, 'height' => 100], ['x' => 900, 'y' => 313]];
    $data['constrained-bottom-left'] = [['x' => -10, 'y' => 2000], ['width' => 1000, 'height' => 1000], ['width' => 100, 'height' => 100], ['x' => 0, 'y' => 900]];
    $data['constrained-bottom-center'] = [['x' => 313, 'y' => 2000], ['width' => 1000, 'height' => 1000], ['width' => 100, 'height' => 100], ['x' => 313, 'y' => 900]];
    $data['constrained-bottom-right'] = [['x' => 3000, 'y' => 2000], ['width' => 1000, 'height' => 1000], ['width' => 100, 'height' => 100], ['x' => 900, 'y' => 900]];
    // @codingStandardsIgnoreEnd
    return $data;
  }

  /**
   * Test calculating the anchor.
   *
   * @covers ::calculateAnchor
   *
   * @dataProvider calculateAnchorProvider
   */
  public function testCalculateAnchor($focal_point, $image_size, $crop_size, $expected) {
    $image = $this->getTestImage($image_size['width'], $image_size['height']);
    $crop = $this->prophesize(CropInterface::class);
    $crop->size()->willReturn($crop_size);

    // Use reflection to test a private/protected method.
    $effect = $this->getTestEffect();
    $effect_reflection = new \ReflectionClass(TestFocalPointEffectBase::class);
    $method = $effect_reflection->getMethod('calculateAnchor');
    $method->setAccessible(TRUE);

    $args = [$focal_point, $image, $crop->reveal()];
    $this->assertSame($expected, $method->invokeArgs($effect, $args));
  }

  /**
   * Data provider for testCalculateAnchor().
   *
   * @see FocalPointEffectsTest::testCalculateAnchor()
   */
  public function calculateAnchorProvider() {
    $data = [];
    // @codingStandardsIgnoreStart

    // Square image with square crop.
    $original_image_size = ['width' => 2000, 'height' => 2000];
    $cropped_image_size = ['width' => 1000, 'height' => 1000];
    list($top, $left, $center, $bottom, $right) = [100, 100, 1000, 1900, 1900];
    $data['square_image_with_square_crop__top_left'] = [['x' => $left, 'y' => $top], $original_image_size, $cropped_image_size, ['x' => 0, 'y' => 0]];
    $data['square_image_with_square_crop__top_center'] = [['x' => $center, 'y' => $top], $original_image_size, $cropped_image_size, ['x' => 500, 'y' => 0]];
    $data['square_image_with_square_crop__top_right'] = [['x' => $right, 'y' => $top], $original_image_size, $cropped_image_size, ['x' => 1000, 'y' => 0]];
    $data['square_image_with_square_crop__center_left'] = [['x' => $left, 'y' => $center], $original_image_size, $cropped_image_size, ['x' => 0, 'y' => 500]];
    $data['square_image_with_square_crop__center_center'] = [['x' => $center, 'y' => $center], $original_image_size, $cropped_image_size, ['x' => 500, 'y' => 500]];
    $data['square_image_with_square_crop__center_right'] = [['x' => $right, 'y' => $center], $original_image_size, $cropped_image_size, ['x' => 1000, 'y' => 500]];
    $data['square_image_with_square_crop__bottom_left'] = [['x' => $left, 'y' => $bottom], $original_image_size, $cropped_image_size, ['x' => 0, 'y' => 1000]];
    $data['square_image_with_square_crop__bottom_center'] = [['x' => $center, 'y' => $bottom], $original_image_size, $cropped_image_size, ['x' => 500, 'y' => 1000]];
    $data['square_image_with_square_crop__bottom_right'] = [['x' => $right, 'y' => $bottom], $original_image_size, $cropped_image_size, ['x' => 1000, 'y' => 1000]];

    // Square image with horizontal crop.
    $original_image_size = ['width' => 2000, 'height' => 2000];
    $cropped_image_size = ['width' => 1000, 'height' => 250];
    list($top, $left, $center, $bottom, $right) = [100, 100, 1000, 1900, 1900];
    $data['square_image_with_horizontal_crop__top_left'] = [['x' => $left, 'y' => $top], $original_image_size, $cropped_image_size, ['x' => 0, 'y' => 0]];
    $data['square_image_with_horizontal_crop__top_center'] = [['x' => $center, 'y' => $top], $original_image_size, $cropped_image_size, ['x' => 500, 'y' => 0]];
    $data['square_image_with_horizontal_crop__top_right'] = [['x' => $right, 'y' => $top], $original_image_size, $cropped_image_size, ['x' => 1000, 'y' => 0]];
    $data['square_image_with_horizontal_crop__center_left'] = [['x' => $left, 'y' => $center], $original_image_size, $cropped_image_size, ['x' => 0, 'y' => 875]];
    $data['square_image_with_horizontal_crop__center_center'] = [['x' => $center, 'y' => $center], $original_image_size, $cropped_image_size, ['x' => 500, 'y' => 875]];
    $data['square_image_with_horizontal_crop__center_right'] = [['x' => $right, 'y' => $center], $original_image_size, $cropped_image_size, ['x' => 1000, 'y' => 875]];
    $data['square_image_with_horizontal_crop__bottom_left'] = [['x' => $left, 'y' => $bottom], $original_image_size, $cropped_image_size, ['x' => 0, 'y' => 1750]];
    $data['square_image_with_horizontal_crop__bottom_center'] = [['x' => $center, 'y' => $bottom], $original_image_size, $cropped_image_size, ['x' => 500, 'y' => 1750]];
    $data['square_image_with_horizontal_crop__bottom_right'] = [['x' => $right, 'y' => $bottom], $original_image_size, $cropped_image_size, ['x' => 1000, 'y' => 1750]];

    // Square image with vertical crop.
    $original_image_size = ['width' => 2000, 'height' => 2000];
    $cropped_image_size = ['width' => 100, 'height' => 500];
    list($top, $left, $center, $bottom, $right) = [100, 100, 1000, 1900, 1900];
    $data['square_image_with_vertical_crop__top_left'] = [['x' => $left, 'y' => $top], $original_image_size, $cropped_image_size, ['x' => 50, 'y' => 0]];
    $data['square_image_with_vertical_crop__top_center'] = [['x' => $center, 'y' => $top], $original_image_size, $cropped_image_size, ['x' => 950, 'y' => 0]];
    $data['square_image_with_vertical_crop__top_right'] = [['x' => $right, 'y' => $top], $original_image_size, $cropped_image_size, ['x' => 1850, 'y' => 0]];
    $data['square_image_with_vertical_crop__center_left'] = [['x' => $left, 'y' => $center], $original_image_size, $cropped_image_size, ['x' => 50, 'y' => 750]];
    $data['square_image_with_vertical_crop__center_center'] = [['x' => $center, 'y' => $center], $original_image_size, $cropped_image_size, ['x' => 950, 'y' => 750]];
    $data['square_image_with_vertical_crop__center_right'] = [['x' => $right, 'y' => $center], $original_image_size, $cropped_image_size, ['x' => 1850, 'y' => 750]];
    $data['square_image_with_vertical_crop__bottom_left'] = [['x' => $left, 'y' => $bottom], $original_image_size, $cropped_image_size, ['x' => 50, 'y' => 1500]];
    $data['square_image_with_vertical_crop__bottom_center'] = [['x' => $center, 'y' => $bottom], $original_image_size, $cropped_image_size, ['x' => 950, 'y' => 1500]];
    $data['square_image_with_vertical_crop__bottom_right'] = [['x' => $right, 'y' => $bottom], $original_image_size, $cropped_image_size, ['x' => 1850, 'y' => 1500]];

    // Horizontal image with square crop.
    $original_image_size = ['width' => 1500, 'height' => 500];
    $cropped_image_size = ['width' => 200, 'height' => 200];
    list($top, $left, $vcenter, $hcenter, $bottom, $right) = [10, 10, 250, 750, 490, 1490];
    $data['horizontal_image_with_square_crop__top_left'] = [['x' => $left, 'y' => $top], $original_image_size, $cropped_image_size, ['x' => 0, 'y' => 0]];
    $data['horizontal_image_with_square_crop__top_center'] = [['x' => $hcenter, 'y' => $top], $original_image_size, $cropped_image_size, ['x' => 650, 'y' => 0]];
    $data['horizontal_image_with_square_crop__top_right'] = [['x' => $right, 'y' => $top], $original_image_size, $cropped_image_size, ['x' => 1300, 'y' => 0]];
    $data['horizontal_image_with_square_crop__center_left'] = [['x' => $left, 'y' => $vcenter], $original_image_size, $cropped_image_size, ['x' => 0, 'y' => 150]];
    $data['horizontal_image_with_square_crop__center_center'] = [['x' => $hcenter, 'y' => $vcenter], $original_image_size, $cropped_image_size, ['x' => 650, 'y' => 150]];
    $data['horizontal_image_with_square_crop__center_right'] = [['x' => $right, 'y' => $vcenter], $original_image_size, $cropped_image_size, ['x' => 1300, 'y' => 150]];
    $data['horizontal_image_with_square_crop__bottom_left'] = [['x' => $left, 'y' => $bottom], $original_image_size, $cropped_image_size, ['x' => 0, 'y' => 300]];
    $data['horizontal_image_with_square_crop__bottom_center'] = [['x' => $hcenter, 'y' => $bottom], $original_image_size, $cropped_image_size, ['x' => 650, 'y' =>300]];
    $data['horizontal_image_with_square_crop__bottom_right'] = [['x' => $right, 'y' => $bottom], $original_image_size, $cropped_image_size, ['x' => 1300, 'y' => 300]];

    // Horizontal image with horizontal crop.
    $original_image_size = ['width' => 1024, 'height' => 768];
    $cropped_image_size = ['width' => 800, 'height' => 50];
    list($top, $left, $vcenter, $hcenter, $bottom, $right) = [10, 10, 384, 512, 750, 1000];
    $data['horizontal_image_with_horizontal_crop__top_left'] = [['x' => $left, 'y' => $top], $original_image_size, $cropped_image_size, ['x' => 0, 'y' => 0]];
    $data['horizontal_image_with_horizontal_crop__top_center'] = [['x' => $hcenter, 'y' => $top], $original_image_size, $cropped_image_size, ['x' => 112, 'y' => 0]];
    $data['horizontal_image_with_horizontal_crop__top_right'] = [['x' => $right, 'y' => $top], $original_image_size, $cropped_image_size, ['x' => 224, 'y' => 0]];
    $data['horizontal_image_with_horizontal_crop__center_left'] = [['x' => $left, 'y' => $vcenter], $original_image_size, $cropped_image_size, ['x' => 0, 'y' => 359]];
    $data['horizontal_image_with_horizontal_crop__center_center'] = [['x' => $hcenter, 'y' => $vcenter], $original_image_size, $cropped_image_size, ['x' => 112, 'y' => 359]];
    $data['horizontal_image_with_horizontal_crop__center_right'] = [['x' => $right, 'y' => $vcenter], $original_image_size, $cropped_image_size, ['x' => 224, 'y' => 359]];
    $data['horizontal_image_with_horizontal_crop__bottom_left'] = [['x' => $left, 'y' => $bottom], $original_image_size, $cropped_image_size, ['x' => 0, 'y' => 718]];
    $data['horizontal_image_with_horizontal_crop__bottom_center'] = [['x' => $hcenter, 'y' => $bottom], $original_image_size, $cropped_image_size, ['x' => 112, 'y' => 718]];
    $data['horizontal_image_with_horizontal_crop__bottom_right'] = [['x' => $right, 'y' => $bottom], $original_image_size, $cropped_image_size, ['x' => 224, 'y' => 718]];

    // Horizontal image with vertical crop.
    $original_image_size = ['width' => 1024, 'height' => 768];
    $cropped_image_size = ['width' => 313, 'height' => 600];
    list($top, $left, $vcenter, $hcenter, $bottom, $right) = [10, 10, 384, 512, 750, 1000];
    $data['horizontal_image_with_vertical_crop__top_left'] = [['x' => $left, 'y' => $top], $original_image_size, $cropped_image_size, ['x' => 0, 'y' => 0]];
    $data['horizontal_image_with_vertical_crop__top_center'] = [['x' => $hcenter, 'y' => $top], $original_image_size, $cropped_image_size, ['x' => 355, 'y' => 0]];
    $data['horizontal_image_with_vertical_crop__top_right'] = [['x' => $right, 'y' => $top], $original_image_size, $cropped_image_size, ['x' => 711, 'y' => 0]];
    $data['horizontal_image_with_vertical_crop__center_left'] = [['x' => $left, 'y' => $vcenter], $original_image_size, $cropped_image_size, ['x' => 0, 'y' => 84]];
    $data['horizontal_image_with_vertical_crop__center_center'] = [['x' => $hcenter, 'y' => $vcenter], $original_image_size, $cropped_image_size, ['x' => 355, 'y' => 84]];
    $data['horizontal_image_with_vertical_crop__center_right'] = [['x' => $right, 'y' => $vcenter], $original_image_size, $cropped_image_size, ['x' => 711, 'y' => 84]];
    $data['horizontal_image_with_vertical_crop__bottom_left'] = [['x' => $left, 'y' => $bottom], $original_image_size, $cropped_image_size, ['x' => 0, 'y' => 168]];
    $data['horizontal_image_with_vertical_crop__bottom_center'] = [['x' => $hcenter, 'y' => $bottom], $original_image_size, $cropped_image_size, ['x' => 355, 'y' => 168]];
    $data['horizontal_image_with_vertical_crop__bottom_right'] = [['x' => $right, 'y' => $bottom], $original_image_size, $cropped_image_size, ['x' => 711, 'y' => 168]];

    // Vertical image with square crop.
    $original_image_size = ['width' => 500, 'height' => 2500];
    $cropped_image_size = ['width' => 100, 'height' => 100];
    list($top, $left, $vcenter, $hcenter, $bottom, $right) = [50, 50, 1250, 250, 2450, 450];
    $data['vertical_image_with_square_crop__top_left'] = [['x' => $left, 'y' => $top], $original_image_size, $cropped_image_size, ['x' => 0, 'y' => 0]];
    $data['vertical_image_with_square_crop__top_center'] = [['x' => $hcenter, 'y' => $top], $original_image_size, $cropped_image_size, ['x' => 200, 'y' => 0]];
    $data['vertical_image_with_square_crop__top_right'] = [['x' => $right, 'y' => $top], $original_image_size, $cropped_image_size, ['x' => 400, 'y' => 0]];
    $data['vertical_image_with_square_crop__center_left'] = [['x' => $left, 'y' => $vcenter], $original_image_size, $cropped_image_size, ['x' => 0, 'y' => 1200]];
    $data['vertical_image_with_square_crop__center_center'] = [['x' => $hcenter, 'y' => $vcenter], $original_image_size, $cropped_image_size, ['x' => 200, 'y' => 1200]];
    $data['vertical_image_with_square_crop__center_right'] = [['x' => $right, 'y' => $vcenter], $original_image_size, $cropped_image_size, ['x' => 400, 'y' => 1200]];
    $data['vertical_image_with_square_crop__bottom_left'] = [['x' => $left, 'y' => $bottom], $original_image_size, $cropped_image_size, ['x' => 0, 'y' => 2400]];
    $data['vertical_image_with_square_crop__bottom_center'] = [['x' => $hcenter, 'y' => $bottom], $original_image_size, $cropped_image_size, ['x' => 200, 'y' => 2400]];
    $data['vertical_image_with_square_crop__bottom_right'] = [['x' => $right, 'y' => $bottom], $original_image_size, $cropped_image_size, ['x' => 400, 'y' => 2400]];

    // Vertical image with horizontal crop.
    $original_image_size = ['width' => 1111, 'height' => 313];
    $cropped_image_size = ['width' => 400, 'height' => 73];
    list($top, $left, $vcenter, $hcenter, $bottom, $right) = [10, 10, 384, 512, 750, 1000];
    $data['vertical_image_with_horizontal_crop__top_left'] = [['x' => $left, 'y' => $top], $original_image_size, $cropped_image_size, ['x' => 0, 'y' => 0]];
    $data['vertical_image_with_horizontal_crop__top_center'] = [['x' => $hcenter, 'y' => $top], $original_image_size, $cropped_image_size, ['x' => 312, 'y' => 0]];
    $data['vertical_image_with_horizontal_crop__top_right'] = [['x' => $right, 'y' => $top], $original_image_size, $cropped_image_size, ['x' => 711, 'y' => 0]];
    $data['vertical_image_with_horizontal_crop__center_left'] = [['x' => $left, 'y' => $vcenter], $original_image_size, $cropped_image_size, ['x' => 0, 'y' => 240]];
    $data['vertical_image_with_horizontal_crop__center_center'] = [['x' => $hcenter, 'y' => $vcenter], $original_image_size, $cropped_image_size, ['x' => 312, 'y' => 240]];
    $data['vertical_image_with_horizontal_crop__center_right'] = [['x' => $right, 'y' => $vcenter], $original_image_size, $cropped_image_size, ['x' => 711, 'y' => 240]];
    $data['vertical_image_with_horizontal_crop__bottom_left'] = [['x' => $left, 'y' => $bottom], $original_image_size, $cropped_image_size, ['x' => 0, 'y' => 240]];
    $data['vertical_image_with_horizontal_crop__bottom_center'] = [['x' => $hcenter, 'y' => $bottom], $original_image_size, $cropped_image_size, ['x' => 312, 'y' => 240]];
    $data['vertical_image_with_horizontal_crop__bottom_right'] = [['x' => $right, 'y' => $bottom], $original_image_size, $cropped_image_size, ['x' => 711, 'y' => 240]];

    // Vertical image with vertical crop.
    $original_image_size = ['width' => 200, 'height' => 2000];
    $cropped_image_size = ['width' => 100, 'height' => 1111];
    list($top, $left, $vcenter, $hcenter, $bottom, $right) = [10, 10, 384, 512, 750, 1000];
    $data['vertical_image_with_vertical_crop__top_left'] = [['x' => $left, 'y' => $top], $original_image_size, $cropped_image_size, ['x' => 0, 'y' => 0]];
    $data['vertical_image_with_vertical_crop__top_center'] = [['x' => $hcenter, 'y' => $top], $original_image_size, $cropped_image_size, ['x' => 100, 'y' => 0]];
    $data['vertical_image_with_vertical_crop__top_right'] = [['x' => $right, 'y' => $top], $original_image_size, $cropped_image_size, ['x' => 100, 'y' => 0]];
    $data['vertical_image_with_vertical_crop__center_left'] = [['x' => $left, 'y' => $vcenter], $original_image_size, $cropped_image_size, ['x' => 0, 'y' => 0]];
    $data['vertical_image_with_vertical_crop__center_center'] = [['x' => $hcenter, 'y' => $vcenter], $original_image_size, $cropped_image_size, ['x' => 100, 'y' => 0]];
    $data['vertical_image_with_vertical_crop__center_right'] = [['x' => $right, 'y' => $vcenter], $original_image_size, $cropped_image_size, ['x' => 100, 'y' => 0]];
    $data['vertical_image_with_vertical_crop__bottom_left'] = [['x' => $left, 'y' => $bottom], $original_image_size, $cropped_image_size, ['x' => 0, 'y' => 194]];
    $data['vertical_image_with_vertical_crop__bottom_center'] = [['x' => $hcenter, 'y' => $bottom], $original_image_size, $cropped_image_size, ['x' => 100, 'y' => 194]];
    $data['vertical_image_with_vertical_crop__bottom_right'] = [['x' => $right, 'y' => $bottom], $original_image_size, $cropped_image_size, ['x' => 100, 'y' => 194]];

    // @codingStandardsIgnoreEnd
    return $data;
  }

}

/**
 * Dummy class for testing FocalPointEffectBase.
 *
 * @package Drupal\Tests\focal_point\Unit\Effects
 */
class TestFocalPointEffectBase extends FocalPointEffectBase {

  /**
   * A focal point string in the form XxY, or null if we're not testing preview.
   *
   * @var string|null
   */
  protected $previewValue = NULL;

  /**
   * Get the preview value.
   *
   * @return string|null
   *   A focal point string in the form XxY, or null.
   */
  protected function getPreviewValue() {
    return $this->previewValue;
  }

  /**
   * Set the preview value.
   *
   * @param string|null $value
   *   A focal point string in the form XxY, or null.
   */
  public function setPreviewValue($value) {
    $this->previewValue = $value;
  }

}
