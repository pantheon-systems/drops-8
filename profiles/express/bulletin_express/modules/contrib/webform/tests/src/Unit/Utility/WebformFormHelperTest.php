<?php

namespace Drupal\Tests\webform\Unit\Utility;

use Drupal\webform\Utility\WebformFormHelper;
use Drupal\Tests\UnitTestCase;

/**
 * Tests webform helper utility.
 *
 * @group webform
 *
 * @coversDefaultClass \Drupal\webform\Utility\WebformFormHelper
 */
class WebformFormHelperTest extends UnitTestCase {

  /**
   * Tests WebformFormHelper::cleanupFormStateValues().
   *
   * @param array $values
   *   The array to run through WebformFormHelper::cleanupFormStateValues().
   * @param array $keys
   *   (optional) An array of custom keys to be removed.
   * @param string $expected
   *   The expected result from calling the function.
   *
   * @see WebformFormHelper::cleanupFormStateValues()
   *
   * @dataProvider providerCleanupFormStateValues
   */
  public function testCleanupFormStateValues(array $values, array $keys, $expected) {
    $result = WebformFormHelper::cleanupFormStateValues($values, $keys);
    $this->assertEquals($expected, $result);
  }

  /**
   * Data provider for testCleanupFormStateValues().
   *
   * @see testCleanupFormStateValues()
   */
  public function providerCleanupFormStateValues() {
    $tests[] = [['key' => 'value'], [], ['key' => 'value']];
    $tests[] = [['key' => 'value', 'form_token' => 'ignored'], [], ['key' => 'value']];
    $tests[] = [['key' => 'value', 'form_token' => 'ignored'], ['key'], []];
    return $tests;
  }

  /**
   * Tests WebformFormHelper::flattenElements().
   *
   * @see WebformFormHelper::flattenElements()
   */
  public function testFlattenElements() {
    $elements = [
      'one' => [
        '#title' => 'one',
        'two' => [
          '#title' => 'two',
        ],
      ],
    ];
    $flattenend_elements = WebformFormHelper::flattenElements($elements);

    // Check flattened elements.
    $this->assertEquals($flattenend_elements, [
      'one' => [
        '#title' => 'one',
        'two' => [
          '#title' => 'two',
        ],
      ],
      'two' => [
        '#title' => 'two',
      ],
    ]);

    // Check flattened elements references.
    $elements['one']['#title'] .= '-UPDATED';
    $elements['one']['two']['#title'] .= '-UPDATED';
    $elements['one']['two']['#type'] = 'textfield';

    $this->assertEquals($flattenend_elements, [
      'one' => [
        '#title' => 'one-UPDATED',
        'two' => [
          '#title' => 'two-UPDATED',
          '#type' => 'textfield',
        ],
      ],
      'two' => [
        '#title' => 'two-UPDATED',
        '#type' => 'textfield',
      ],
    ]);

    // Check flattened elements with duplicate keys.
    $elements = [
      'one' => [
        '#title' => 'one',
        'two' => [
          '#title' => 'two-FIRST',
        ],
      ],
      'two' => [
        '#title' => 'two-SECOND',
      ],
    ];
    $flattenend_elements = WebformFormHelper::flattenElements($elements);
    $this->assertEquals($flattenend_elements, [
      'one' => [
        '#title' => 'one',
        'two' => [
          '#title' => 'two-FIRST',
        ],
      ],
      'two' => [
        [
          '#title' => 'two-FIRST',
        ],
        [
          '#title' => 'two-SECOND',
        ],
      ],
    ]);

    // Check flattened elements references with duplicate keys.
    $elements['one']['#title'] .= '-UPDATED';
    $elements['one']['two']['#title'] .= '-UPDATED';
    $elements['one']['two']['#type'] = 'textfield';

    $this->assertEquals($flattenend_elements, [
      'one' => [
        '#title' => 'one-UPDATED',
        'two' => [
          '#title' => 'two-FIRST-UPDATED',
          '#type' => 'textfield',
        ],
      ],
      'two' => [
        [
          '#title' => 'two-FIRST-UPDATED',
          '#type' => 'textfield',
        ],
        [
          '#title' => 'two-SECOND',
        ],
      ],
    ]);
  }

}
