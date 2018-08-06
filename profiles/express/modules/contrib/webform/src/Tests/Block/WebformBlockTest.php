<?php

namespace Drupal\webform\Tests\Block;

use Drupal\webform\Tests\WebformTestBase;

/**
 * Tests for webform block.
 *
 * @group Webform
 */
class WebformBlockTest extends WebformTestBase {

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
    $block = $this->drupalPlaceBlock('webform_block');

    // Check contact webform.
    $block->getPlugin()->setConfigurationValue('webform_id', 'contact');
    $block->save();
    $this->drupalGet('<front>');
    $this->assertRaw('webform-submission-contact-add-form');

    // Check contact webform with default data.
    $block->getPlugin()->setConfigurationValue('default_data', "name: 'John Smith'");
    $block->save();
    $this->drupalGet('<front>');
    $this->assertRaw('webform-submission-contact-add-form');
    $this->assertFieldByName('name', 'John Smith');

    // Check confirmation inline webform.
    $block->getPlugin()->setConfigurationValue('webform_id', 'test_confirmation_inline');
    $block->save();
    $this->drupalPostForm('<front>', [], t('Submit'));
    $this->assertRaw('This is a custom inline confirmation message.');

    // Check confirmation message webform.
    $block->getPlugin()->setConfigurationValue('webform_id', 'test_confirmation_message');
    $block->save();
    $this->drupalPostForm('<front>', [], t('Submit'));
    $this->assertRaw('This is a <b>custom</b> confirmation message.');
  }

}
