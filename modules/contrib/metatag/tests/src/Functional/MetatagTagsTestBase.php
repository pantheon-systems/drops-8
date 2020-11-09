<?php

namespace Drupal\Tests\metatag\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Tests\BrowserTestBase;
use Symfony\Component\DependencyInjection\Container;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Base class to test all of the meta tags that are in a specific module.
 */
abstract class MetatagTagsTestBase extends BrowserTestBase {

  use MetatagHelperTrait;
  use StringTranslationTrait;

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
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * All of the meta tags defined by this module which will be tested.
   *
   * @var array
   */
  protected $tags = [];

  /**
   * The tag to look for when testing the output.
   *
   * @var string
   */
  protected $testTag = 'meta';

  /**
   * {@inheritdoc}
   *
   * @var string
   */
  protected $testNameAttribute = 'name';

  /**
   * The attribute to look for when testing the output.
   *
   * @var string
   */
  protected $testValueAttribute = 'content';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Use the test page as the front page.
    $this->config('system.site')->set('page.front', '/test-page')->save();

    // Initiate session with a user who can manage meta tags and access content.
    $permissions = [
      'administer site configuration',
      'administer meta tags',
      'access content',
    ];
    $account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($account);
  }

  /**
   * Tests that this module's tags are available.
   */
  public function testTagsArePresent() {
    // Load the global config.
    $this->drupalGet('admin/config/search/metatag/global');
    $this->assertSession()->statusCodeEquals(200);

    // Confirm the various meta tags are available.
    foreach ($this->tags as $tag) {
      // Look for a custom method named "{$tagname}TestFieldXpath", if found
      // use that method to get the xpath definition for this meta tag,
      // otherwise it defaults to just looking for a text input field.
      $method = $this->getMethodFromTagCallback($tag, 'test_field_xpath');
      if (method_exists($this, $method)) {
        $xpath = $this->$method();
      }
      else {
        $xpath = "//input[@name='{$tag}' and @type='text']";
      }

      $this->assertFieldByXPath($xpath, NULL, new FormattableMarkup('Found the @tag meta tag field using the xpath: @xpath', ['@tag' => $tag, '@xpath' => $xpath]));
    }

    $this->drupalLogout();
  }

  /**
   * Confirm that each tag can be saved and that the output is correct.
   *
   * Each tag is passed in one at a time (using the dataProvider) to make it
   * easier to distinguish when a problem occurs.
   *
   * @param string $tag_name
   *   The tag to test.
   *
   * @dataProvider tagsInputOutputProvider
   */
  public function testTagsInputOutput($tag_name) {
    // Create a content type to test with.
    $this->createContentType(['type' => 'page']);
    $this->drupalCreateNode([
      'title' => $this->t('Hello, world!'),
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

    foreach ($paths as $item) {
      [$path1, $path2, $save_message] = $item;

      // Load the global config.
      $this->drupalGet($path1);
      $this->assertSession()->statusCodeEquals(200);

      // Update the Global defaults and test them.
      $all_values = $values = [];
      // Look for a custom method named "{$tagname}TestKey", if found use
      // that method to get the test string for this meta tag, otherwise it
      // defaults to the meta tag's name.
      $method = $this->getMethodFromTagCallback($tag_name, 'TestKey');
      if (method_exists($this, $method)) {
        $test_key = $this->$method();
      }
      else {
        $test_key = $tag_name;
      }

      // Look for a custom method named "{$tagname}TestValue", if found use
      // that method to get the test string for this meta tag, otherwise it
      // defaults to just generating a random string.
      $method = $this->getMethodFromTagCallback($tag_name, 'TestValue');
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
      $this->drupalPostForm(NULL, $values, 'Save');
      $this->assertText($save_message);

      // Load the test page.
      $this->drupalGet($path2);
      $this->assertSession()->statusCodeEquals(200);

      // Look for the values.
      // Look for a custom method named "{$tag_name}TestOutputXpath", if
      // found use that method to get the xpath definition for this meta tag,
      // otherwise it defaults to just looking for a meta tag matching:
      // {@code}
      // <$testTag $testNameAttribute=$tag_name $testValueAttribute=$value />
      // {@endcode}
      $method = $this->getMethodFromTagCallback($tag_name, 'TestOutputXpath');
      if (method_exists($this, $method)) {
        $xpath_string = $this->$method();
      }
      else {
        // Look for a custom method named "{$tag_name}TestTag", if
        // found use that method to get the xpath definition for this meta
        // tag, otherwise it defaults to $this->testTag.
        $method = $this->getMethodFromTagCallback($tag_name, 'TestTag');
        if (method_exists($this, $method)) {
          $xpath_tag = $this->$method();
        }
        else {
          $xpath_tag = $this->testTag;
        }

        // Look for a custom method named "{$tag_name}TestNameAttribute",
        // if found use that method to get the xpath definition for this meta
        // tag, otherwise it defaults to $this->testNameAttribute.
        $method = $this->getMethodFromTagCallback($tag_name, 'TestNameAttribute');
        if (method_exists($this, $method)) {
          $xpath_name_attribute = $this->$method();
        }
        else {
          $xpath_name_attribute = $this->testNameAttribute;
        }

        // Look for a custom method named "{$tag_name}TestTagName", if
        // found use that method to get the xpath definition for this meta
        // tag, otherwise it defaults to $tag_name.
        $method = $this->getMethodFromTagCallback($tag_name, 'TestTagName');
        if (method_exists($this, $method)) {
          $xpath_name_tag = $this->$method();
        }
        else {
          $xpath_name_tag = $this->getTestTagName($tag_name);
        }

        // Compile the xpath.
        $xpath_string = "//{$xpath_tag}[@{$xpath_name_attribute}='{$xpath_name_tag}']";
      }

      // Look for a custom method named "{$tag_name}TestValueAttribute", if
      // found use that method to get the xpath definition for this meta tag,
      // otherwise it defaults to $this->testValueAttribute.
      $method = $this->getMethodFromTagCallback($tag_name, 'TestValueAttribute');
      if (method_exists($this, $method)) {
        $xpath_value_attribute = $this->$method();
      }
      else {
        $xpath_value_attribute = $this->testValueAttribute;
      }

      // Extract the meta tag from the HTML.
      $xpath = $this->xpath($xpath_string);
      $this->assertEqual(count($xpath), 1, new FormattableMarkup('One @tag tag found using @xpath.', ['@tag' => $tag_name, '@xpath' => $xpath_string]));
      if (count($xpath) !== 1) {
        $this->verbose($xpath, $tag_name . ': ' . $xpath_string);
      }

      // Run various tests on the output variables.
      // Most meta tags have an attribute, but some don't.
      if (!empty($xpath_value_attribute)) {
        $this->assertNotEmpty($xpath_value_attribute);
        $this->assertTrue($xpath[0]->hasAttribute($xpath_value_attribute));
        // Help with debugging.
        if (!$xpath[0]->hasAttribute($xpath_value_attribute)) {
          $this->verbose($xpath, $tag_name . ': ' . $xpath_string);
        }
        else {
          if ((string) $xpath[0]->getAttribute($xpath_value_attribute) != $all_values[$tag_name]) {
            $this->verbose($xpath, $tag_name . ': ' . $xpath_string);
          }
          $this->assertNotEmpty($xpath[0]->getAttribute($xpath_value_attribute));
          $this->assertEqual($xpath[0]->getAttribute($xpath_value_attribute), $all_values[$tag_name], "The '{$tag_name}' tag was found with the expected value.");
        }
      }
      else {
        $this->verbose($xpath, $tag_name . ': ' . $xpath_string);
        $this->assertTrue((string) $xpath[0]);
        $this->assertEqual((string) $xpath[0], $all_values[$tag_name], new FormattableMarkup("The '@tag' tag was found with the expected value '@value'.", ['@tag' => $tag_name, '@value' => $all_values[$tag_name]]));
      }
    }

    $this->drupalLogout();
  }

  /**
   * Data provider for testTagsInputOutput.
   *
   * @return array
   *   The set of tags to test.
   */
  public function tagsInputOutputProvider() {
    $set = [];
    foreach ($this->tags as $tag) {
      $set[$tag] = [$tag];
    }
    return $set;
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
  protected function getTestTagName($tag_name) {
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
  protected function getTestTagValue() {
    return $this->randomMachineName() . ' ' . $this->randomMachineName();
  }

  /**
   * Generate a URL for an image.
   *
   * @return string
   *   An absolute URL to a non-existent image.
   */
  protected function randomImageUrl() {
    return 'https://www.example.com/images/' . $this->randomMachineName() . '.png';
  }

  /**
   * Convert a tag name with a callback to a lowerCamelCase method name.
   *
   * @param string $tag_name
   *   The meta tag name.
   * @param string $callback
   *   The callback that is to be used.
   *
   * @return string
   *   The tag name and callback concatenated together and converted to
   *   lowerCamelCase.
   */
  private function getMethodFromTagCallback($tag_name, $callback) {
    return lcfirst(Container::camelize($tag_name . '_' . $callback));
  }

}
