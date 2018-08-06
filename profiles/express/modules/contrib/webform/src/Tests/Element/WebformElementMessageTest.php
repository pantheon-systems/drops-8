<?php

namespace Drupal\webform\Tests\Element;

use Drupal\webform\Tests\WebformTestBase;
use Drupal\webform\Entity\Webform;

/**
 * Tests for message webform element.
 *
 * @group Webform
 */
class WebformElementMessageTest extends WebformTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_message'];

  /**
   * Tests message element.
   */
  public function testMessage() {
    $webform = Webform::load('test_element_message');

    $this->drupalGet('webform/test_element_message');

    // Check basic message.
    $this->assertRaw('<div data-drupal-selector="edit-message-info" class="webform-message js-webform-message js-form-wrapper form-wrapper" id="edit-message-info">');
    $this->assertRaw('<div role="contentinfo" aria-label="" class="messages messages--info">');
    $this->assertRaw('This is an <strong>info</strong> message.');

    // Check close message with slide effect.
    $this->assertRaw('<div data-drupal-selector="edit-message-close-slide" class="webform-message js-webform-message webform-message--close js-webform-message--close js-form-wrapper form-wrapper" data-message-close-effect="slide" id="edit-message-close-slide">');
    $this->assertRaw('<div role="contentinfo" aria-label="" class="messages messages--info">');
    $this->assertRaw('<a href="#close" aria-label="close" class="js-webform-message__link webform-message__link">×</a>This is message that can be <b>closed using slide effect</b>.');

    // Set user and state storage.
    $elements = [
      'message_close_storage_user' => $webform->getElementDecoded('message_close_storage_user'),
      'message_close_storage_state' => $webform->getElementDecoded('message_close_storage_state'),
    ];
    $webform->setElements($elements);
    $webform->save();

    // Check that close links are not enabled for 'user' or 'state' storage
    // for anonymous users.
    $this->drupalGet('webform/test_element_message');
    $this->assertRaw('href="#close"');
    $this->assertNoRaw('data-message-storage="user"');
    $this->assertNoRaw('data-message-storage="state"');

    // Login to test closing message via 'user' and 'state' storage.
    $this->drupalLogin($this->drupalCreateUser());

    // Check that close links are enabled.
    $this->drupalGet('webform/test_element_message');
    $this->assertNoRaw('href="#close"');
    $this->assertRaw('data-drupal-selector="edit-message-close-storage-user"');
    $this->assertRaw('data-message-storage="user"');
    $this->assertRaw('data-drupal-selector="edit-message-close-storage-state"');
    $this->assertRaw('data-message-storage="state"');

    // Close message using 'user' storage.
    $this->drupalGet('webform/test_element_message');
    $this->clickLink('×', 0);

    // Check that 'user' storage message is removed.
    $this->drupalGet('webform/test_element_message');
    $this->assertNoRaw('data-drupal-selector="edit-message-close-storage-user"');
    $this->assertNoRaw('data-message-storage="user"');
    $this->assertRaw('data-drupal-selector="edit-message-close-storage-state"');
    $this->assertRaw('data-message-storage="state"');

    // Close message using 'state' storage.
    $this->drupalGet('webform/test_element_message');
    $this->clickLink('×', 0);

    // Check that 'state' and 'user' storage message is removed.
    $this->drupalGet('webform/test_element_message');
    $this->assertNoRaw('data-drupal-selector="edit-message-close-storage-user"');
    $this->assertNoRaw('data-message-storage="user"');
    $this->assertNoRaw('data-drupal-selector="edit-message-close-storage-state"');
    $this->assertNoRaw('data-message-storage="state"');
  }

}
