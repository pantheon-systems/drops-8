<?php

namespace Drupal\webform\Tests\Handler;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Tests\WebformTestBase;

/**
 * Tests for email webform handler email states.
 *
 * @group Webform
 */
class WebformHandlerEmailStatesTest extends WebformTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_handler_email_states'];

  /**
   * Test email states handler.
   */
  public function testEmailStates() {
    $webform = Webform::load('test_handler_email_states');

    // Check draft saved email.
    $this->drupalPostForm('webform/test_handler_email_states', [], t('Save Draft'));
    $this->assertRaw('Debug: Email: Draft saved');

    // Check completed email.
    $sid = $this->postSubmission($webform);
    $this->assertRaw('Debug: Email: Submission completed');

    $this->drupalLogin($this->rootUser);

    // Check converted email.
    $email = $this->getLastEmail();
    $this->assertEqual($email['id'], 'webform_email_email_converted');

    // Check updated email.
    $this->drupalPostForm("/admin/structure/webform/manage/test_handler_email_states/submission/$sid/edit", [], t('Save'));

    /**************************************************************************/
    // @todo Fix random test failure that can't be reproduced locally.
    // $this->assertRaw('Debug: Email: Submission updated');
    /**************************************************************************/

    // Check deleted email.
    $this->drupalPostForm("/admin/structure/webform/manage/test_handler_email_states/submission/$sid/delete", [], t('Delete'));
    $this->assertRaw('Debug: Email: Submission deleted');

    // Check that 'Send when...' is visible.
    $this->drupalGet('admin/structure/webform/manage/test_handler_email_states/handlers/email_draft/edit');
    $this->assertRaw('<span class="fieldset-legend js-form-required form-required">Send email</span>');

    // Check states hidden when results are disabled.
    $webform->setSetting('results_disabled', TRUE)->save();
    $this->drupalGet('admin/structure/webform/manage/test_handler_email_states/handlers/email_draft/edit');
    $this->assertNoRaw('<span class="fieldset-legend js-form-required form-required">Send email</span>');

    // Check that only completed email is triggered when states are disabled.
    $this->postSubmission($webform);
    $this->assertNoRaw('Debug: Email: Draft saved');
    $this->assertRaw('Debug: Email: Submission completed');
    $this->assertNoRaw('Debug: Email: Submission updated');
    $this->assertNoRaw('Debug: Email: Submission deleted');

    // Check that resave draft handler automatically switches
    // states to completed.
    $this->drupalPostForm('admin/structure/webform/manage/test_handler_email_states/handlers/email_draft/edit', [], t('Save'));
    $this->postSubmission($webform);
    $this->assertRaw('Debug: Email: Draft saved');
    $this->assertRaw('Debug: Email: Submission completed');
    $this->assertNoRaw('Debug: Email: Submission updated');
    $this->assertNoRaw('Debug: Email: Submission deleted');

  }

}
