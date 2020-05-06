<?php

namespace Drupal\Tests\webform\Unit\Utility;

use Drupal\webform\Utility\WebformReflectionHelper;
use Drupal\Tests\UnitTestCase;

/**
 * Tests webform reflection utility.
 *
 * @group webform
 *
 * @coversDefaultClass \Drupal\webform\Utility\WebformReflectionHelper
 */
class WebformReflectionHelperTest extends UnitTestCase {

  /**
   * Tests WebformReflectionHelper get parent classes with WebformReflectionHelper::getParentClasses().
   *
   * @param object $object
   *   An object.
   * @param string $base_class_name
   *   (optional) Base class name to use as the root of object's class
   *   hierarchy.
   * @param string $expected
   *   The expected result from calling the function.
   *
   * @see WebformReflectionHelper::getParentClasses()
   *
   * @dataProvider providerGetParentClasses
   */
  public function testGetParentClasses($object, $base_class_name, $expected) {
    $result = WebformReflectionHelper::getParentClasses($object, $base_class_name);
    $this->assertEquals($expected, $result);
  }

  /**
   * Data provider for testGetParentClasses().
   *
   * @see testGetParentClasses()
   */
  public function providerGetParentClasses() {
    $tests[] = [new WebformReflectionTestParent(), '', ['WebformReflectionTestParent']];
    $tests[] = [new WebformReflectionTestChild(), '', ['WebformReflectionTestParent', 'WebformReflectionTestChild']];
    $tests[] = [new WebformReflectionTestGrandChild(), '', ['WebformReflectionTestParent', 'WebformReflectionTestChild', 'WebformReflectionTestGrandChild']];

    $tests[] = [new WebformReflectionTestGrandChild(), 'WebformReflectionTestParent', ['WebformReflectionTestParent', 'WebformReflectionTestChild', 'WebformReflectionTestGrandChild']];
    $tests[] = [new WebformReflectionTestGrandChild(), 'WebformReflectionTestChild', ['WebformReflectionTestChild', 'WebformReflectionTestGrandChild']];
    $tests[] = [new WebformReflectionTestGrandChild(), 'WebformReflectionTestGrandChild', ['WebformReflectionTestGrandChild']];
    return $tests;
  }

}

/**
 * Reflection test parent.
 */
class WebformReflectionTestParent {}

/**
 * Reflection test child.
 */
class WebformReflectionTestChild extends WebformReflectionTestParent {}

/**
 * Reflection test grandchild.
 */
class WebformReflectionTestGrandChild extends WebformReflectionTestChild {}
