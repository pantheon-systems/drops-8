<?php

namespace Drupal\Tests\webform\Functional\Settings;

use Drupal\webform\Entity\Webform;
use Drupal\Tests\webform\Functional\WebformBrowserTestBase;

/**
 * Tests for webform assets settings.
 *
 * @group Webform
 */
class WebformSettingsAssetsTest extends WebformBrowserTestBase {

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

    // Check has CSS (href) and JavaScript (src).
    $this->drupalGet('/webform/test_form_assets');
    $this->assertRaw('href="' . base_path() . 'webform/css/test_form_assets?');
    $this->assertRaw('src="' . base_path() . 'webform/javascript/test_form_assets?');

    // Clear CSS (href) and JavaScript (src).
    $webform_assets->setCss('')->setJavaScript('')->save();

    // Check has no CSS (href) and JavaScript (src).
    $this->drupalGet('/webform/test_form_assets');
    $this->assertNoRaw('href="' . base_path() . 'webform/css/test_form_assets?');
    $this->assertNoRaw('src="' . base_path() . 'webform/javascript/test_form_assets?');

    // Add global CSS and JS on all webforms.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('assets.css', '/**/')
      ->set('assets.javascript', '/**/')
      ->save();

    // Check has global CSS (href) and JavaScript (src).
    $this->drupalGet('/webform/test_form_assets');
    $this->assertRaw('href="' . base_path() . 'webform/css/test_form_assets?');
    $this->assertRaw('src="' . base_path() . 'webform/javascript/test_form_assets?');
  }

}
