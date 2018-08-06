<?php

namespace Drupal\Tests\webform\Unit\Utility;

use Drupal\webform\Utility\WebformOptionsHelper;
use Drupal\Tests\UnitTestCase;

/**
 * Tests webform options utility.
 *
 * @group webform
 *
 * @coversDefaultClass \Drupal\webform\Utility\WebformOptionsHelper
 */
class WebformOptionsHelperTest extends UnitTestCase {

  /**
   * Tests WebformOptionsHelper::hasOption().
   *
   * @param string $value
   *   The value to run through WebformOptionsHelper::hasOption().
   * @param array $options
   *   The array to run through WebformOptionsHelper::hasOption().
   * @param string $expected
   *   The expected result from calling the function.
   *
   * @see WebformOptionsHelper::hasOption()
   *
   * @dataProvider providerHasOption
   */
  public function testHasOption($value, array $options, $expected) {
    $result = WebformOptionsHelper::hasOption($value, $options);
    $this->assertEquals($expected, $result);
  }

  /**
   * Data provider for testHasOption().
   *
   * @see testHasOption()
   */
  public function providerHasOption() {
    $tests[] = ['value', ['value' => 'text'], TRUE];
    $tests[] = ['value', [], FALSE];
    $tests[] = [3, [1 => 'One', 2 => 'Two', 'optgroup' => [3 => 'Three']], TRUE];
    $tests[] = ['optgroup', [1 => 'One', 2 => 'Two', 'optgroup' => [3 => 'Three']], FALSE];
    return $tests;
  }

  /**
   * Tests WebformOptionsHelper::getOptionsText().
   *
   * @param array $values
   *   The array to run through WebformOptionsHelper::getOptionsText().
   * @param array $options
   *   The array to run through WebformOptionsHelper::getOptionsText().
   * @param string $expected
   *   The expected result from calling the function.
   *
   * @see WebformOptionsHelper::getOptionsText()
   *
   * @dataProvider providerGetOptionsText
   */
  public function testGetOptionsText(array $values, array $options, $expected) {
    $result = WebformOptionsHelper::getOptionsText($values, $options);
    $this->assertEquals($expected, $result);
  }

  /**
   * Data provider for testGetOptionsText().
   *
   * @see testGetOptionsText()
   */
  public function providerGetOptionsText() {
    $tests[] = [['value'], ['value' => 'text'], ['text']];
    $tests[] = [[1, 3], [1 => 'One', 2 => 'Two', 'optgroup' => [3 => 'Three']], ['One', 'Three']];
    return $tests;
  }

  /**
   * Tests WebformOptionsHelper::convertOptionsToString().
   *
   * @param array $options
   *   The array to run through WebformOptionsHelper::range().
   * @param string $expected
   *   The expected result from calling the function.
   *
   * @see WebformOptionsHelper::convertOptionsToString()
   *
   * @dataProvider providerConvertOptionsToString
   */
  public function testConvertOptionsToString(array $options, $expected) {
    $result = WebformOptionsHelper::convertOptionsToString($options);
    $this->assertEquals($expected, $result);
  }

  /**
   * Data provider for testConvertOptionsToString().
   *
   * @see testConvertOptionsToString()
   */
  public function providerConvertOptionsToString() {
    $tests[] = [[99 => 99], ['99' => 99]];
    $tests[] = [[99.11 => 99], ['99' => 99]];
    $tests[] = [[TRUE => 99], ['1' => 99]];
    return $tests;
  }

  /**
   * Tests WebformOptionsHelper::range().
   *
   * @param array $element
   *   The array to run through WebformOptionsHelper::range().
   * @param string $expected
   *   The expected result from calling the function.
   *
   * @see WebformOptionsHelper::range()
   *
   * @dataProvider providerRange
   */
  public function testRange(array $element, $expected) {
    $element += [
      '#min' => 1,
      '#max' => 100,
      '#step' => 1,
      '#pad_length' => NULL,
      '#pad_str' => 0,
    ];

    $result = WebformOptionsHelper::range(
      $element['#min'],
      $element['#max'],
      $element['#step'],
      $element['#pad_length'],
      $element['#pad_str']
    );
    $this->assertEquals($expected, $result);
  }

  /**
   * Data provider for testRange().
   *
   * @see testRange()
   */
  public function providerRange() {
    $tests[] = [['#min' => 1, '#max' => 3], [1 => 1, 2 => 2, 3 => 3]];
    $tests[] = [['#min' => 0, '#max' => 6, '#step' => 2], [0 => 0, 2 => 2, 4 => 4, 6 => 6]];
    $tests[] = [['#min' => 'A', '#max' => 'C'], ['A' => 'A', 'B' => 'B', 'C' => 'C']];
    $tests[] = [['#min' => 'a', '#max' => 'c'], ['a' => 'a', 'b' => 'b', 'c' => 'c']];
    $tests[] = [['#min' => 1, '#max' => 3, '#step' => 1, '#pad_length' => 2, '#pad_str' => 0], ['01' => '01', '02' => '02', '03' => '03']];
    return $tests;
  }

  /**
   * Tests WebformOptionsHelper::encodeConfig().
   *
   * @param array $options
   *   The options array to run through WebformOptionsHelper::encodeConfig().
   * @param string $expected
   *   The expected result from calling the function.
   *
   * @see WebformOptionsHelper::encodeConfig()
   *
   * @dataProvider providerEncodeConfig
   */
  public function testEncodeConfig(array $options, $expected) {
    $result = WebformOptionsHelper::encodeConfig($options);
    $this->assertEquals($expected, $result);
  }

  /**
   * Data provider for testEncodeConfig().
   *
   * @see testEncodeConfig()
   */
  public function providerEncodeConfig() {
    $tests[] = [
      ['one' => 1, 'two' => 2, 'with.period' => 'with period'],
      [
        ['value' => 'one', 'text' => 1],
        ['value' => 'two', 'text' => 2],
        ['value' => 'with.period', 'text' => 'with period'],
      ],
    ];
    return $tests;
  }

  /**
   * Tests WebformOptionsHelper::decodeConfig().
   *
   * @param array $options
   *   The options array to run through WebformOptionsHelper::decodeConfig().
   * @param string $expected
   *   The expected result from calling the function.
   *
   * @see WebformOptionsHelper::decodeConfig()
   *
   * @dataProvider providerDecodeConfig
   */
  public function testDecodeConfig(array $options, $expected) {
    $result = WebformOptionsHelper::decodeConfig($options);
    $this->assertEquals($expected, $result);
  }

  /**
   * Data provider for testDecodeConfig().
   *
   * @see testDecodeConfig()
   */
  public function providerDecodeConfig() {
    $tests[] = [
      [
        ['value' => 'one', 'text' => 1],
        ['value' => 'two', 'text' => 2],
        ['value' => 'with.period', 'text' => 'with period'],
      ],
      ['one' => 1, 'two' => 2, 'with.period' => 'with period'],
    ];
    return $tests;
  }

}
