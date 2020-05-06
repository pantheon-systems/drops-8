<?php

namespace Drupal\Tests\webform\Functional\Settings;

use Drupal\Tests\webform_node\Traits\WebformNodeBrowserTestTrait;
use Drupal\webform\Entity\Webform;
use Drupal\Tests\webform\Functional\WebformBrowserTestBase;

/**
 * Tests for webform submission form ajax.
 *
 * @group Webform
 */
class WebformSettingsAjaxTest extends WebformBrowserTestBase {

  use WebformNodeBrowserTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['webform', 'webform_node'];

  /**
   * Test webform submission form Ajax setting.
   */
  public function testAjax() {
    $webform = Webform::load('contact');

    // Check that Ajax is not enabled.
    $this->drupalGet('/webform/contact');
    $this->assertNoRaw('<div id="webform-submission-contact-form-ajax" class="webform-ajax-form-wrapper" data-effect="fade" data-progress-type="throbber">');
    $this->assertNoCssSelect('#webform-submission-contact-form-ajax');

    // Set 'Use Ajax' for the individual webform.
    $webform->setSetting('ajax', TRUE);
    $webform->save();

    // Check that Ajax is enabled for the individual webform.
    $this->drupalGet('/webform/contact');
    $this->assertRaw('<div id="webform-submission-contact-form-ajax" class="webform-ajax-form-wrapper" data-effect="fade" data-progress-type="throbber">');
    $this->assertCssSelect('#webform-submission-contact-form-ajax');
    $this->assertRaw('"effect":"fade","speed":500');

    // Unset 'Use Ajax' for the individual webform.
    $webform->setSetting('ajax', FALSE);
    $webform->save();

    // Check that Ajax is not enabled for the individual webform.
    $this->drupalGet('/webform/contact');
    $this->assertNoRaw('<div id="webform-submission-contact-form-ajax" class="webform-ajax-form-wrapper" data-effect="fade" data-progress-type="throbber">');

    // Globally enable Ajax for all webforms.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('settings.default_ajax', TRUE)
      ->set('settings.default_ajax_progress_type', 'fullscreen')
      ->set('settings.default_ajax_effect', 'slide')
      ->set('settings.default_ajax_speed', 1500)
      ->save();

    // Check that Ajax is enabled for all webforms.
    $this->drupalGet('/webform/contact');
    $this->assertRaw('<div id="webform-submission-contact-form-ajax" class="webform-ajax-form-wrapper" data-effect="slide" data-progress-type="fullscreen">');
    $this->assertRaw('"effect":"slide","speed":1500');

    // Check webform node Ajax wrapper.
    $node = $this->createWebformNode('contact');
    $this->drupalGet('/node/' . $node->id());
    $this->assertNoCssSelect('#webform-submission-contact-form-ajax');
    $this->assertCssSelect('#webform-submission-contact-node-' . $node->id() . '-form-ajax-content');
  }

}
