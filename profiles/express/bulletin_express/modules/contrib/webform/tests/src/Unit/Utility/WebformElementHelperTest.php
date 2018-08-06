<?php

namespace Drupal\Tests\webform\Unit\Utility;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\webform\Utility\WebformElementHelper;
use Drupal\Tests\UnitTestCase;

/**
 * Tests webform element utility.
 *
 * @group webform
 *
 * @coversDefaultClass \Drupal\webform\Utility\WebformElementHelper
 */
class WebformElementHelperTest extends UnitTestCase {

  /**
   * Tests WebformElementHelper::isTitleDisplayed().
   *
   * @param array $element
   *   The element to run through WebformElementHelper::IsTitleDisplayed().
   * @param string $expected
   *   The expected result from calling the function.
   *
   * @see WebformElementHelper::isTitleDisplayed()
   *
   * @dataProvider providerIsTitleDisplayed
   */
  public function testIsTitleDisplayed(array $element, $expected) {
    $result = WebformElementHelper::IsTitleDisplayed($element);
    $this->assertEquals($expected, $result, serialize($element));
  }

  /**
   * Data provider for testIsTitleDisplayed().
   *
   * @see testIsTitleDisplayed()
   */
  public function providerIsTitleDisplayed() {
    $tests[] = [['#title' => 'Test'], TRUE];
    $tests[] = [['#title' => 'Test', '#title_display' => 'above'], TRUE];
    $tests[] = [[], FALSE];
    $tests[] = [['#title' => ''], FALSE];
    $tests[] = [['#title' => NULL], FALSE];
    $tests[] = [['#title' => 'Test', '#title_display' => 'invisible'], FALSE];
    $tests[] = [['#title' => 'Test', '#title_display' => 'attribute'], FALSE];
    return $tests;
  }

  /**
   * Tests WebformElementHelper::GetIgnoredProperties().
   *
   * @param array $element
   *   The array to run through WebformElementHelper::GetIgnoredProperties().
   * @param string $expected
   *   The expected result from calling the function.
   *
   * @see WebformElementHelper::GetIgnoredProperties()
   *
   * @dataProvider providerGetIgnoredProperties
   */
  public function testGetIgnoredProperties(array $element, $expected) {
    $result = WebformElementHelper::getIgnoredProperties($element);
    $this->assertEquals($expected, $result);
  }

  /**
   * Data provider for testGetIgnoredProperties().
   *
   * @see testGetIgnoredProperties()
   */
  public function providerGetIgnoredProperties() {
    // Nothing ignored.
    $tests[] = [
      ['#value' => 'text'],
      [],
    ];
    // Ignore #tree.
    $tests[] = [
      ['#tree' => TRUE],
      ['#tree' => '#tree'],
    ];
    // Ignore #tree and #element_validate.
    $tests[] = [
      ['#tree' => TRUE, '#value' => 'text', '#element_validate' => 'some_function'],
      ['#tree' => '#tree', '#element_validate' => '#element_validate'],
    ];
    // Ignore #subelement__tree and #subelement__element_validate.
    $tests[] = [
      ['#subelement__tree' => TRUE, '#value' => 'text', '#subelement__element_validate' => 'some_function'],
      ['#subelement__tree' => '#subelement__tree', '#subelement__element_validate' => '#subelement__element_validate'],
    ];
    return $tests;
  }

  /**
   * Tests WebformElementHelper::removeIgnoredProperties().
   *
   * @param array $element
   *   The array to run through WebformElementHelper::removeIgnoredProperties().
   * @param string $expected
   *   The expected result from calling the function.
   *
   * @see WebformElementHelper::removeIgnoredProperties()
   *
   * @dataProvider providerRemoveIgnoredProperties
   */
  public function testRemoveIgnoredProperties(array $element, $expected) {
    $result = WebformElementHelper::removeIgnoredProperties($element);
    $this->assertEquals($expected, $result);
  }

  /**
   * Data provider for testRemoveIgnoredProperties().
   *
   * @see testRemoveIgnoredProperties()
   */
  public function providerRemoveIgnoredProperties() {
    // Nothing removed.
    $tests[] = [
      ['#value' => 'text'],
      ['#value' => 'text'],
    ];
    // Remove #tree.
    $tests[] = [
      ['#tree' => TRUE],
      [],
    ];
    // Remove #tree and #element_validate.
    $tests[] = [
      ['#tree' => TRUE, '#value' => 'text', '#element_validate' => 'some_function'],
      ['#value' => 'text'],
    ];
    // Remove #subelement__tree and #subelement__element_validate.
    $tests[] = [
      ['#subelement__tree' => TRUE, '#value' => 'text', '#subelement__element_validate' => 'some_function'],
      ['#value' => 'text'],
    ];
    return $tests;
  }

  /**
   * Tests WebformElementHelper::convertRenderMarkupToStrings().
   *
   * @param array $elements
   *   The array to run through WebformElementHelper::convertRenderMarkupToStrings().
   * @param string $expected
   *   The expected result from calling the function.
   *
   * @see WebformElementHelper::convertRenderMarkupToStrings()
   *
   * @dataProvider providerConvertRenderMarkupToStrings
   */
  public function testConvertRenderMarkupToStrings(array $elements, $expected) {
    WebformElementHelper::convertRenderMarkupToStrings($elements);
    $this->assertEquals($expected, $elements);
  }

  /**
   * Data provider for testConvertRenderMarkupToStrings().
   *
   * @see testConvertRenderMarkupToStrings()
   */
  public function providerConvertRenderMarkupToStrings() {
    return [
      [
        ['test' => new FormattableMarkup('markup', [])],
        ['test' => 'markup'],
      ],
      [
        ['test' => ['nested' => new FormattableMarkup('markup', [])]],
        ['test' => ['nested' => 'markup']],
      ],
    ];
  }

}
