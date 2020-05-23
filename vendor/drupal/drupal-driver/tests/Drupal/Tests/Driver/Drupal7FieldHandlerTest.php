<?php

namespace Drupal\Tests\Driver;

/**
 * Tests the Drupal 7 field handlers.
 */
class Drupal7FieldHandlerTest extends FieldHandlerAbstractTest {

  /**
   * Tests the field handlers.
   *
   * @param string $class_name
   *   The name of the field handler class under test.
   * @param object $entity
   *   An object representing an entity. Should contain a single property which
   *   represents a field containing a value.
   * @param string $entity_type
   *   The entity type under test.
   * @param array $field
   *   An associative array with the following keys:
   *   - 'field_name': the field name that is used for the property on $entity.
   *   - 'columns': an optional array containing the column names of the field
   *     as keys.
   * @param array $expected_values
   *   The values in the expected format after expansion.
   *
   * @dataProvider dataProvider
   */
  public function testFieldHandlers($class_name, $entity, $entity_type, array $field, array $expected_values) {
    $handler = $this->getMockHandler($class_name, $entity, $entity_type, $field);

    $field_name = $field['field_name'];
    $expanded_values = $handler->expand($this->values($entity->$field_name));
    $this->assertArraySubset($expected_values, $expanded_values);
  }

  /**
   * Data provider.
   *
   * @return array
   *   An array of test data.
   */
  public function dataProvider() {
    return [
      // Test default text field provided as simple text.
      [
        'DefaultHandler',
        (object) ['field_text' => 'Text'],
        'node',
        ['field_name' => 'field_text'],
        ['en' => [['value' => 'Text']]],
      ],

      // Test default text field provided as array.
      [
        'DefaultHandler',
        (object) ['field_text' => ['Text']],
        'node',
        ['field_name' => 'field_text'],
        ['en' => [['value' => 'Text']]],
      ],

      // Test default field handler using custom field columns.
      [
        'DefaultHandler',
        (object) [
          'field_addressfield' => [
            [
              'country' => 'BE',
              'locality' => 'Brussels',
              'thoroughfare' => 'Grote Markt 1',
              'postal_code' => '1000',
            ],
          ],
        ],
        'node',
        ['field_name' => 'field_addressfield'],
        [
          'en' => [
            [
              'country' => 'BE',
              'locality' => 'Brussels',
              'thoroughfare' => 'Grote Markt 1',
              'postal_code' => '1000',
            ],
          ],
        ],
      ],

      // Test single-value date field provided as simple text.
      [
        'DatetimeHandler',
        (object) ['field_date' => '2015-01-01 00:00:00'],
        'node',
        ['field_name' => 'field_date'],
        ['en' => [['value' => '2015-01-01 00:00:00']]],
      ],

      // Test single-value date field provided as an array.
      [
        'DatetimeHandler',
        (object) ['field_date' => ['2015-01-01 00:00:00']],
        'node',
        ['field_name' => 'field_date'],
        ['en' => [['value' => '2015-01-01 00:00:00']]],
      ],

      // Test double-value date field. Can only be provided as an array
      // due to array type casting we perform in
      // \Drupal\Driver\Fields\Drupal7\AbstractFieldHandler::__call()
      [
        'DatetimeHandler',
        (object) [
          'field_date' => [
            [
              '2015-01-01 00:00:00',
              '2015-01-02 00:00:00',
            ],
          ],
        ],
        'node',
        [
          'field_name' => 'field_date',
          'columns' => ['value' => '', 'value2' => ''],
        ],
        [
          'en' => [
            [
              'value' => '2015-01-01 00:00:00',
              'value2' => '2015-01-02 00:00:00',
            ],
          ],
        ],
      ],

      // Test list boolean field with blank 'On' and 'Off' values.
      [
        'ListBooleanHandler',
        (object) ['field_list_boolean' => [0]],
        'node',
        [
          'field_name' => 'field_list_boolean',
          'settings' => [
            'allowed_values' => [
              0 => '',
              1 => '',
            ],
          ],
        ],
        [
          'en' => [
            [
              'value' => 0,
            ],
          ],
        ],
      ],
    ];
  }

}
