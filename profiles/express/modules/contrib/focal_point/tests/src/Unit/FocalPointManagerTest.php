<?php

namespace Drupal\Tests\focal_point\Unit;

use Drupal\crop\CropInterface;
use Drupal\focal_point\FocalPointManager;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * @coversDefaultClass \Drupal\focal_point\FocalPointManager
 *
 * @group Focal Point
 */
class FocalPointManagerTest extends FocalPointUnitTestCase {

  /**
   * Test constructor.
   *
   * @covers ::__construct
   */
  public function testConstuctor() {
    $crop_storage = $this->prophesize(CropStorageInterface::class);
    $entity_type_manager = $this->prophesize(EntityTypeManager::class);
    $entity_type_manager->getStorage('crop')->willReturn($crop_storage);

    $focal_point_manager = new FocalPointManager($entity_type_manager->reveal());
    $focal_point_manager_reflection = new \ReflectionClass(FocalPointManager::class);
    $property = $focal_point_manager_reflection->getProperty('cropStorage');
    $property->setAccessible(TRUE);
    $this->assertEquals($crop_storage->reveal(), $property->getValue($focal_point_manager));
  }

  /**
   * Validate focal point.
   *
   * @covers ::validateFocalPoint
   *
   * @dataProvider providerValidateFocalPoint
   */
  public function testValidateFocalPoint($value, $expected) {
    $this->assertEquals($expected, $this->focalPointManager->validateFocalPoint($value));
  }

  /**
   * Data provider for testFocalPoint().
   */
  public function providerValidateFocalPoint() {
    $data = [];
    $data['default_focal_point_position'] = ['50,50', TRUE];
    $data['basic_focal_point_position_1'] = ['75,25', TRUE];
    $data['basic_focal_point_position_2'] = ['3,50', TRUE];
    $data['basic_focal_point_position_3'] = ['83,6', TRUE];
    $data['basic_focal_point_position_4'] = ['2,9', TRUE];
    $data['extreme_focal_point_position_top_right'] = ['100,0', TRUE];
    $data['extreme_focal_point_position_top_left'] = ['0,0', TRUE];
    $data['extreme_focal_point_position_bottom_right'] = ['100,100', TRUE];
    $data['extreme_focal_point_position_bottom_left'] = ['0,100', TRUE];
    $data['invalid_focal_point_position_negative_x'] = ['-20,50', FALSE];
    $data['invalid_focal_point_position_negative_y'] = ['18,-3', FALSE];
    $data['invalid_focal_point_position_out_of_bounds_x'] = ['101,33', FALSE];
    $data['invalid_focal_point_position_out_of_bounds_y'] = ['44,101', FALSE];
    $data['invalid_focal_point_position_out_of_bounds_xy'] = ['313,512', FALSE];
    $data['invalid_focal_point_position_empty'] = ['', FALSE];
    $data['invalid_focal_point_position_incorrect_format_1'] = ['invalid', FALSE];
    $data['invalid_focal_point_position_incorrect_format_2'] = ['invalid,invalid', FALSE];
    $data['invalid_focal_point_position_incorrect_format_3'] = ['23,invalid', FALSE];

    return $data;
  }

  /**
   * Relative to Absolute.
   *
   * @covers ::relativeToAbsolute
   *
   * @dataProvider providerCoordinates
   */
  public function testRelativeToAbsolute($relative, $size, $absolute) {
    $this->assertEquals(
      $absolute,
      $this->focalPointManager
        ->relativeToAbsolute($relative['x'], $relative['y'], $size['width'], $size['height'])
    );
  }

  /**
   * Absolute to relative.
   *
   * @covers ::absoluteToRelative
   *
   * @dataProvider providerCoordinates
   */
  public function testAbsoluteToRelative($relative, $size, $absolute) {
    $this->assertEquals(
      $relative,
      $this->focalPointManager
        ->absoluteToRelative($absolute['x'], $absolute['y'], $size['width'], $size['height'])
    );
  }

  /**
   * Data provider for testRelativeToAbsolute() and absoluteToRelative().
   */
  public function providerCoordinates() {
    $data = [];
    $data['top_left'] = [
      ['x' => 0, 'y' => 0],
      ['width' => 1000, 'height' => 2000],
      ['x' => 0, 'y' => 0],
    ];
    $data['basic_case_1'] = [
      ['x' => 25, 'y' => 50],
      ['width' => 1000, 'height' => 2000],
      ['x' => 250, 'y' => 1000],
    ];
    $data['basic_case_2'] = [
      ['x' => 50, 'y' => 25],
      ['width' => 1000, 'height' => 2000],
      ['x' => 500, 'y' => 500],
    ];
    $data['basic_case_3'] = [
      ['x' => 50, 'y' => 50],
      ['width' => 1000, 'height' => 2000],
      ['x' => 500, 'y' => 1000],
    ];
    $data['basic_case_4'] = [
      ['x' => 75, 'y' => 50],
      ['width' => 1000, 'height' => 2000],
      ['x' => 750, 'y' => 1000],
    ];
    $data['basic_case_5'] = [
      ['x' => 100, 'y' => 75],
      ['width' => 1000, 'height' => 2000],
      ['x' => 1000, 'y' => 1500],
    ];
    $data['bottom_right'] = [
      ['x' => 100, 'y' => 100],
      ['width' => 1000, 'height' => 2000],
      ['x' => 1000, 'y' => 2000],
    ];

    return $data;
  }

  /**
   * Save Crop Entity.
   *
   * @covers ::saveCropEntity
   */
  public function testSaveCropEntity() {
    // Test that crop is saved when focal point value has changed.
    $crop = $this->prophesize(CropInterface::class);
    $crop->anchor()->willReturn(['x' => 50, 'y' => 50])->shouldBeCalledTimes(1);
    $crop->setPosition(20, 20)->willReturn($crop->reveal())->shouldBeCalledTimes(1);
    $crop->save()->willReturn($crop->reveal())->shouldBeCalledTimes(1);

    $this->focalPointManager->saveCropEntity(10, 10, 200, 200, $crop->reveal());

    // Test that crop is not saved when focal point value is unchanged.
    $crop = $this->prophesize(CropInterface::class);
    $crop->anchor()->willReturn(['x' => 20, 'y' => 20])->shouldBeCalledTimes(1);
    $crop->setPosition()->willReturn($crop->reveal())->shouldBeCalledTimes(0);
    $crop->save()->willReturn($crop->reveal())->shouldBeCalledTimes(0);

    $this->focalPointManager->saveCropEntity(10, 10, 200, 200, $crop->reveal());
  }

}
