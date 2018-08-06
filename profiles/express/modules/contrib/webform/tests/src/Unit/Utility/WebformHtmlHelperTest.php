<?php

namespace Drupal\Tests\webform\Unit\Utility;

use Drupal\webform\Utility\WebformHtmlHelper;
use Drupal\Tests\UnitTestCase;

/**
 * Tests webform HTML helper.
 *
 * @group webform
 *
 * @coversDefaultClass \Drupal\webform\Utility\WebformHtmlHelper
 */
class WebformHtmlHelperTest extends UnitTestCase {


  /**
   * Tests WebformHtmlHelper has block tags with WebformHtmlHelper::containsHtml().
   *
   * @param string $text
   *   Text to run through WebformHtmlHelper::containsHtml().
   * @param string $expected
   *   The expected result from calling the function.
   *
   * @see \Drupal\webform\Utility\WebformHtmlHelper::containsHtml()
   *
   * @dataProvider providerContainsHtml
   */
  public function testContainsHtml($text, $expected) {
    $result = WebformHtmlHelper::containsHtml($text);
    $this->assertEquals($expected, $result, $text);
  }

  /**
   * Data provider for testContainsHtml().
   *
   * @see testContainsHtml()
   */
  public function providerContainsHtml() {
    $tests = [];
    $tests[] = ['some text', FALSE];
    $tests[] = ['<b>some text</b>', TRUE];
    return $tests;
  }
  
  /**
   * Tests WebformHtmlHelper has block tags with WebformHtmlHelper::hasBlockTags().
   *
   * @param string $text
   *   Text to run through WebformHtmlHelper::hasBlockTags().
   * @param string $expected
   *   The expected result from calling the function.
   *
   * @see \Drupal\webform\Utility\WebformHtmlHelper::hasBlockTags()
   *
   * @dataProvider providerHasBlockTags
   */
  public function testHasBlockTags($text, $expected) {
    $result = WebformHtmlHelper::hasBlockTags($text);
    $this->assertEquals($expected, $result, $text);
  }

  /**
   * Data provider for testHasBlockTags().
   *
   * @see testHasBlockTags()
   */
  public function providerHasBlockTags() {
    $tests = [];
    $tests[] = ['some text', FALSE];
    $tests[] = ['<b>some text</b>', FALSE];
    $tests[] = ['<p>some text</p>', TRUE];
    $tests[] = ['some text<br />', TRUE];
    return $tests;
  }

}
