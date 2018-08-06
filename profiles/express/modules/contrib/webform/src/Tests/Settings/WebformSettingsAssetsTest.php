<?php

namespace Drupal\webform\Tests\Settings;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Tests\WebformTestBase;

/**
 * Tests for webform assets settings.
 *
 * @group Webform
 */
class WebformSettingsAssetsTest extends WebformTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_form_assets'];

  /**
   * Tests webform assets.
   */
  public function testAssets() {
    $webform_assets = Webform::load('test_form_assets');

    // Check has CSS and JavaScript.
    $this->drupalGet('webform/test_form_assets');
    $this->assertRaw('<link rel="stylesheet" href="' . base_path() . 'webform/css/test_form_assets?');
    $this->assertRaw('<script src="' . base_path() . 'webform/javascript/test_form_assets?');

    // Clear CSS and JavaScript.
    $webform_assets->setCss('')->setJavaScript('')->save();

    // Check has no CSS or JavaScript.
    $this->drupalGet('webform/test_form_assets');
    $this->assertNoRaw('<link rel="stylesheet" href="' . base_path() . 'webform/css/test_form_assets?');
    $this->assertNoRaw('<script src="' . base_path() . 'webform/javascript/test_form_assets?');

    // Add global CSS and JS on all webforms.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('assets.css', '/**/')
      ->set('assets.javascript', '/**/')
      ->save();

    // Check has global CSS and JavaScript.
    $this->drupalGet('webform/test_form_assets');
    $this->assertRaw('<link rel="stylesheet" href="' . base_path() . 'webform/css/test_form_assets?');
    $this->assertRaw('<script src="' . base_path() . 'webform/javascript/test_form_assets?');
  }

}
