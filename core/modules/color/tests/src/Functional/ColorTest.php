<?php

namespace Drupal\Tests\color\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Modify the Bartik theme colors and make sure the changes are reflected on the
 * frontend.
 *
 * @group color
 */
class ColorTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['color', 'color_test', 'block', 'file'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user with administrative permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $bigUser;

  /**
   * An associative array of settings for themes.
   *
   * @var array
   */
  protected $themes;

  /**
   * Associative array of hex color strings to test.
   *
   * Keys are the color string and values are a Boolean set to TRUE for valid
   * colors.
   *
   * @var array
   */
  protected $colorTests;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create user.
    $this->bigUser = $this->drupalCreateUser(['administer themes']);

    // This tests the color module in Bartik.
    $this->themes = [
      'bartik' => [
        'palette_input' => 'palette[bg]',
        'scheme' => 'slate',
        'scheme_color' => '#3b3b3b',
      ],
      'color_test_theme' => [
        'palette_input' => 'palette[bg]',
        'scheme' => 'custom',
        'scheme_color' => '#3b3b3b',
      ],
    ];
    \Drupal::service('theme_installer')->install(array_keys($this->themes));

    // Array filled with valid and not valid color values.
    $this->colorTests = [
      '#000' => TRUE,
      '#123456' => TRUE,
      '#abcdef' => TRUE,
      '#0' => FALSE,
      '#00' => FALSE,
      '#0000' => FALSE,
      '#00000' => FALSE,
      '123456' => FALSE,
      '#00000g' => FALSE,
    ];
  }

  /**
   * Tests the Color module functionality.
   */
  public function testColor() {
    foreach ($this->themes as $theme => $test_values) {
      $this->_testColor($theme, $test_values);
    }
  }

  /**
   * Tests the Color module functionality using the given theme.
   *
   * @param string $theme
   *   The machine name of the theme being tested.
   * @param array $test_values
   *   An associative array of test settings (i.e. 'Main background', 'Text
   *   color', 'Color set', etc) for the theme which being tested.
   */
  public function _testColor($theme, $test_values) {
    $this->config('system.theme')
      ->set('default', $theme)
      ->save();
    $settings_path = 'admin/appearance/settings/' . $theme;

    $this->drupalLogin($this->bigUser);
    $this->drupalGet($settings_path);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContainsOnce('Color set');
    $edit['scheme'] = '';
    $edit[$test_values['palette_input']] = '#123456';
    $this->drupalGet($settings_path);
    $this->submitForm($edit, 'Save configuration');

    $this->drupalGet('<front>');
    $stylesheets = $this->config('color.theme.' . $theme)->get('stylesheets');
    /** @var \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator */
    $file_url_generator = \Drupal::service('file_url_generator');
    // Make sure the color stylesheet is included in the content.
    foreach ($stylesheets as $stylesheet) {
      $this->assertSession()->responseMatches('|' . $file_url_generator->generateString($stylesheet) . '|');
      $stylesheet_content = implode("\n", file($stylesheet));
      $this->assertStringContainsString('color: #123456', $stylesheet_content, 'Make sure the color we changed is in the color stylesheet. (' . $theme . ')');
    }

    $this->drupalGet($settings_path);
    $this->assertSession()->statusCodeEquals(200);
    $edit['scheme'] = $test_values['scheme'];
    $this->drupalGet($settings_path);
    $this->submitForm($edit, 'Save configuration');

    $this->drupalGet('<front>');
    $stylesheets = $this->config('color.theme.' . $theme)->get('stylesheets');
    foreach ($stylesheets as $stylesheet) {
      $stylesheet_content = implode("\n", file($stylesheet));
      $this->assertStringContainsString('color: ' . $test_values['scheme_color'], $stylesheet_content, 'Make sure the color we changed is in the color stylesheet. (' . $theme . ')');
    }

    // Test with aggregated CSS turned on.
    $config = $this->config('system.performance');
    $config->set('css.preprocess', 1);
    $config->save();
    $this->drupalGet('<front>');
    $stylesheets = \Drupal::state()->get('drupal_css_cache_files', []);
    $stylesheet_content = '';
    foreach ($stylesheets as $uri) {
      $stylesheet_content .= implode("\n", file(\Drupal::service('file_system')->realpath($uri)));
    }
    $this->assertStringNotContainsString('public://', $stylesheet_content, 'Make sure the color paths have been translated to local paths. (' . $theme . ')');
    $config->set('css.preprocess', 0);
    $config->save();
  }

  /**
   * Tests whether the provided color is valid.
   */
  public function testValidColor() {
    $this->config('system.theme')
      ->set('default', 'bartik')
      ->save();
    $settings_path = 'admin/appearance/settings/bartik';

    $this->drupalLogin($this->bigUser);
    $edit['scheme'] = '';

    foreach ($this->colorTests as $color => $is_valid) {
      $edit['palette[bg]'] = $color;
      $this->drupalGet($settings_path);
      $this->submitForm($edit, 'Save configuration');

      if ($is_valid) {
        $this->assertSession()->pageTextContains('The configuration options have been saved.');
      }
      else {
        $this->assertSession()->pageTextContains('You must enter a valid hexadecimal color value for Main background.');
      }
    }
  }

  /**
   * Tests whether the custom logo is used in the color preview.
   */
  public function testLogoSettingOverride() {
    $this->drupalLogin($this->bigUser);
    $edit = [
      'default_logo' => FALSE,
      'logo_path' => 'core/misc/druplicon.png',
    ];
    $this->drupalGet('admin/appearance/settings');
    $this->submitForm($edit, 'Save configuration');

    // Ensure that the overridden logo is present in Bartik, which is colorable.
    $this->drupalGet('admin/appearance/settings/bartik');
    $this->assertSame($GLOBALS['base_path'] . 'core/misc/druplicon.png', $this->getDrupalSettings()['color']['logo']);
  }

  /**
   * Tests whether the scheme can be set, viewed anonymously and reset.
   */
  public function testOverrideAndResetScheme() {
    $settings_path = 'admin/appearance/settings/bartik';
    $this->config('system.theme')
      ->set('default', 'bartik')
      ->save();

    // Place branding block with site name and slogan into header region.
    $this->drupalPlaceBlock('system_branding_block', ['region' => 'header']);

    $this->drupalGet('');
    // Make sure the color logo is not being used.
    $this->assertSession()->responseNotContains('files/color/bartik-');
    // Make sure the original bartik logo exists.
    $this->assertSession()->responseContains('bartik/logo.svg');

    // Log in and set the color scheme to 'slate'.
    $this->drupalLogin($this->bigUser);
    $edit['scheme'] = 'slate';
    $this->drupalGet($settings_path);
    $this->submitForm($edit, 'Save configuration');

    // Visit the homepage and ensure color changes.
    $this->drupalLogout();
    $this->drupalGet('');
    // Make sure the color logo is being used.
    $this->assertSession()->responseContains('files/color/bartik-');
    // Make sure the original bartik logo does not exist.
    $this->assertSession()->responseNotContains('bartik/logo.svg');

    // Log in and set the color scheme back to default (delete config).
    $this->drupalLogin($this->bigUser);
    $edit['scheme'] = 'default';
    $this->drupalGet($settings_path);
    $this->submitForm($edit, 'Save configuration');

    // Log out and ensure there is no color and we have the original logo.
    $this->drupalLogout();
    $this->drupalGet('');
    // Make sure the color logo is not being used.
    $this->assertSession()->responseNotContains('files/color/bartik-');
    // Make sure the original bartik logo exists.
    $this->assertSession()->responseContains('bartik/logo.svg');
  }

}
