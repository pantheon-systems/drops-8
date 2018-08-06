<?php

namespace Drupal\Tests\webform\Unit\Utility;

use Drupal\Core\Serialization\Yaml;
use Drupal\webform\Utility\WebformYaml;
use Drupal\Tests\UnitTestCase;

/**
 * Tests webform tidy utility.
 *
 * @group webform
 *
 * @coversDefaultClass \Drupal\webform\Utility\WebformYaml
 */
class WebformYamlTest extends UnitTestCase {

  /**
   * Tests WebformYaml tidy with WebformYaml::tidy().
   *
   * @param array $data
   *   The array to run through WebformYaml::tidy().
   * @param string $expected
   *   The expected result from calling the function.
   *
   * @see WebformYaml::tidy()
   *
   * @dataProvider providerTidy
   */
  public function testTidy(array $data, $expected) {
    $result = WebformYaml::tidy(Yaml::encode($data));
    $this->assertEquals($expected, $result);
  }

  /**
   * Data provider for testTidy().
   *
   * @see testTidy()
   */
  public function providerTidy() {
    $tests[] = [
      ['simple' => 'value'],
      "simple: value",
    ];
    $tests[] = [
      ['returns' => "line 1\nline 2"],
      "returns: |\n  line 1\n  line 2",
    ];
    $tests[] = [
      ['one two' => "line 1\nline 2"],
      "'one two': |\n  line 1\n  line 2",
    ];
    $tests[] = [
      ['array' => ['one', 'two']],
      "array:\n  - one\n  - two",
    ];
    $tests[] = [
      [['one' => 'One'], ['two' => 'Two']],
      "- one: One\n- two: Two",
    ];
    return $tests;
  }

}
