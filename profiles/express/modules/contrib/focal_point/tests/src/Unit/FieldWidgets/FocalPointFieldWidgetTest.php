<?php

namespace Drupal\Tests\focal_point\Unit\FieldWidgets;

use Drupal\focal_point\FocalPointManager;
use Drupal\focal_point\Plugin\Field\FieldWidget\FocalPointImageWidget;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Render\ElementInfoManagerInterface;

/**
 * @coversDefaultClass \Drupal\focal_point\Plugin\Field\FieldWidget\FocalPointImageWidget
 *
 * @group Focal Point
 */
class FocalPointFieldWidgetTest extends UnitTestCase {

  /**
   * A simple form element for testing.
   *
   * @var testElement
   */
  protected $testElement;

  /**
   * A mock FormState object for testing.
   *
   * @var \Drupal\Core\Form\FormStateInterface
   */
  protected $testFormState;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create some mock objects.
    $entity_type_manager = $this->prophesize(EntityTypeManager::class)->reveal();
    $focal_point_manager = new FocalPointManager($entity_type_manager);

    // Create and set the mock container.
    $container = $this->prophesize(ContainerInterface::class);
    $container->get('entity_type.manager')->willReturn($entity_type_manager);
    $container->get('focal_point.manager')->willReturn($focal_point_manager);
    \Drupal::setContainer($container->reveal());

    // Setup an image element for testing.
    $this->testElement = [
      '#title' => 'some title',
      '#parents' => ['field_image'],
    ];

    // Setup a mock form state object for testing.
    // @todo: Figure out why using prophesize for this mock causes an exception.
    $this->testFormState = $this->getMockBuilder('\Drupal\Core\Form\FormStateInterface')->disableOriginalConstructor()->getMock();
  }

  /**
   * Testing focal point validation.
   *
   * @covers ::validateFocalPoint
   *
   * @dataProvider providerValidateFocalPoint
   */
  public function testValidateFocalPoint($value, $is_valid) {
    $this->testElement['#value'] = $value;

    // Test that an invalid focal point value sets a form error and a valid
    // focal point value does not.
    if ($is_valid === TRUE) {
      $this->testFormState->expects($this->never())
        ->method('setError');
    }
    else {
      $this->testFormState->expects($this->once())
        ->method('setError')
        ->will($this->returnSelf());
    }

    $element = [
      '#title' => 'foo',
      '#value' => $value,
    ];

    // Create a focal point image widget and test the validate method.
    $focalPointImageWidget = new FocalPointImageWidget([], [], $this->prophesize(FieldDefinitionInterface::class)->reveal(), [], [], $this->prophesize(ElementInfoManagerInterface::class)->reveal());
    $focalPointImageWidget::validateFocalPoint($element, $this->testFormState);
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

}
