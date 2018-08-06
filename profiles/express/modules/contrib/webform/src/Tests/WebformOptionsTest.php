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
    $element = ['#options' => $yes_no_options];
    $this->assertEqual(WebformOptions::getElementOptions($element), $yes_no_options);
    $element = ['#options' => 'yes_no'];
    $this->assertEqual(WebformOptions::getElementOptions($element), $yes_no_options);
    $element = ['#options' => 'not-found'];
    $this->assertEqual(WebformOptions::getElementOptions($element), []);

    $color_options = [
      'red' => 'Red',
      'white' => 'White',
      'blue' => 'Blue',
    ];

    // Check get element options for manually defined options.
    $element = ['#options' => $color_options];
    $this->assertEqual(WebformOptions::getElementOptions($element), $color_options);

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
    // Check that the default value can be set from the alter hook.
    $this->drupalGet('webform/test_options');
    $this->assertRaw('<select data-drupal-selector="edit-custom" id="edit-custom" name="custom" class="form-select"><option value="">- None -</option><option value="one" selected="selected">One</option><option value="two">Two</option><option value="three">Three</option></select>');
    $this->assertRaw('<select data-drupal-selector="edit-test" id="edit-test" name="test" class="form-select"><option value="" selected="selected">- None -</option><option value="four">Four</option><option value="five">Five</option><option value="six">Six</option></select>');

    // Check hook_webform_options_WEBFORM_OPTIONS_ID_alter() is not executed
    // when options are altered.
    $webform_test_options = WebformOptions::load('test');
    $webform_test_options->set('options', Yaml::encode($color_options));
    $webform_test_options->save();
    $this->debug($webform_test_options->getOptions());

    $this->drupalGet('webform/test_options');
    $this->assertRaw('<select data-drupal-selector="edit-test" id="edit-test" name="test" class="form-select"><option value="" selected="selected">- None -</option><option value="red">Red</option><option value="white">White</option><option value="blue">Blue</option><option value="four">Four</option><option value="five">Five</option><option value="six">Six</option></select>');

    // Check custom options set via alter hook().
    $this->drupalGet('webform/test_options');
    $this->assertRaw('<select data-drupal-selector="edit-test" id="edit-test" name="test" class="form-select"><option value="" selected="selected">- None -</option><option value="red">Red</option><option value="white">White</option><option value="blue">Blue</option><option value="four">Four</option><option value="five">Five</option><option value="six">Six</option></select>');

    // Check that 'Afghanistan' is the first option.
    $element = ['#options' => 'country_names'];
    $options = WebformOptions::getElementOptions($element);
    $this->assertEqual(reset($options), 'Afghanistan');

    // Check that custom options can be customized.
    $country_names_options = WebformOptions::load('country_names');
    $country_names_options->set('options', Yaml::encode(['Switzerland' => 'Switzerland'] + $country_names_options->getOptions()));
    $country_names_options->save();

    // Check that 'Switzerland' is the now first option.
    $element = ['#options' => 'country_names'];
    $options = WebformOptions::getElementOptions($element);
    $this->assertEqual(reset($options), 'Switzerland');

    // Make sure we can reach the option admin pages.
    $this->drupalLogin($this->adminWebformUser);
    $this->drupalGet('admin/structure/webform/config/options/manage');
    $this->drupalGet('admin/structure/webform/config/options/manage/add');
  }

}
