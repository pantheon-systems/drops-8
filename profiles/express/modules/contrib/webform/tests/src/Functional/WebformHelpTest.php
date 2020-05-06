<?php

namespace Drupal\Tests\webform\Functional;

/**
 * Tests for webform help.
 *
 * @group Webform
 */
class WebformHelpTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['block', 'help', 'webform_test_message_custom'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->drupalPlaceBlock('help_block');
  }

  /**
   * Tests webform help.
   */
  public function testHelp() {
    $this->drupalLogin($this->rootUser);

    // Check notifications, promotion, and welcome messages displayed.
    $this->drupalGet('/admin/structure/webform');
    $this->assertRaw('This is a warning notification.');
    $this->assertRaw('This is an info notification.');
    $this->assertRaw('If you enjoy and value Drupal and the Webform module,');

    // Close all notifications, promotion, and welcome messages.
    $this->drupalGet('/admin/structure/webform');
    $this->clickLink('×', 0);
    $this->drupalGet('/admin/structure/webform');
    $this->clickLink('×', 0);
    $this->drupalGet('/admin/structure/webform');
    $this->clickLink('×', 0);

    // Check notifications, promotion, and welcome messages closed.
    $this->drupalGet('/admin/structure/webform');
    $this->assertNoRaw('This is a warning notification.');
    $this->assertNoRaw('This is an info notification.');
    $this->assertNoRaw('If you enjoy and value Drupal and the Webform module,');

    // Check that help is enabled.
    $this->drupalGet('/admin/structure/webform/config/advanced');
    $this->assertRaw('block block-help block-help-block');
    $this->assertRaw('The <strong>Advanced configuration</strong> page allows an administrator to enable/disable UI behaviors, manage requirements and define data used for testing webforms.');

    // Disable help via the UI which will clear the cached help block.
    $this->drupalPostForm('/admin/structure/webform/config/advanced', ['ui[help_disabled]' => TRUE], t('Save configuration'));

    // Check that help is disabled.
    $this->drupalGet('/admin/structure/webform/config/advanced');
    $this->assertNoRaw('block block-help block-help-block');
    $this->assertNoRaw('The <strong>Advanced configuration</strong> page allows an administrator to enable/disable UI behaviors, manage requirements and define data used for testing webforms.');

  }

}
