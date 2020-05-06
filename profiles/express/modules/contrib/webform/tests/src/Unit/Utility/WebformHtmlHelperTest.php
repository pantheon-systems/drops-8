<?php

namespace Drupal\Tests\webform\Unit\Utility;

use Drupal\Core\DependencyInjection\ContainerBuilder;
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
   * Tests WebformHtmlHelper::containsToPlainText().
   *
   * @param string $text
   *   Text to run through WebformHtmlHelper::toPlainText().
   * @param string $expected
   *   The expected result from calling the function.
   *
   * @see \Drupal\webform\Utility\WebformHtmlHelper::toPlainText()
   *
   * @dataProvider providerToPlainText
   */
  public function testToPlainText($text, $expected) {
    $config_factory = $this->getConfigFactoryStub([
      'webform.settings' => ['element' => ['allowed_tags' => 'b']],
    ]);
    $container = new ContainerBuilder();
    $container->set('config.factory', $config_factory);
    \Drupal::setContainer($container);

    /**************************************************************************/

    $result = WebformHtmlHelper::toPlainText($text);
    $this->assertEquals((string) $expected, (string) $result, $text);
  }

  /**
   * Data provider for testToPlainText().
   *
   * @see testToPlainText()
   */
  public function providerToPlainText() {
    $tests = [];
    $tests[] = ['some text', 'some text'];
    $tests[] = ['some &amp; text', 'some & text'];
    $tests[] = ['<b>some text</b>', 'some text'];
    $tests[] = ['<script>alert(\'message\');</script><b>some text</b>', 'alert(\'message\');some text'];
    return $tests;
  }

  /**
   * Tests WebformHtmlHelper::containsToHtmlMarkup().
   *
   * @param string $text
   *   Text to run through WebformHtmlHelper::toHtmlMarkup().
   * @param string $expected
   *   The expected result from calling the function.
   *
   * @see \Drupal\webform\Utility\WebformHtmlHelper::toHtmlMarkup()
   *
   * @dataProvider providerToHtmlMarkup
   */
  public function testToHtmlMarkup($text, $expected) {
    $config_factory = $this->getConfigFactoryStub([
      'webform.settings' => ['element' => ['allowed_tags' => 'b']],
    ]);
    $container = new ContainerBuilder();
    $container->set('config.factory', $config_factory);
    \Drupal::setContainer($container);

    /**************************************************************************/

    $result = WebformHtmlHelper::toHtmlMarkup($text);
    $this->assertEquals((string) $expected, (string) $result, $text);
  }

  /**
   * Data provider for testToHtmlMarkup().
   *
   * @see testToHtmlMarkup()
   */
  public function providerToHtmlMarkup() {
    $tests = [];
    $tests[] = ['some text', 'some text'];
    $tests[] = ['some & text', 'some & text'];
    $tests[] = ['<b>some text</b>', '<b>some text</b>'];
    $tests[] = ['<script>alert(\'message\');</script><b>some text</b>', 'alert(\'message\');<b>some text</b>'];
    return $tests;
  }

  /**
   * Tests WebformHtmlHelper::containsHtml().
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
   * Tests WebformHtmlHelper::hasBlockTags().
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
