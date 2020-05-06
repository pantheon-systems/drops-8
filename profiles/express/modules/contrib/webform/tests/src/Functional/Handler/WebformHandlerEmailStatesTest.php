<?php

namespace Drupal\Tests\webform\Functional\Handler;

use Drupal\webform\Entity\Webform;
use Drupal\Tests\webform\Functional\WebformBrowserTestBase;

/**
 * Tests for email webform handler email states.
 *
 * @group Webform
 */
class WebformHandlerEmailStatesTest extends WebformBrowserTestBase {

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
    $this->drupalPostForm('/webform/test_handler_email_states', [], t('Save Draft'));
    $this->assertRaw('Debug: Email: Draft saved');

    // Check completed email.
    $sid = $this->postSubmission($webform);
    $this->assertRaw('Debug: Email: Submission completed');

    $this->drupalLogin($this->rootUser);

    // Check converted email.
    $email = $this->getLastEmail();
    $this->assertEqual($email['id'], 'webform_test_handler_email_states_email_converted');

    // Check updated email.
    $this->drupalPostForm("/admin/structure/webform/manage/test_handler_email_states/submission/$sid/edit", [], t('Save'));

    /**************************************************************************/
    // @todo Fix random test failure that can't be reproduced locally.
    // $this->assertRaw('Debug: Email: Submission updated');
    /**************************************************************************/

    // Check that custom (aka no states) is only visible on the 'Resend' tab.
    $this->drupalGet("/admin/structure/webform/manage/test_handler_email_states/submission/$sid/resend");
    $this->assertRaw('<b>Subject:</b> Draft saved<br />');
    $this->assertRaw('<b>Subject:</b> Submission converted<br />');
    $this->assertRaw('<b>Subject:</b> Submission completed<br />');
    $this->assertRaw('<b>Subject:</b> Submission updated<br />');
    $this->assertRaw('<b>Subject:</b> Submission locked<br />');
    $this->assertRaw('<b>Subject:</b> Submission deleted<br />');
    $this->assertRaw('<b>Subject:</b> Submission custom<br />');

    // Check locked email.
    $this->drupalPostForm("/admin/structure/webform/manage/test_handler_email_states/submission/$sid/notes", ['locked' => TRUE], t('Save'));
    $this->assertRaw('Debug: Email: Submission locked');

    // Check deleted email.
    $this->drupalPostForm("/admin/structure/webform/manage/test_handler_email_states/submission/$sid/delete", [], t('Delete'));
    $this->assertRaw('Debug: Email: Submission deleted');

    // Check that 'Send when…' is visible.
    $this->drupalGet('/admin/structure/webform/manage/test_handler_email_states/handlers/email_draft/edit');
    $this->assertRaw('<span class="fieldset-legend">Send email</span>');

    // Check states hidden when results are disabled.
    $webform->setSetting('results_disabled', TRUE)->save();
    $this->drupalGet('/admin/structure/webform/manage/test_handler_email_states/handlers/email_draft/edit');
    $this->assertNoRaw('<span class="fieldset-legend js-form-required form-required">Send email</span>');

    // Check that only completed email is triggered when states are disabled.
    $this->postSubmission($webform);
    $this->assertNoRaw('Debug: Email: Draft saved');
    $this->assertRaw('Debug: Email: Submission completed');
    $this->assertNoRaw('Debug: Email: Submission updated');
    $this->assertNoRaw('Debug: Email: Submission deleted');
    $this->assertNoRaw('Debug: Email: Submission custom');

    // Check that resave draft handler automatically switches
    // states to completed.
    $this->drupalPostForm('/admin/structure/webform/manage/test_handler_email_states/handlers/email_draft/edit', [], t('Save'));
    $this->postSubmission($webform);
    $this->assertRaw('Debug: Email: Draft saved');
    $this->assertRaw('Debug: Email: Submission completed');
    $this->assertNoRaw('Debug: Email: Submission updated');
    $this->assertNoRaw('Debug: Email: Submission deleted');
    $this->assertNoRaw('Debug: Email: Submission custom');
  }

}
