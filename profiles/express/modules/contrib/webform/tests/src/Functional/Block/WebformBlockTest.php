<?php

namespace Drupal\Tests\webform\Functional\Block;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;

/**
 * Tests for webform block.
 *
 * @group Webform
 */
class WebformBlockTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['block', 'webform'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_confirmation_inline', 'test_confirmation_message'];

  /**
   * Tests webform block.
   */
  public function testBlock() {
    // Place block.
    $block = $this->drupalPlaceBlock('webform_block', [
      'webform_id' => 'contact',
    ]);

    // Check contact webform.
    $this->drupalGet('/<front>');
    $this->assertRaw('webform-submission-contact-add-form');

    // Check contact webform with default data.
    $block->getPlugin()->setConfigurationValue('default_data', "name: 'John Smith'");
    $block->save();
    $this->drupalGet('/<front>');
    $this->assertRaw('webform-submission-contact-add-form');
    $this->assertFieldByName('name', 'John Smith');

    // Check confirmation inline webform.
    $block->getPlugin()->setConfigurationValue('webform_id', 'test_confirmation_inline');
    $block->save();
    $this->drupalPostForm('/<front>', [], t('Submit'));
    $this->assertRaw('This is a custom inline confirmation message.');

    // Check confirmation message webform displayed on front page.
    $block->getPlugin()->setConfigurationValue('webform_id', 'test_confirmation_message');
    $block->save();
    $this->drupalPostForm('/<front>', [], t('Submit'));
    $this->assertRaw('This is a <b>custom</b> confirmation message.');
    $this->assertUrl('/user/login');

    // Check confirmation message webform display on webform URL.
    $block->getPlugin()->setConfigurationValue('redirect', TRUE);
    $block->save();
    $this->drupalPostForm('/<front>', [], t('Submit'));
    $this->assertRaw('This is a <b>custom</b> confirmation message.');
    $this->assertUrl('webform/test_confirmation_message');

  }

}
