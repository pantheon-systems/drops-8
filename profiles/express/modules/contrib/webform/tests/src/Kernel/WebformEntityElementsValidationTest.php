<?php

namespace Drupal\Tests\webform\Kernel;

use Drupal\Core\Url;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests webform entity elements validation.
 *
 * @group webform
 */
class WebformEntityElementsValidationTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'webform', 'user'];

  /**
   * The webform elements validator.
   *
   * @var \Drupal\webform\WebformEntityElementsValidatorInterface
   */
  protected $validator;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->validator = \Drupal::service('webform.elements_validator');
  }

  /**
   * Tests validating elements.
   */
  public function testValidate() {
    $tests = [
      /*
      [
        'getElementsRaw' => '', // Elements.
        'getElementsOriginalRaw' => '', // Original elements.
        'messages' => [], // Validation error message.
      ],
      */

      // Check required.
      [
        'getElementsRaw' => '',
        'getElementsOriginalRaw' => '',
        'messages' => [
          'Elements are required',
        ],
      ],

      // Check elements are an array.
      [
        'getElementsRaw' => 'string',
        'messages' => [
          'Elements are not valid. YAML must contain an associative array of elements.',
        ],
      ],

      // Check names.
      [
        'getElementsRaw' => "Not Valid:
  '#type': textfield",
        'messages' => [
          'The element key <em class="placeholder">Not Valid</em> on line 1 must contain only lowercase letters, numbers, and underscores.',
        ],
      ],

      // Check duplicate names.
      [
        'getElementsRaw' => "name:
  '#type': textfield
duplicate:
  name:
    '#type': textfield",
        'messages' => [
          'Elements contain a duplicate element key <em class="placeholder">name</em> found on lines 1 and 4.',
        ],
      ],

      // Check duplicate name with single and double quotes.
      [
        'getElementsRaw' => "name :
  '#type': textfield
duplicate:
  name:
    '#type': textfield",
        'messages' => [
          'Elements contain a duplicate element key <em class="placeholder">name</em> found on lines 1 and 4.',
        ],
      ],

      // Check reserved names.
      [
        'getElementsRaw' => "name:
  '#type': textfield
duplicate:
  add:
    '#type': textfield",
        'messages' => [
          'The element key <em class="placeholder">add</em> on line 4 is a reserved key.',
        ],
      ],

      // Check ignored properties.
      [
        'getElementsRaw' => "'tree':
  '#tree': true
  '#submit' : 'function_name'",
        'messages' => [
          'Elements contain an unsupported <em class="placeholder">#tree</em> property found on line 2.',
          'Elements contain an unsupported <em class="placeholder">#submit</em> property found on line 3.',
        ],
      ],

      // Check validate submissions.
      [
        'getElementsRaw' => "name_changed:
  '#type': 'textfield'",
        'getElementsOriginalRaw' => "name:
  '#type': 'textfield'",
        'messages' => [
          'The <em class="placeholder">name</em> element can not be removed because the <em class="placeholder">Test</em> webform has <a href="http://example.com">results</a>.<ul><li><a href="http://example.com">Delete all submissions</a> to this webform.</li><li><a href="/admin/modules">Enable the Webform UI module</a> and safely delete this element.</li><li>Hide this element by setting its <code>\'#access\'</code> property to <code>false</code>.</li></ul>',
        ],
      ],

      // Check validate hierarchy.
      [
        'getElementsRaw' => 'empty: empty',
        'getElementsOriginalRaw' => 'empty: empty',
        'getElementsInitializedAndFlattened' => [
          'leaf_parent' => [
            '#type' => 'textfield',
            '#webform_key' => 'leaf_parent',
            '#webform_children' => TRUE,
          ],
          'root' => [
            '#type' => 'webform_wizard_page',
            '#webform_key' => 'root',
            '#webform_parent_key' => TRUE,
          ],
        ],
        'messages' => [
          'The <em class="placeholder">leaf_parent</em> (textfield) is a webform element that can not have any child elements.',
          'The <em class="placeholder">root</em> (webform_wizard_page) is a root element that can not be used as child to another element',
        ],
      ],

      // Check validate table hierarchy.
      [
        'getElementsRaw' => 'empty: empty',
        'getElementsOriginalRaw' => 'empty: empty',
        'getElementsInitializedAndFlattened' => [
          'table' => [
            '#type' => 'webform_table',
            '#webform_key' => 'table',
          ],
          'table_row' => [
            '#type' => 'webform_table_row',
            '#webform_key' => 'table_row',
            '#webform_parent_key' => 'table',
          ],
          'table_row_invalid' => [
            '#type' => 'webform_table_row',
            '#webform_key' => 'table_row',
            '#webform_parent_key' => NULL,
          ],
        ],
        'messages' => [
          'The <em class="placeholder">table_row_invalid</em> (webform_table_row) must be with in a <em class="placeholder">Table</em> (webform_table) element.',
        ],
      ],

/*
      // Check validate rendering.
      [
        'getElementsRaw' => "machine_name:
  '#type': 'machine_name'
  '#machine_name':
     source:
      broken",
        'getElementsOriginalRaw' => "machine_name:
  '#type': 'machine_name'
  '#machine_name':
     source:
      broken",
        'messages' => [
          'Unable to render elements, please view the below message and the error log.<ul><li>Query condition &#039;webform_submission.webform_id IN ()&#039; cannot be empty.</li></ul>',
        ],
      ],
*/
    ];

    // Check invalid YAML.
    // Test is customized depending on if the PECL YAML component is installed.
    // @see https://www.drupal.org/node/1920902#comment-11418117
    if (function_exists('yaml_parse')) {
      $test[] = [
        'getElementsRaw' => "not\nvalid\nyaml",
        'messages' => [
          'Elements are not valid. YAML must contain an associative array of elements.',
        ],
      ];
      $test[] = [
        'getElementsRaw' => "not:\nvalid\nyaml",
        'messages' => [
          'Elements are not valid. yaml_parse(): scanning error encountered during parsing: could not find expected &#039;:&#039; (line 3, column 1), context while scanning a simple key (line 2, column 1)',
        ],
      ];
    }
    else {
      $test[] = [
        'getElementsRaw' => "not\nvalid\nyaml",
        'messages' => [
          'Elements are not valid. Unable to parse at line 1 (near &quot;not&quot;).',
        ],
      ];
    }

    foreach ($tests as $test) {
      $test += [
        'getElementsRaw' => '',
        'getElementsOriginalRaw' => '',
        'getElementsInitializedAndFlattened' => [],
        'hasSubmissions' => TRUE,
        'label' => 'Test',
        'toUrl' => Url::fromUri('http://example.com'),
        'messages' => [],
      ];

      /** @var \Drupal\webform\WebformInterface $webform */
      $webform = $this->createMock('\Drupal\webform\WebformInterface');
      $methods = $test;
      unset($methods['messages']);
      foreach ($methods as $method => $returnValue) {
        $webform->expects($this->any())
          ->method($method)
          ->will($this->returnValue($returnValue));
      }

      $messages = $this->validator->validate($webform);
      foreach ($messages as $index => $message) {
        $messages[$index] = (string) $message;
      }
      $this->assertEquals($messages, $test['messages']);
    }
  }

}
