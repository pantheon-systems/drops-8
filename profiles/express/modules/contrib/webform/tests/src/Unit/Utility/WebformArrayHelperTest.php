<?php

namespace Drupal\Tests\webform\Unit\Utility;

use Drupal\webform\Utility\WebformArrayHelper;
use Drupal\Tests\UnitTestCase;

/**
 * Tests webform array utility.
 *
 * @group webform
 *
 * @coversDefaultClass \Drupal\webform\Utility\WebformArrayHelper
 */
class WebformArrayHelperTest extends UnitTestCase {

  /**
   * Tests converting arrays to readable string with WebformArrayHelper::toString().
   *
   * @param array $array
   *   The array to run through WebformArrayHelper::toString().
   * @param string $conjunction
   *   The $conjunction to run through WebformArrayHelper::toString().
   * @param string $expected
   *   The expected result from calling the function.
   *
   * @see WebformArrayHelper::toString()
   *
   * @dataProvider providerToString
   */
  public function testToString(array $array, $conjunction, $expected) {
    $result = WebformArrayHelper::toString($array, $conjunction);
    $this->assertEquals($expected, $result);
  }

  /**
   * Data provider for testToString().
   *
   * @see testToString()
   */
  public function providerToString() {
    $tests[] = [['Jack', 'Jill'], 'and', 'Jack and Jill'];
    $tests[] = [['Jack', 'Jill'], 'or', 'Jack or Jill'];
    $tests[] = [['Jack', 'Jill', 'Bill'], 'and', 'Jack, Jill, and Bill'];
    $tests[] = [[''], 'and', '', 'WebformArrayHelper::toString with no one'];
    $tests[] = [['Jack'], 'and', 'Jack'];
    return $tests;
  }

  /**
   * Tests determining type of array with WebformArrayHelper::IsAssociative().
   *
   * @param array $array
   *   The array to run through WebformArrayHelper::IsAssociative().
   * @param string $expected
   *   The expected result from calling the function.
   *
   * @see WebformArrayHelper::IsAssociative()
   *
   * @dataProvider providerIsAssociative
   */
  public function testIsAssociative(array $array, $expected) {
    $result = WebformArrayHelper::IsAssociative($array);
    $this->assertEquals($expected, $result);
  }

  /**
   * Data provider for testIsAssociative().
   *
   * @see testIsAssociative()
   */
  public function providerIsAssociative() {
    $tests[] = [['Jack'], FALSE];
    $tests[] = [[0 => 'Jack', 1 => 'Jill'], FALSE];
    $tests[] = [[0 => 'Jack', 2 => 'Jill'], TRUE];
    $tests[] = [['name' => 'Jack'], TRUE];
    $tests[] = [['Jack', 'name' => 'Jill'], TRUE];
    $tests[] = [['name' => 'Jack'], TRUE];
    $tests[] = [['name' => 'Jack', 'Jill'], TRUE];
    return $tests;
  }

  /**
   * Tests determining type of array with WebformArrayHelper::InArray().
   *
   * @param array $needles
   *   The searched values.
   * @param array $haystack
   *   The array.
   * @param string $expected
   *   The expected result from calling the function.
   *
   * @see WebformArrayHelper::InArray()
   *
   * @dataProvider providerInArray
   */
  public function testInArray(array $needles, array $haystack, $expected) {
    $result = WebformArrayHelper::InArray($needles, $haystack);
    $this->assertEquals($expected, $result);
  }

  /**
   * Data provider for testInArray().
   *
   * @see testInArray()
   */
  public function providerInArray() {
    $tests[] = [[], ['A', 'B', 'C'], FALSE];
    $tests[] = [['A'], ['A', 'B', 'C'], TRUE];
    $tests[] = [['A', 'B'], ['A', 'B', 'C'], TRUE];
    $tests[] = [['D'], ['A', 'B', 'C'], FALSE];
    $tests[] = [[1], [1, 2, 3], TRUE];
    $tests[] = [[4], [1, 2, 3], FALSE];
    return $tests;
  }

  /**
   * Tests navigating an associative array's keys.
   *
   * @see WebformArrayHelper::getFirstKey()
   * @see WebformArrayHelper::getLastKey()
   * @see WebformArrayHelper::getPreviousKey()
   * @see WebformArrayHelper::getNextKey()
   */
  public function testGetKey() {
    $array = [
      'one' => 'One',
      'two' => 'Two',
      'three' => 'Three',
      'four' => 'Four',
      'five' => 'Five',
    ];

    $this->assertEquals(WebformArrayHelper::getFirstKey($array), 'one');
    $this->assertEquals(WebformArrayHelper::getFirstKey([]), NULL);

    $this->assertEquals(WebformArrayHelper::getLastKey($array), 'five');
    $this->assertEquals(WebformArrayHelper::getLastKey([]), NULL);

    $this->assertEquals(WebformArrayHelper::getNextKey($array, 'one'), 'two');
    $this->assertEquals(WebformArrayHelper::getNextKey($array, 'five'), NULL);
    $this->assertEquals(WebformArrayHelper::getNextKey($array, 'six'), NULL);

    $this->assertEquals(WebformArrayHelper::getPreviousKey($array, 'five'), 'four');
    $this->assertEquals(WebformArrayHelper::getPreviousKey($array, 'one'), NULL);
    $this->assertEquals(WebformArrayHelper::getNextKey($array, 'six'), NULL);
  }

  /**
   * Tests prefix an associative array.
   *
   * @see WebformArrayHelper::addPrefix()
   * @see WebformArrayHelper::removePrefix()
   */
  public function testPrefixing() {
    $this->assertEquals(WebformArrayHelper::addPrefix(['test' => 'test']), ['#test' => 'test']);
    $this->assertEquals(WebformArrayHelper::addPrefix(['test' => 'test'], '@'), ['@test' => 'test']);
    $this->assertEquals(WebformArrayHelper::removePrefix(['#test' => 'test']), ['test' => 'test']);
    $this->assertEquals(WebformArrayHelper::removePrefix(['@test' => 'test'], '@'), ['test' => 'test']);
  }

}
