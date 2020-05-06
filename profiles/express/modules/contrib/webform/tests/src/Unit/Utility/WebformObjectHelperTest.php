<?php

namespace Drupal\Tests\webform\Unit\Utility;

use Drupal\webform\Utility\WebformObjectHelper;
use Drupal\Tests\UnitTestCase;

/**
 * Tests webform object utility.
 *
 * @group webform
 *
 * @coversDefaultClass \Drupal\webform\Utility\WebformObjectHelper
 */
class WebformObjectHelperTest extends UnitTestCase {

  /**
   * Tests sorting object by properties.
   *
   * @param object $object
   *   The object to run through WebformObjectHelper::sortByProperty().
   * @param array $expected
   *   The expected result from calling the function.
   *
   * @see WebformObjectHelper::sortByProperty()
   *
   * @dataProvider providerSortByProperty
   */
  public function testSortByProperty($object, array $expected) {
    $result = (array) WebformObjectHelper::sortByProperty($object);
    $this->assertEquals(
      implode('|', array_keys($expected)),
      implode('|', array_keys($result))
    );
  }

  /**
   * Data provider for testSortByProperty().
   *
   * @see testSortByProperty()
   */
  public function providerSortByProperty() {
    $object = new \stdClass();
    $object->c = 'c';
    $object->a = 'a';
    $object->b = 'b';
    $tests[] = [$object, ['a' => 'a', 'b' => 'b', 'c' => 'c']];

    $object = new \stdClass();
    $object->c = 'c';
    $object->b = 'b';
    $object->a = 'a';
    $tests[] = [$object, ['a' => 'a', 'b' => 'b', 'c' => 'c']];

    $object = new \stdClass();
    $object->b = 'b';
    $object->a = 'a';
    $object->_ = '_';
    $tests[] = [$object, ['_' => '_', 'a' => 'a', 'b' => 'b']];

    return $tests;
  }

}
