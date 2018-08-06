<?php

namespace Drupal\Tests\webform\Unit\Utility;

use Drupal\webform\Utility\WebformDateHelper;
use Drupal\Tests\UnitTestCase;

/**
 * Tests webform date helper utility.
 *
 * @group webform
 *
 * @coversDefaultClass \Drupal\webform\Utility\WebformDateHelper
 */
class WebformDateHelperTest extends UnitTestCase {

  /**
   * Tests WebformDateHelper::isValidDateFormat().
   *
   * @param string $time
   *   The date/time string to run through WebformDateHelper::isValidDateFormat().
   * @param string $format
   *   Format accepted by date().
   * @param string $expected
   *   The expected result from calling the function.
   *
   * @see WebformDateHelper::isValidDateFormat()
   *
   * @dataProvider providerisValidDateFormat
   */
  public function testisValidDateFormat($time, $format, $expected) {
    $result = WebformDateHelper::isValidDateFormat($time, $format);
    $this->assertEquals($expected, $result);
  }

  /**
   * Data provider for testisValidDateFormat().
   *
   * @see testisValidDateFormat()
   */
  public function providerisValidDateFormat() {
    $tests[] = ['2013-13-01', 'Y-m-d', FALSE];
    $tests[] = ['2013-13-01', 'Y-m-d', FALSE];
    $tests[] = ['20132-13-01', 'Y-m-d', FALSE];
    $tests[] = ['2013-11-32', 'Y-m-d', FALSE];
    $tests[] = ['2012-2-25', 'Y-m-d', FALSE];
    $tests[] = ['2013-12-01', 'Y-m-d', TRUE];
    $tests[] = ['1970-12-01', 'Y-m-d', TRUE];
    $tests[] = ['2012-02-29', 'Y-m-d', TRUE];
    $tests[] = ['2013-13-01', 'Y-m-d', FALSE];
    return $tests;
  }

}
