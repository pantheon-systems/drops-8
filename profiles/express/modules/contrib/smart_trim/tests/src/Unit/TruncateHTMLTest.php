<?php

/**
 * @file
 * Contains Drupal\smart_trim|Tests\TruncateHTMLTest.
 */

namespace Drupal\smart_trim\Tests;

use Drupal\smart_trim\Truncate\TruncateHTML;
use Drupal\Tests\UnitTestCase;

/**
 * Unit Test coverage.
 *
 * @coversDefaultClass \Drupal\smart_trim\Truncate\TruncateHTML
 *
 * @group smart_trim
 */
class TruncateHTMLTest extends UnitTestCase {

  /**
   * Testing truncateChars.
   *
   * @covers ::truncateChars
   *
   * @dataProvider truncateCharsDataProvider
   */
  public function testTruncateChars($html, $limit, $ellipsis, $expected) {
    $truncate = new TruncateHTML();
    $this->assertSame($expected, $truncate->truncateChars($html, $limit, $ellipsis));
  }

  /**
   * Data provider for testTruncateChars().
   */
  public function truncateCharsDataProvider() {
    return [
      [
        'A test string',
        5,
        '…',
        'A tes…',
      ],
      [
        '“I like funky quotes”',
        5,
        '',
        '“I li',
      ],
      [
        '“I <em>really, really</em> like funky quotes”',
        14,
        '',
        '“I <em>really, rea</em>',
      ],
    ];
  }

  /**
   * Covers TruncateWords.
   *
   * @covers ::truncateWords
   *
   * @dataProvider truncateWordsDataProvider
   */
  public function testTruncateWords($html, $limit, $ellipsis, $expected) {
    $truncate = new TruncateHTML();
    $this->assertSame($expected, $truncate->truncateWords($html, $limit, $ellipsis));
  }

  /**
   * Data provider for testTruncateWords().
   */
  public function truncateWordsDataProvider() {
    return [
      [
        'A test string',
        2,
        '…',
        'A test…',
      ],
      [
        'A test string',
        3,
        '…',
        'A test string',
      ],
      [
        '“I like funky quotes”',
        2,
        '',
        '“I like',
      ],
      [
        '“I like funky quotes”',
        4,
        '',
        '“I like funky quotes”',
      ],
      [
        '“I <em>really, really</em> like funky quotes”',
        2,
        '',
        '“I <em>really,</em>',
      ],
    ];
  }

}
