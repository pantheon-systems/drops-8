<?php

namespace Drupal\Tests\pathauto\Kernel;

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests tokens provided by Pathauto.
 *
 * @group pathauto
 */
class PathautoTokenTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system', 'token', 'pathauto');

  public function testPathautoTokens() {

    $this->installConfig(array('pathauto'));

    $array = array(
      'test first arg',
      'The Array / value',
    );

    $tokens = array(
      'join-path' => 'test-first-arg/array-value',
    );
    $data['array'] = $array;
    $replacements = $this->assertTokens('array', $data, $tokens);

    // Ensure that the cleanTokenValues() method does not alter this token value.
    /* @var \Drupal\pathauto\AliasCleanerInterface $alias_cleaner */
    $alias_cleaner = \Drupal::service('pathauto.alias_cleaner');
    $alias_cleaner->cleanTokenValues($replacements, $data, array());
    $this->assertEqual($replacements['[array:join-path]'], 'test-first-arg/array-value');
  }

  /**
   * Function copied from TokenTestHelper::assertTokens().
   */
  public function assertTokens($type, array $data, array $tokens, array $options = array()) {
    $input = $this->mapTokenNames($type, array_keys($tokens));
    $bubbleable_metadata = new BubbleableMetadata();
    $replacements = \Drupal::token()->generate($type, $input, $data, $options, $bubbleable_metadata);
    foreach ($tokens as $name => $expected) {
      $token = $input[$name];
      if (!isset($expected)) {
        $this->assertTrue(!isset($values[$token]), t("Token value for @token was not generated.", array('@type' => $type, '@token' => $token)));
      }
      elseif (!isset($replacements[$token])) {
        $this->fail(t("Token value for @token was not generated.", array('@type' => $type, '@token' => $token)));
      }
      elseif (!empty($options['regex'])) {
        $this->assertTrue(preg_match('/^' . $expected . '$/', $replacements[$token]), t("Token value for @token was '@actual', matching regular expression pattern '@expected'.", array('@type' => $type, '@token' => $token, '@actual' => $replacements[$token], '@expected' => $expected)));
      }
      else {
        $this->assertIdentical($replacements[$token], $expected, t("Token value for @token was '@actual', expected value '@expected'.", array('@type' => $type, '@token' => $token, '@actual' => $replacements[$token], '@expected' => $expected)));
      }
    }

    return $replacements;
  }

  public function mapTokenNames($type, array $tokens = array()) {
    $return = array();
    foreach ($tokens as $token) {
      $return[$token] = "[$type:$token]";
    }
    return $return;
  }

}
