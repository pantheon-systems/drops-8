<?php

namespace Drupal\Tests\responsive_preview\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Tests\BrowserTestBase;

/**
 * Responsive preview base test class.
 */
abstract class ResponsivePreviewTestBase extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['responsive_preview'];

  /**
   * Return the default devices.
   *
   * @param bool $enabled_only
   *   Whether return only devices enabled by default.
   *
   * @return array
   *   An array of the default devices.
   */
  protected function getDefaultDevices($enabled_only = FALSE) {
    $devices = [
      'galaxy_s7' => 'Galaxy S7',
      'galaxy_tab_2_10' => 'Galaxy Tab 2 10"',
      'ipad_air_2' => 'iPad Air 2',
      'iphone_7' => 'iPhone 7',
      'iphone_7plus' => 'iPhone 7+',
    ];

    if ($enabled_only) {
      return $devices;
    }

    $devices += [
      'large' => 'Typical desktop',
      'medium' => 'Tablet',
      'small' => 'Smart phone',
    ];

    return $devices;
  }

  /**
   * Tests exposed devices in the responsive preview list.
   *
   * @param array $devices
   *   An array of devices to check.
   */
  protected function assertDeviceListEquals(array $devices) {
    $device_buttons = $this->xpath('//button[@data-responsive-preview-name]');
    $this->assertTrue(count($devices) === count($device_buttons));

    foreach ($device_buttons as $button) {
      $name = $button->getAttribute('data-responsive-preview-name');
      $this->assertTrue(!empty($name) && in_array($name, $devices), new FormattableMarkup('%name device shown', ['%name' => $name]));
    }
  }

  /**
   * Asserts whether responsive preview cache metadata is present.
   */
  protected function assertResponsivePreviewCachesTagAndContexts() {
    $this->assertSession()
      ->responseHeaderContains('X-Drupal-Cache-Tags', 'config:responsive_preview_device_list');
    $this->assertSession()
      ->responseHeaderContains('X-Drupal-Cache-Contexts', 'route.is_admin');
  }

  /**
   * Asserts whether responsive preview cache metadata is not present.
   */
  protected function assertNoResponsivePreviewCachesTagAndContexts() {
    $this->assertSession()
      ->responseHeaderNotContains('X-Drupal-Cache-Tags', 'config:responsive_preview_device_list');
    $this->assertSession()
      ->responseHeaderNotContains('X-Drupal-Cache-Contexts', 'route.is_admin');
  }

  /**
   * Asserts whether responsive preview library is included.
   */
  protected function assertResponsivePreviewLibrary() {
    $this->assertSession()
      ->responseContains('responsive_preview/js/responsive-preview.js');
    $this->assertSession()
      ->responseContains('responsive_preview/css/responsive-preview.icons.css');
    $this->assertSession()
      ->responseContains('responsive_preview/css/responsive-preview.module.css');
    $this->assertSession()
      ->responseContains('responsive_preview/css/responsive-preview.theme.css');
  }

  /**
   * Asserts whether responsive preview library is not included.
   */
  protected function assertNoResponsivePreviewLibrary() {
    $this->assertSession()
      ->responseNotContains('responsive_preview/js/responsive-preview.js');
    $this->assertSession()
      ->responseNotContains('responsive_preview/css/responsive-preview.icons.css');
    $this->assertSession()
      ->responseNotContains('responsive_preview/css/responsive-preview.module.css');
    $this->assertSession()
      ->responseNotContains('responsive_preview/css/responsive-preview.theme.css');
  }

}
