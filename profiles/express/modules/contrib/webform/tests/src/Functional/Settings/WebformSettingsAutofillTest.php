<?php

namespace Drupal\Tests\webform\Functional\Settings;

use Drupal\webform\Entity\Webform;
use Drupal\Tests\webform\Functional\WebformBrowserTestBase;

/**
 * Tests for webform submission form autofill.
 *
 * @group Webform
 */
class WebformSettingsAutofillTest extends WebformBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_form_autofill'];

  /**
   * Test webform submission form autofill.
   */
  public function testAutofill() {
    $this->drupalLogin($this->rootUser);

    $webform = Webform::load('test_form_autofill');

    // Check that elements are both blank.
    $this->drupalGet('/webform/test_form_autofill');
    $this->assertNoRaw('This submission has been autofilled with your previous submission.');
    $this->assertFieldByName('textfield_autofill', '');
    $this->assertFieldByName('textfield_excluded', '');

    // Create a submission.
    $edit = [
      'textfield_autofill' => '{textfield_autofill}',
      'textfield_excluded' => '{textfield_excluded}',
    ];
    $this->postSubmission($webform, $edit);

    // Check that 'textfield_autofill' is autofilled and 'textfield_excluded'
    // is empty.
    $this->drupalGet('/webform/test_form_autofill');
    $this->assertFieldByName('textfield_autofill', '{textfield_autofill}');
    $this->assertNoFieldByName('textfield_autofill', '{textfield_excluded}');
    $this->assertFieldByName('textfield_excluded', '');

    // Check that default configuration message is displayed.
    $this->drupalGet('/webform/test_form_autofill');
    $this->assertFieldByName('textfield_autofill', '{textfield_autofill}');
    $this->assertRaw('This submission has been autofilled with your previous submission.');

    // Clear default autofill message.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('settings.default_autofill_message', '')
      ->save();

    // Check no autofill message is displayed.
    $this->drupalGet('/webform/test_form_autofill');
    $this->assertFieldByName('textfield_autofill', '{textfield_autofill}');
    $this->assertNoRaw('This submission has been autofilled with your previous submission.');

    // Set custom automfill message.
    $webform
      ->setSetting('autofill_message', '{autofill_message}')
      ->save();

    // Check custom autofill message is displayed.
    $this->drupalGet('/webform/test_form_autofill');
    $this->assertFieldByName('textfield_autofill', '{textfield_autofill}');
    $this->assertRaw('{autofill_message}');
  }

}
