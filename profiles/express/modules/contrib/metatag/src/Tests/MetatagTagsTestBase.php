<?php

namespace Drupal\metatag\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Html;

/**
 * Base class to test all of the meta tags that are in a specific module.
 */
abstract class MetatagTagsTestBase extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    // This is needed for the 'access content' permission.
    'node',

    // Dependencies.
    'token',

    // Metatag itself.
    'metatag',

    // This module will be used to load a static page which will inherit the
    // global defaults, without loading values from other configs.
    'metatag_test_custom_route',
  ];

  /**
   * All of the meta tags defined by this module which will be tested.
   */
  public $tags = [];

  /**
   * The tag to look for when testing the output.
   */
  public $test_tag = 'meta';

  /**
   * The attribute to look for to indicate which tag.
   */
  public $test_name_attribute = 'name';

  /**
   * The attribute to look for when testing the output.
   */
  public $test_value_attribute = 'content';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Use the test page as the front page.
    $this->config('system.site')->set('page.front', '/test-page')->save();

    // Initiate session with a user who can manage metatags and access content.
    $permissions = [
      'administer site configuration',
      'administer meta tags',
      'access content',
    ];
    $account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($account);
  }

  /**
   * {@inheritdoc}
   */
  protected function verbose($message, $title = NULL) {
    // Handle arrays, objects, etc.
    if (!is_string($message)) {
      $message = "<pre>\n" . print_r($message, TRUE) . "\n</pre>\n";
    }

    // Optional title to go before the output.
    if (!empty($title)) {
      $title = '<h2>' . Html::escape($title) . "</h2>\n";
    }

    parent::verbose($title . $message);
  }

  /**
   * Tests that this module's tags are available.
   */
  function testTagsArePresent() {
    // Load the global config.
    $this->drupalGet('admin/config/search/metatag/global');
    $this->assertResponse(200);

    // Confirm the various meta tags are available.
    foreach ($this->tags as $tag) {
      // Look for a custom method named "{$tagname}_test_field_xpath", if found
      // use that method to get the xpath definition for this meta tag,
      // otherwise it defaults to just looking for a text input field.
      $method = "{$tag}_test_field_xpath";
      if (method_exists($this, $method)) {
        $xpath = $this->$method();
      }
      else {
        $xpath = "//input[@name='{$tag}' and @type='text']";
      }

      $this->assertFieldByXPath($xpath, NULL, new FormattableMarkup('Found the @tag meta tag field.', ['@tag' => $tag]));
    }

    $this->drupalLogout();
  }

  /**
   * Confirm that each tag can be saved and that the output of each tag is
   * correct.
   */
  function testTagsInputOutput() {
    // Create a content type to test with.
    $this->createContentType(['type' => 'page']);
    $node = $this->drupalCreateNode([
      'title' => t('Hello, world!'),
      'type' => 'page',
    ]);

    // Test a non-entity path and an entity path. The non-entity path inherits
    // the global meta tags, the entity path inherits from its entity config.
    $paths = [
      [
        'admin/config/search/metatag/global',
        'metatag_test_custom_route',
        'Saved the Global Metatag defaults.',
      ],
      [
        'admin/config/search/metatag/node',
        'node/1',
        'Saved the Content Metatag defaults',
      ],
    ];

    // 
    foreach ($paths as $item) {
      list($path1, $path2, $save_message) = $item;

      // Load the global config.
      $this->drupalGet($path1);
      $this->assertResponse(200);

      // Update the Global defaults and test them.
      $all_values = $values = [];
      foreach ($this->tags as $tag_name) {
        // Look for a custom method named "{$tagname}_test_key", if found use
        // that method to get the test string for this meta tag, otherwise it
        // defaults to the meta tag's name.
        $method = "{$tag_name}_test_key";
        if (method_exists($this, $method)) {
          $test_key = $this->$method();
        }
        else {
          $test_key = $tag_name;
        }

        // Look for a custom method named "{$tagname}_test_value", if found use
        // that method to get the test string for this meta tag, otherwise it
        // defaults to just generating a random string.
        $method = "{$tag_name}_test_value";
        if (method_exists($this, $method)) {
          $test_value = $this->$method();
        }
        else {
          // Generate a random string. Generating two words of 8 characters each
          // with simple machine name -style strings.
          $test_value = $this->randomMachineName() . ' ' . $this->randomMachineName();
        }

        $values[$test_key] = $test_value;
        $all_values[$tag_name] = $test_value;
      }
      $this->drupalPostForm(NULL, $values, 'Save');
      $this->assertText($save_message);

      // Load the test page.
      $this->drupalGet($path2);
      $this->assertResponse(200);

      // Look for the values.
      foreach ($this->tags as $tag_name) {
        // Look for a custom method named "{$tag_name}_test_output_xpath", if
        // found use that method to get the xpath definition for this meta tag,
        // otherwise it defaults to just looking for a meta tag matching:
        // <$test_tag $test_name_attribute=$tag_name $test_value_attribute=$value />
        $method = "{$tag_name}_test_output_xpath";
        if (method_exists($this, $method)) {
          $xpath_string = $this->$method();
        }
        else {
          // Look for a custom method named "{$tag_name}_test_tag", if
          // found use that method to get the xpath definition for this meta
          // tag, otherwise it defaults to $this->test_tag.
          $method = "{$tag_name}_test_tag";
          if (method_exists($this, $method)) {
            $xpath_tag = $this->$method();
          }
          else {
            $xpath_tag = $this->test_tag;
          }

          // Look for a custom method named "{$tag_name}_test_name_attribute",
          // if found use that method to get the xpath definition for this meta
          // tag, otherwise it defaults to $this->test_name_attribute.
          $method = "{$tag_name}_test_name_attribute";
          if (method_exists($this, $method)) {
            $xpath_name_attribute = $this->$method();
          }
          else {
            $xpath_name_attribute = $this->test_name_attribute;
          }

          // Look for a custom method named "{$tag_name}_test_tag_name", if
          // found use that method to get the xpath definition for this meta
          // tag, otherwise it defaults to $tag_name.
          $method = "{$tag_name}_test_tag_name";
          if (method_exists($this, $method)) {
            $xpath_name_tag = $this->$method();
          }
          else {
            $xpath_name_tag = $this->getTestTagName($tag_name);
          }

          // Compile the xpath.
          $xpath_string = "//{$xpath_tag}[@{$xpath_name_attribute}='{$xpath_name_tag}']";
        }

        // Look for a custom method named "{$tag_name}_test_value_attribute", if
        // found use that method to get the xpath definition for this meta tag,
        // otherwise it defaults to $this->test_value_attribute.
        $method = "{$tag_name}_test_value_attribute";
        if (method_exists($this, $method)) {
          $xpath_value_attribute = $this->$method();
        }
        else {
          $xpath_value_attribute = $this->test_value_attribute;
        }

        // Extract the meta tag from the HTML.
        $xpath = $this->xpath($xpath_string);
        $this->assertEqual(count($xpath), 1, new FormattableMarkup('One @name tag found.', ['@name' => $tag_name]));
        if (count($xpath) !== 1) {
          $this->verbose($xpath, $tag_name . ': ' . $xpath_string);
        }

        // Run various tests on the output variables.
        // $this->assertTrue($xpath_string);
        // $this->assertTrue($xpath_value_attribute);
        // $this->assertTrue(isset($xpath[0][$xpath_value_attribute]));
        // $this->assertTrue($all_values[$tag_name]);
        // $this->assertTrue(isset($xpath[0][$xpath_value_attribute]));
        // $this->assertEqual($xpath[0][$xpath_value_attribute], $all_values[$tag_name], "The meta tag was found with the expected value.");
        // Most meta tags have an attribute, but some don't.
        if (!empty($xpath_value_attribute)) {
          $this->assertTrue($xpath_value_attribute);
          $this->assertTrue(isset($xpath[0][$xpath_value_attribute]));
          // Help with debugging.
          if (!isset($xpath[0][$xpath_value_attribute])) {
            $this->verbose($xpath, $tag_name . ': ' . $xpath_string);
          }
          else {
            if ((string)$xpath[0][$xpath_value_attribute] != $all_values[$tag_name]) {
              $this->verbose($xpath, $tag_name . ': ' . $xpath_string);
            }
            $this->assertTrue($xpath[0][$xpath_value_attribute]);
            $this->assertEqual($xpath[0][$xpath_value_attribute], $all_values[$tag_name], "The meta tag was found with the expected value.");
          }
        }
        else {
          $this->verbose($xpath, $tag_name . ': ' . $xpath_string);
          $this->assertTrue((string)$xpath[0]);
          $this->assertEqual((string)$xpath[0], $all_values[$tag_name], "The meta tag was found with the expected value.");
        }
      }
    }

    $this->drupalLogout();
  }

  /**
   * Convert a tag's internal name to the string which is actually used in HTML.
   *
   * The meta tag internal name will be machine names, i.e. only contain a-z,
   * A-Z, 0-9 and the underline character. Meta tag names will actually contain
   * any possible character.
   *
   * @param string $tag_name
   *   The tag name to be converted.
   *
   * @return string
   *   The converted tag name.
   */
  public function getTestTagName($tag_name) {
    return $tag_name;
  }

  /**
   * Generate a random value for testing meta tag fields.
   *
   * As a reasonable default, this will generating two words of 8 characters
   * each with simple machine name -style strings.
   *
   * @return string
   *   A normal string.
   */
  public function getTestTagValue() {
    return $this->randomMachineName() . ' ' . $this->randomMachineName();
  }

  /**
   * Generate a URL for an image.
   *
   * @return string
   *   An absolute URL to a non-existant image.
   */
  public function randomImageUrl() {
    return 'http://www.example.com/images/'  . $this->randomMachineName() . '.png';
  }

}
