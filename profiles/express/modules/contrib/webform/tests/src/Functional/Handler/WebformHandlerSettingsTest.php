<?php

namespace Drupal\Tests\webform\Functional\Handler;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;

/**
 * Tests for settings webform handler functionality.
 *
 * @group Webform
 */
class WebformHandlerSettingsTest extends WebformBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_handler_settings'];

  /**
   * Test settings handler.
   */
  public function testSettingsHandler() {
    // NOTE: Using message indentation to make sure the message is matched
    // and not the input value.
    $message_indentation = '                    ';

    // Check custom save draft message.
    $edit = [
      'preview' => TRUE,
      'confirmation' => TRUE,
      'custom' => TRUE,
    ];
    $this->drupalPostForm('/webform/test_handler_settings', $edit, t('Save Draft'));
    $this->assertRaw($message_indentation . '{Custom draft saved message}');

    // Check custom save load message.
    $this->drupalGet('/webform/test_handler_settings');
    // NOTE: Adding indentation to make sure the message is matched and not input value.
    $this->assertRaw($message_indentation . '{Custom draft loaded message}');

    // Check custom preview title and message.
    $this->drupalPostForm('/webform/test_handler_settings', [], t('Preview'));
    $this->assertRaw('<li class="messages__item">{Custom preview message}</li>');
    $this->assertRaw('<h1 class="page-title">{Custom preview title}</h1>');

    // Check custom confirmation title and message.
    $this->drupalPostForm('/webform/test_handler_settings', [], t('Submit'));
    $this->assertRaw('<h1 class="page-title">{Custom confirmation title}</h1>');
    $this->assertRaw('<div class="webform-confirmation__message">{Custom confirmation message}</div>');

    // Check no custom save draft message.
    $edit = [
      'preview' => FALSE,
      'confirmation' => FALSE,
      'custom' => FALSE,
    ];
    $this->drupalPostForm('/webform/test_handler_settings', $edit, t('Save Draft'));
    $this->assertNoRaw($message_indentation . '{Custom draft saved message}');

    // Check no custom save load message.
    $this->drupalGet('/webform/test_handler_settings');
    $this->assertNoRaw($message_indentation . '{Custom draft loaded message}');

    // Check no custom preview title and message.
    $this->drupalPostForm('/webform/test_handler_settings', [], t('Preview'));
    $this->assertNoRaw('<h1 class="page-title">{Custom confirmation title}</h1>');
    $this->assertNoRaw('<div class="webform-confirmation__message">{Custom confirmation message}</div>');

    // Check no custom confirmation title and message.
    $this->drupalPostForm('/webform/test_handler_settings', [], t('Submit'));
    $this->assertNoRaw('{Custom confirmation title}');
    $this->assertNoRaw('{Custom confirmation message}');
  }

}
