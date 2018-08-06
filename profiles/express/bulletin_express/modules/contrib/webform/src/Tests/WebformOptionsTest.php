<?php

namespace Drupal\webform\Tests;

use Drupal\Core\Serialization\Yaml;
use Drupal\webform\Entity\WebformOptions;
use Drupal\webform\WebformInterface;

/**
 * Tests for webform option entity.
 *
 * @group Webform
 */
class WebformOptionsTest extends WebformTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['webform', 'webform_test_options'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_options'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create users.
    $this->createUsers();
  }

  /**
   * Tests webform options entity.
   */
  public function testWebformOptions() {
    $this->drupalLogin($this->normalUser);

    // Check get element options.
    $yes_no_options = ['Yes' => 'Yes', 'No' => 'No'];
    $this->assertEqual(WebformOptions::getElementOptions(['#options' => $yes_no_options]), $yes_no_options);
    $this->assertEqual(WebformOptions::getElementOptions(['#options' => 'yes_no']), $yes_no_options);
    $this->assertEqual(WebformOptions::getElementOptions(['#options' => 'not-found']), []);

    $color_options = [
      'red' => 'Red',
      'white' => 'White',
      'blue' => 'Blue',
    ];

    // Check get element options for manually defined options.
    $this->assertEqual(WebformOptions::getElementOptions(['#options' => $color_options]), $color_options);

    /** @var \Drupal\webform\WebformOptionsInterface $webform_options */
    $webform_options = WebformOptions::create([
      'langcode' => 'en',
      'status' => WebformInterface::STATUS_OPEN,
      'id' => 'test_flag',
      'title' => 'Test flag',
      'options' => Yaml::encode($color_options),
    ]);
    $webform_options->save();

    // Check get options.
    $this->assertEqual($webform_options->getOptions(), $color_options);

    // Set invalid options.
    $webform_options->set('options', "not\nvalid\nyaml")->save();

    // Check invalid options.
    $this->assertFalse($webform_options->getOptions());

    // Check hook_webform_options_alter() && hook_webform_options_WEBFORM_OPTIONS_ID_alter().
    $this->drupalGet('webform/test_options');
    $this->assertRaw('<select data-drupal-selector="edit-custom" id="edit-custom" name="custom" class="form-select"><option value="" selected="selected">- Select -</option><option value="one">One</option><option value="two">Two</option><option value="three">Three</option></select>');
    $this->assertRaw('<select data-drupal-selector="edit-test" id="edit-test" name="test" class="form-select"><option value="" selected="selected">- Select -</option><option value="four">Four</option><option value="five">Five</option><option value="six">Six</option></select>');

    // Check hook_webform_options_WEBFORM_OPTIONS_ID_alter() is not executed
    // when options are altered.
    $webform_test_options = WebformOptions::load('test');
    $webform_test_options->set('options', Yaml::encode($color_options));
    $webform_test_options->save();
    $this->debug($webform_test_options->getOptions());

    $this->drupalGet('webform/test_options');
    $this->assertRaw('<select data-drupal-selector="edit-test" id="edit-test" name="test" class="form-select"><option value="" selected="selected">- Select -</option><option value="red">Red</option><option value="white">White</option><option value="blue">Blue</option><option value="four">Four</option><option value="five">Five</option><option value="six">Six</option></select>');
  }

}
